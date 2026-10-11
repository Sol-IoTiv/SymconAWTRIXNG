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
        $this->RegisterPropertyString('IconRules', '[]');
        $this->RegisterPropertyBoolean('ManageBuiltins', false);
        foreach (['Time', 'Date', 'Temperature', 'Humidity', 'Battery'] as $name) {
            $this->RegisterPropertyBoolean('Builtin' . $name, in_array($name, ['Time', 'Date'], true));
        }
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
            ['name' => 'Key', 'caption' => 'Kennung (Pflichtfeld, eindeutig)', 'width' => '160px', 'add' => '', 'edit' => ['type' => 'ValidationTextBox', 'validate' => '^[A-Za-z0-9_-]{1,16}$']],
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
                ['type' => 'List', 'name' => 'Displays', 'caption' => 'Anzeigen', 'rowCount' => 8, 'add' => true, 'delete' => true, 'columns' => $columns],
                ['type' => 'ExpansionPanel', 'caption' => 'Icons abhängig vom Wert', 'items' => [
                    ['type' => 'Label', 'caption' => 'Kennung wie in der Anzeigenliste. Es gilt die kleinste passende Obergrenze (Wert kleiner oder gleich). Oberhalb aller Grenzen gilt das normale Icon der Anzeige. Ohne Regeln bleibt das Icon fest.'],
                    ['type' => 'List', 'name' => 'IconRules', 'caption' => 'Icon-Regeln', 'rowCount' => 6, 'add' => true, 'delete' => true, 'columns' => [
                        ['name' => 'Key', 'caption' => 'Anzeigekennung (Pflichtfeld)', 'width' => '200px', 'add' => '', 'edit' => ['type' => 'ValidationTextBox', 'validate' => '^[A-Za-z0-9_-]{1,16}$']],
                        ['name' => 'Maximum', 'caption' => 'Wert bis einschließlich', 'width' => '180px', 'add' => 0, 'edit' => ['type' => 'NumberSpinner', 'digits' => 3]],
                        ['name' => 'Icon', 'caption' => 'Icon', 'width' => '200px', 'add' => '', 'edit' => ['type' => 'Select', 'options' => $options]]
                    ]]
                ]],
                ['type' => 'ExpansionPanel', 'caption' => 'Standardanzeigen der Uhr', 'items' => [
                    ['type' => 'CheckBox', 'name' => 'ManageBuiltins', 'caption' => 'Standardanzeigen über Symcon steuern'],
                    ['type' => 'Label', 'caption' => 'Bei aktivierter Steuerung erscheinen nur angehakte Standardanzeigen. Ohne Steuerung bleiben die Einstellungen auf der Uhr erhalten.'],
                    ['type' => 'CheckBox', 'name' => 'BuiltinTime', 'caption' => 'Uhrzeit'],
                    ['type' => 'CheckBox', 'name' => 'BuiltinDate', 'caption' => 'Datum'],
                    ['type' => 'CheckBox', 'name' => 'BuiltinTemperature', 'caption' => 'Gerätetemperatur'],
                    ['type' => 'CheckBox', 'name' => 'BuiltinHumidity', 'caption' => 'Luftfeuchtigkeit des Geräts'],
                    ['type' => 'CheckBox', 'name' => 'BuiltinBattery', 'caption' => 'Batteriestand der Uhr']
                ]]
            ],
            'actions' => [
                ['type' => 'Button', 'caption' => 'Verbindung testen', 'onClick' => 'echo AWNG_TestConnection($id);'],
                ['type' => 'Button', 'caption' => 'Icon-Galerie öffnen (TC001)', 'link' => true, 'onClick' => 'echo "https://awtrix.de/icons?device=esp32&panel=32x8&caps=audio.rtttl,sensors.light";'],
                ['type' => 'Button', 'caption' => 'Icons auf der Uhr verwalten', 'link' => true, 'onClick' => 'echo AWNG_GetIconsURL($id);'],
                ['type' => 'Label', 'caption' => 'In der Galerie anmelden, Icon öffnen und mit Send to AWTRIX auf die Uhr übertragen. Danach hier Icons von Uhr laden und in der Anzeigenzeile auswählen. Änderungen vorher übernehmen.'],
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

    public function GetIconsURL(): string
    {
        try {
            return $this->BaseURL() . '/#/icons';
        } catch (Throwable $e) {
            $this->Failure($e);
            return '';
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
            $rules = $this->IconRules();
            $this->SyncBuiltins();
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
                $icon = $this->SelectIcon($row, $value, $rules);
                if ($icon !== '') {
                    $payload['icon'] = $icon;
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

    private function IconRules(): array
    {
        $rules = json_decode($this->ReadPropertyString('IconRules'), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($rules)) {
            throw new InvalidArgumentException('Die Icon-Regeln sind ungültig.');
        }
        $seen = [];
        foreach ($rules as $rule) {
            if (!is_array($rule) || !isset($rule['Key'], $rule['Maximum'], $rule['Icon']) ||
                !preg_match('/^[A-Za-z0-9_-]{1,16}$/D', $rule['Key']) ||
                !is_numeric($rule['Maximum']) || !is_finite((float) $rule['Maximum']) || !is_string($rule['Icon'])) {
                throw new InvalidArgumentException('Jede Icon-Regel benötigt eine Kennung, eine numerische Obergrenze und ein Icon.');
            }
            $key = $rule['Key'] . ':' . (string) (float) $rule['Maximum'];
            if (isset($seen[$key])) {
                throw new InvalidArgumentException('Doppelte Icon-Obergrenze für ' . $rule['Key']);
            }
            $seen[$key] = true;
        }
        usort($rules, static function (array $a, array $b): int {
            return (float) $a['Maximum'] <=> (float) $b['Maximum'];
        });
        return $rules;
    }

    private function SelectIcon(array $row, $value, array $rules): string
    {
        // Compare raw numeric values, independently of displayed rounding or units.
        if (is_int($value) || is_float($value)) {
            foreach ($rules as $rule) {
                if ($rule['Key'] === $row['Key'] && $value <= (float) $rule['Maximum']) {
                    return $rule['Icon'];
                }
            }
        }
        return $row['Icon'];
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

    private function SyncBuiltins(): void
    {
        if (!$this->ReadPropertyBoolean('ManageBuiltins')) {
            return;
        }
        $apps = json_decode($this->Request('GET', '/api/v1/apps'), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($apps)) {
            throw new RuntimeException('Ungültige App-Liste von der Uhr.');
        }
        foreach ($apps as $app) {
            $name = $app['name'] ?? '';
            if (($app['origin'] ?? '') !== 'builtin' || !in_array($name, ['Time', 'Date', 'Temperature', 'Humidity', 'Battery'], true)) {
                continue;
            }
            $enabled = $this->ReadPropertyBoolean('Builtin' . $name);
            // Avoid repeated writes to the clock's persistent settings.
            if (($app['enabled'] ?? null) !== $enabled) {
                $this->Request('PUT', '/api/v1/apps/' . rawurlencode($name) . '/enabled', $enabled);
            }
        }
    }

    private function Request(string $method, string $path, $payload = null, bool $allowMissing = false): string
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
