<?php

declare(strict_types=1);

class AWTRIXNGDiscovery extends IPSModule
{
    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyString('Broadcast', '255.255.255.255');
        $this->RegisterAttributeString('Devices', '[]');
    }

    public function GetConfigurationForm()
    {
        $values = [];
        foreach (json_decode($this->ReadAttributeString('Devices'), true) ?: [] as $device) {
            $instance = 0;
            foreach (IPS_GetInstanceListByModuleID('{A872657B-C512-42D6-B13E-EAE02AD408B9}') as $id) {
                if (IPS_GetProperty($id, 'Host') === $device['Host']) {
                    $instance = $id;
                    break;
                }
            }
            $values[] = [
                'Name' => $device['Name'], 'Host' => $device['Host'], 'instanceID' => $instance,
                'create' => [
                    'moduleID' => '{A872657B-C512-42D6-B13E-EAE02AD408B9}',
                    'configuration' => ['Host' => $device['Host']],
                    'name' => 'AWTRIX NG ' . $device['Name']
                ]
            ];
        }
        return json_encode([
            'elements' => [
                ['type' => 'Label', 'caption' => 'Geräte suchen, Uhr auswählen und Instanz erstellen. Die Uhr muss im gleichen lokalen Netz wie Symcon erreichbar sein.'],
                ['type' => 'ValidationTextBox', 'name' => 'Broadcast', 'caption' => 'Broadcast-Adresse (normalerweise unverändert lassen)']
            ],
            'actions' => [
                ['type' => 'Button', 'caption' => 'Geräte suchen', 'onClick' => 'echo AWNGD_Search($id);'],
                ['type' => 'Configurator', 'name' => 'Devices', 'rowCount' => 8,
                    'columns' => [
                        ['name' => 'Name', 'caption' => 'Uhr', 'width' => '250px'],
                        ['name' => 'Host', 'caption' => 'IP-Adresse', 'width' => '200px']
                    ], 'values' => $values]
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function Search(): string
    {
        if (!IPS_SemaphoreEnter('AWNG_Discovery', 100)) {
            return 'Eine Gerätesuche läuft bereits.';
        }
        $socket = false;
        try {
            $broadcast = trim($this->ReadPropertyString('Broadcast'));
            if (filter_var($broadcast, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                throw new InvalidArgumentException('Eine gültige IPv4-Broadcast-Adresse eingeben.');
            }
            $context = stream_context_create(['socket' => ['so_broadcast' => true]]);
            $socket = @stream_socket_server('udp://0.0.0.0:4211', $errno, $error, STREAM_SERVER_BIND, $context);
            if ($socket === false) {
                throw new RuntimeException('UDP-Port 4211 kann nicht geöffnet werden: ' . $error);
            }
            if (@stream_socket_sendto($socket, 'FIND_AWTRIXNG', 0, $broadcast . ':4210') === false) {
                throw new RuntimeException('Suchanfrage konnte nicht gesendet werden.');
            }
            $devices = [];
            $deadline = microtime(true) + 2.0;
            while (($remaining = $deadline - microtime(true)) > 0) {
                $read = [$socket];
                $write = null;
                $except = null;
                $seconds = (int) $remaining;
                $micros = (int) (($remaining - $seconds) * 1000000);
                $ready = @stream_select($read, $write, $except, $seconds, $micros);
                if ($ready === false) {
                    throw new RuntimeException('Fehler beim Empfang der Suchantworten.');
                }
                if ($ready === 0) {
                    break;
                }
                $reply = stream_socket_recvfrom($socket, 256, 0, $peer);
                $device = self::ParseReply((string) $reply, (string) $peer);
                if ($device !== null) {
                    $devices[$device['Host']] = $device;
                }
            }
            ksort($devices, SORT_NATURAL);
            $this->WriteAttributeString('Devices', json_encode(array_values($devices), JSON_THROW_ON_ERROR));
            $this->ReloadForm();
            return count($devices) . ' Uhr(en) gefunden. Keine Treffer? WLAN, Firewall und Broadcast-Adresse prüfen oder IP manuell eingeben.';
        } catch (Throwable $e) {
            return $e->getMessage();
        } finally {
            if (is_resource($socket)) {
                fclose($socket);
            }
            IPS_SemaphoreLeave('AWNG_Discovery');
        }
    }

    private static function ParseReply(string $reply, string $peer): ?array
    {
        if (!preg_match('/^([A-Za-z0-9][A-Za-z0-9-]{0,62})(?::([0-9]{1,5}))?$/D', trim($reply), $parts)) {
            return null;
        }
        $ip = explode(':', $peer)[0];
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return null;
        }
        $port = isset($parts[2]) ? (int) $parts[2] : 80;
        if ($port < 1 || $port > 65535) {
            return null;
        }
        return ['Name' => $parts[1], 'Host' => $ip . ($port === 80 ? '' : ':' . $port)];
    }
}
