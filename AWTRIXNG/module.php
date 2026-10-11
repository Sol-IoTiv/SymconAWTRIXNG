<?php

declare(strict_types=1);

class AWTRIXNG extends IPSModule
{
    public function Create()
    {
        parent::Create();
        $this->RegisterPropertyString('Host', '');
        $this->RegisterPropertyString('Username', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyInteger('Interval', 60);
        $this->RegisterPropertyString('Displays', '[]');
        $this->RegisterAttributeString('Icons', '[]');
        $this->RegisterAttributeString('Managed', '{}');
        $this->RegisterTimer('Refresh', 0, 'AWNG_UpdateDisplays($_IPS["TARGET"]);');
        $this->RegisterVariableString('LastError', 'Letzter Fehler', '', 0);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
        foreach ($this->GetMessageList() as $sender => $messages) {
            if (in_array(VM_UPDATE, $messages, true)) {
                $this->UnregisterMessage((int) $sender, VM_UPDATE);
            }
        }
        try {
            $this->BaseURL();
            foreach ($this->Rows() as $row) {
                if ($row['Enabled']) {
                    $this->RegisterMessage((int) $row['VariableID'], VM_UPDATE);
                }
            }
            $this->SetTimerInterval('Refresh', max(10, $this->ReadPropertyInteger('Interval')) * 1000);
            $this->SetStatus(102);
            $this->UpdateDisplays();
        } catch (Throwable $e) {
            $this->SetTimerInterval('Refresh', 0);
            $this->Failure($e);
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        if ($Message === VM_UPDATE && !empty($Data[1])) {
            $this->UpdateDisplays();
        }
    }

    public function GetConfigurationForm()
    {
        $options = [['caption' => 'Kein Icon', 'value' => '']];
        foreach (json_decode($this->ReadAttributeString('Icons'), true) ?: [] as $icon) {
            $options[] = ['caption' => $icon, 'value' => $icon];
        }
        $columns = [
            ['name' => 'Enabled', 'caption' => 'Aktiv', 'width' => '65px', 'add' => true, 'edit' => ['type' => 'CheckBox']],
            ['name' => 'Key', 'caption' => 'Kennung (eindeutig)', 'width' => '160px', 'add' => '', 'edit' => ['type' => 'ValidationTextBox']],
            ['name' => 'VariableID', 'caption' => 'Variable', 'width' => '220px', 'add' => 0, 'edit' => ['type' => 'SelectVariable']],
            ['name' => 'Prefix', 'caption' => 'Text davor', 'width' => '130px', 'add' => '', 'edit' => ['type' => 'ValidationTextBox']],
            ['name' => 'Suffix', 'caption' => 'Einheit / Text danach', 'width' => '150px', 'add' => '', 'edit' => ['type' => 'ValidationTextBox']],
            ['name' => 'Decimals', 'caption' => 'Nachkommastellen', 'width' => '130px', 'add' => 1, 'edit' => ['type' => 'NumberSpinner', 'minimum' => 0, 'maximum' => 6]],
            ['name' => 'Icon', 'caption' => 'Icon', 'width' => '160px', 'add' => '', 'edit' => ['type' => 'Select', 'options' => $options]],
            ['name' => 'Duration', 'caption' => 'Dauer (s)', 'width' => '100px', 'add' => 5, 'edit' => ['type' => 'NumberSpinner', 'minimum' => 1, 'maximum' => 300]],
            ['name' => 'Color', 'caption' => 'Textfarbe', 'width' => '120px', 'add' => 16777215, 'edit' => ['type' => 'SelectColor']]
        ];
        return json_encode([
            'elements' => [
                ['type' => 'Label', 'caption' => 'AWTRIX NG per HTTP – kein MQTT erforderlich.'],
                ['type' => 'ValidationTextBox', 'name' => 'Host', 'caption' => 'IP-Adresse oder Hostname der Uhr'],
                ['type' => 'ExpansionPanel', 'caption' => 'Anmeldung (nur bei aktiviertem Login auf der Uhr)', 'items' => [
                    ['type' => 'ValidationTextBox', 'name' => 'Username', 'caption' => 'Benutzername'],
                    ['type' => 'PasswordTextBox', 'name' => 'Password', 'caption' => 'Passwort']
                ]],
                ['type' => 'NumberSpinner', 'name' => 'Interval', 'caption' => 'Regelmäßig aktualisieren (Sekunden)', 'minimum' => 10, 'maximum' => 3600],
                ['type' => 'Label', 'caption' => 'Zuerst IP übernehmen, dann Icons laden. Kennung: 1–16 Buchstaben, Ziffern, _ oder -. Einheit inklusive Leerzeichen eingeben, z. B. " °C".'],
                ['type' => 'List', 'name' => 'Displays', 'caption' => 'Anzeigen', 'rowCount' => 8, 'add' => true, 'delete' => true, 'columns' => $columns]
            ],
            'actions' => [
                ['type' => 'Button', 'caption' => 'Verbindung testen', 'onClick' => 'echo AWNG_TestConnection($id);'],
                ['type' => 'Button', 'caption' => 'Icons von Uhr laden', 'onClick' => 'echo AWNG_LoadIcons($id);'],
                ['type' => 'Button', 'caption' => 'Testnachricht', 'onClick' => 'AWNG_Notify($id, "Hallo von Symcon!", "", 5);'],
                ['type' => 'Button', 'caption' => 'Anzeigen jetzt senden', 'onClick' => 'AWNG_UpdateDisplays($id);']
            ],
            'status' => [
                ['code' => 102, 'icon' => 'active', 'caption' => 'Bereit'],
                ['code' => 201, 'icon' => 'error', 'caption' => 'Konfiguration oder Verbindung prüfen – siehe Letzter Fehler']
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function TestConnection(): string
    {
        try {
            $result = $this->Request('GET', '/api/v1/version');
            json_decode($result, true, 512, JSON_THROW_ON_ERROR);
            $this->Success();
            return 'Uhr erreichbar: ' . $result;
        } catch (Throwable $e) {
            $this->Failure($e);
            return $e->getMessage();
        }
    }

    public function LoadIcons(): string
    {
        try {
            $result = json_decode($this->Request('GET', '/api/v1/files?dir=/ICONS'), true, 512, JSON_THROW_ON_ERROR);
            if (!isset($result['files']) || !is_array($result['files'])) {
                throw new RuntimeException('Ungültige Icon-Liste von der Uhr.');
            }
            $icons = [];
            foreach ($result['files'] as $file) {
                $name = basename((string) $file['name']);
                if (preg_match('/\.(gif|jpe?g)$/i', $name)) {
                    $icons[] = pathinfo($name, PATHINFO_FILENAME);
                }
            }
            $icons = array_values(array_unique($icons));
            sort($icons, SORT_NATURAL);
            $this->WriteAttributeString('Icons', json_encode($icons, JSON_THROW_ON_ERROR));
            $this->Success();
            $this->ReloadForm();
            return count($icons) . ' Icons geladen.';
        } catch (Throwable $e) {
            $this->Failure($e);
            return $e->getMessage();
        }
    }

    public function UpdateDisplays(): bool
    {
        if (!IPS_SemaphoreEnter('AWNG_' . $this->InstanceID, 100)) {
            return false;
        }
        try {
            $base = $this->BaseURL();
            $rows = $this->Rows();
            // Ownership is scoped to the host: never delete names on another clock.
            $all = json_decode($this->ReadAttributeString('Managed'), true) ?: [];
            $managed = $all[$base] ?? [];
            $wanted = [];
            foreach ($rows as $row) {
                if (!$row['Enabled']) {
                    continue;
                }
                $name = 'ips_' . $this->InstanceID . '_' . $row['Key'];
                $value = GetValue((int) $row['VariableID']);
                if (is_bool($value)) {
                    $text = $value ? 'Ein' : 'Aus';
                } elseif (is_int($value) || is_float($value)) {
                    $text = number_format((float) $value, (int) $row['Decimals'], ',', '');
                } else {
                    $text = (string) $value;
                }
                $payload = [
                    'text' => $row['Prefix'] . $text . $row['Suffix'],
                    'durationMs' => (int) $row['Duration'] * 1000,
                    'textColor' => sprintf('#%06X', (int) $row['Color']),
                    'lifetimeMs' => max(10, $this->ReadPropertyInteger('Interval')) * 3000,
                    'lifetimeExpiry' => 'mark'
                ];
                if ($row['Icon'] !== '') {
                    $payload['icon'] = $row['Icon'];
                }
                $this->Request('PUT', '/api/v1/apps/pushed/' . rawurlencode($name), $payload);
                $wanted[] = $name;
                // Persist each success so interrupted runs can still clean up their apps.
                $managed = array_values(array_unique(array_merge($managed, [$name])));
                $all[$base] = $managed;
                $this->WriteAttributeString('Managed', json_encode($all, JSON_THROW_ON_ERROR));
            }
            foreach (array_diff($managed, $wanted) as $name) {
                $this->Request('DELETE', '/api/v1/apps/' . rawurlencode($name), null, true);
                $managed = array_values(array_diff($managed, [$name]));
                $all[$base] = $managed;
                $this->WriteAttributeString('Managed', json_encode($all, JSON_THROW_ON_ERROR));
            }
            $this->Success();
            return true;
        } catch (Throwable $e) {
            $this->Failure($e);
            return false;
        } finally {
            IPS_SemaphoreLeave('AWNG_' . $this->InstanceID);
        }
    }

    public function Notify(string $Text, string $Icon = '', int $Duration = 5): bool
    {
        try {
            $payload = ['text' => $Text, 'durationMs' => max(1, min(300, $Duration)) * 1000];
            if ($Icon !== '') {
                $payload['icon'] = $Icon;
            }
            $this->Request('POST', '/api/v1/notifications', $payload);
            $this->Success();
            return true;
        } catch (Throwable $e) {
            $this->Failure($e);
            return false;
        }
    }

    private function Rows(): array
    {
        $rows = json_decode($this->ReadPropertyString('Displays'), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($rows)) {
            throw new InvalidArgumentException('Die Anzeigenliste ist ungültig.');
        }
        $keys = [];
        foreach ($rows as &$row) {
            $row += ['Enabled' => true, 'Key' => '', 'VariableID' => 0, 'Prefix' => '', 'Suffix' => '', 'Decimals' => 1, 'Icon' => '', 'Duration' => 5, 'Color' => 16777215];
            if (!$row['Enabled']) {
                continue;
            }
            if (!preg_match('/^[A-Za-z0-9_-]{1,16}$/D', $row['Key']) || isset($keys[$row['Key']])) {
                throw new InvalidArgumentException('Jede aktive Anzeige benötigt eine eindeutige Kennung (1–16 Zeichen).');
            }
            $keys[$row['Key']] = true;
            if (!IPS_VariableExists((int) $row['VariableID'])) {
                throw new InvalidArgumentException('Variable fehlt für Anzeige ' . $row['Key']);
            }
            if ($row['Decimals'] < 0 || $row['Decimals'] > 6 || $row['Duration'] < 1 || $row['Duration'] > 300 || $row['Color'] < 0 || $row['Color'] > 16777215) {
                throw new InvalidArgumentException('Ungültiges Zahlenformat, Dauer oder Farbe: ' . $row['Key']);
            }
        }
        unset($row);
        return $rows;
    }

    private function BaseURL(): string
    {
        $host = trim($this->ReadPropertyString('Host'));
        // Accept hostnames, IPv4 and bracketed IPv6, optionally with a port.
        if (!preg_match('/^(?:[A-Za-z0-9.-]+|\[[0-9a-fA-F:]+\])(?::[0-9]{1,5})?$/D', $host)) {
            throw new InvalidArgumentException('IP-Adresse oder Hostname ohne http:// und ohne Pfad eingeben.');
        }
        return 'http://' . strtolower($host);
    }

    private function Request(string $method, string $path, ?array $payload = null, bool $allowMissing = false): string
    {
        $handle = curl_init($this->BaseURL() . $path);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_HTTPHEADER => ['Content-Type: application/json']
        ]);
        if ($this->ReadPropertyString('Username') !== '') {
            curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($handle, CURLOPT_USERPWD, $this->ReadPropertyString('Username') . ':' . $this->ReadPropertyString('Password'));
        }
        if ($payload !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        }
        try {
            $body = curl_exec($handle);
            $code = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
            if ($body === false) {
                throw new RuntimeException('Uhr nicht erreichbar: ' . curl_error($handle));
            }
            if ($allowMissing && $code === 404) {
                return '';
            }
            if ($code < 200 || $code >= 300) {
                throw new RuntimeException('AWTRIX HTTP ' . $code . ': ' . substr($body, 0, 300));
            }
            return $body;
        } finally {
            curl_close($handle);
        }
    }

    private function Success(): void
    {
        $this->SetValue('LastError', '');
        $this->SetStatus(102);
    }

    private function Failure(Throwable $error): void
    {
        $this->SetValue('LastError', $error->getMessage());
        $this->SetStatus(201);
    }
}
