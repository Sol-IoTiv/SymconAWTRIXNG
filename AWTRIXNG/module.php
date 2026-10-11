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
        $this->RegisterPropertyBoolean('ManageAudio', false);
        $this->RegisterPropertyInteger('Volume', 60);
        $this->RegisterPropertyString('Sound', '');
        $this->RegisterAttributeString('Melodies', '{}');
        $this->RegisterPropertyBoolean('ManageBuiltins', false);
        $this->RegisterPropertyBoolean('ManageAppearance', false);
        $this->RegisterPropertyBoolean('AutoTransition', true);
        $this->RegisterPropertyString('TransitionEffect', 'Fade');
        $this->RegisterPropertyInteger('TransitionDurationMs', 500);
        $this->RegisterPropertyString('TransitionDirection', 'normal');
        $this->RegisterPropertyBoolean('AutoBrightness', true);
        $this->RegisterPropertyInteger('Brightness', 120);
        $this->RegisterAttributeString('Transitions', '{}');
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
                if ($row['Enabled'] && $row['Type'] === 'variable') {
                    $this->RegisterMessage((int) $row['VariableID'], VM_UPDATE);
                }
            }
            $this->SetTimerInterval('Refresh', max(10, $this->ReadPropertyInteger('Interval')) * 1000);
            $this->SetStatus(102);
            $this->UpdateDisplays(true);
        } catch (Throwable $e) {
            $this->SetTimerInterval('Refresh', 0);
            $this->Failure($e);
        }
        $this->ReloadForm();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        if ($Message === VM_UPDATE && !empty($Data[1])) {
            $this->UpdateDisplays();
        }
    }

    public function GetConfigurationForm()
    {
        $transitionOptions = [];
        $transitionsByHost = json_decode($this->ReadAttributeString('Transitions'), true) ?: [];
        $hostKey = 'http://' . strtolower(trim($this->ReadPropertyString('Host')));
        foreach ($transitionsByHost[$hostKey] ?? [] as $transition) {
            $transitionOptions[] = ['caption' => $transition, 'value' => $transition];
        }
        if (!in_array($this->ReadPropertyString('TransitionEffect'), array_column($transitionOptions, 'value'), true)) {
            $transitionOptions[] = ['caption' => $this->ReadPropertyString('TransitionEffect') . ' (noch nicht geprüft)', 'value' => $this->ReadPropertyString('TransitionEffect')];
        }
        $options = [['caption' => 'Kein Icon', 'value' => '']];
        foreach (json_decode($this->ReadAttributeString('Icons'), true) ?: [] as $icon) {
            $options[] = ['caption' => $icon, 'value' => $icon];
        }
        $melodies = json_decode($this->ReadAttributeString('Melodies'), true) ?: [];
        $soundOptions = [['caption' => 'Kein Ton', 'value' => '']];
        foreach ($melodies[$hostKey] ?? [] as $name) {
            $soundOptions[] = ['caption' => $name, 'value' => $name];
        }
        $selectedSound = $this->ReadPropertyString('Sound');
        if ($selectedSound !== '' && !in_array($selectedSound, array_column($soundOptions, 'value'), true)) {
            $soundOptions[] = ['caption' => $selectedSound . ' (neu laden)', 'value' => $selectedSound];
        }
        $displayOptions = [];
        $displayKeys = [];
        foreach (json_decode($this->ReadPropertyString('Displays'), true) ?: [] as $display) {
            $key = (string) ($display['Key'] ?? '');
            if (preg_match('/^[A-Za-z0-9_-]{1,16}$/D', $key) && !isset($displayKeys[$key])) {
                $displayKeys[$key] = true;
                $displayOptions[] = ['caption' => $key, 'value' => $key];
            }
        }
        // Keep old references visible when their display was renamed or removed.
        foreach (json_decode($this->ReadPropertyString('IconRules'), true) ?: [] as $rule) {
            $key = (string) ($rule['Key'] ?? '');
            if ($key !== '' && !isset($displayKeys[$key])) {
                $displayKeys[$key] = true;
                $displayOptions[] = ['caption' => $key . ' (Anzeige fehlt)', 'value' => $key];
            }
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
            ['name' => 'Color', 'caption' => 'Textfarbe', 'width' => '120px', 'add' => 16777215, 'edit' => ['type' => 'SelectColor']],
            ['name' => 'Type', 'caption' => 'Inhalt', 'width' => '140px', 'add' => 'variable', 'edit' => ['type' => 'Select', 'options' => [
                ['caption' => 'Variablenwert', 'value' => 'variable'], ['caption' => 'Laufschrift', 'value' => 'text'], ['caption' => 'Countdown (Tage)', 'value' => 'countdown']
            ]]],
            ['name' => 'Text', 'caption' => 'Freier Text (nur Laufschrift)', 'width' => '220px', 'add' => '', 'edit' => ['type' => 'ValidationTextBox']],
            ['name' => 'EndDate', 'caption' => 'Enddatum (JJJJ-MM-TT, nur Countdown)', 'width' => '180px', 'add' => '', 'edit' => ['type' => 'ValidationTextBox']],
            ['name' => 'ScrollSpeed', 'caption' => 'Laufschrift-Tempo (%)', 'width' => '130px', 'add' => 100, 'edit' => ['type' => 'NumberSpinner', 'minimum' => 1, 'maximum' => 200]]
        ];
        return json_encode([
            'elements' => [
                ['type' => 'Label', 'caption' => 'AWTRIX NG per HTTP – kein MQTT erforderlich.'],
                ['type' => 'ValidationTextBox', 'name' => 'Host', 'caption' => 'IP-Adresse oder Hostname der Uhr'],
                ['type' => 'ExpansionPanel', 'caption' => 'Anmeldung (nur bei aktiviertem Login auf der Uhr)', 'items' => [
                    ['type' => 'ValidationTextBox', 'name' => 'Username', 'caption' => 'Benutzername'],
                    ['type' => 'PasswordTextBox', 'name' => 'Password', 'caption' => 'Passwort']
                ]],
                ['type' => 'NumberSpinner', 'name' => 'Interval', 'caption' => 'Alle Anzeigen erneut senden (Sekunden)', 'minimum' => 10, 'maximum' => 3600],
                ['type' => 'Label', 'caption' => 'Zuerst IP übernehmen, dann Icons laden. Inhalt wählen: Variablenwert, Laufschrift oder Countdown. Nur die zum Inhalt passenden Felder ausfüllen. Countdown: Enddatum JJJJ-MM-TT, Einheit z. B. " Tage". Kennung: 1–16 Buchstaben, Ziffern, _ oder -. Leerzeichen vor Einheiten mit eingeben.'],
                ['type' => 'List', 'name' => 'Displays', 'caption' => 'Anzeigen (Reihenfolge per Drag-and-drop ändern, danach übernehmen)', 'rowCount' => 8, 'add' => true, 'delete' => true, 'changeOrder' => true, 'columns' => $columns],
                ['type' => 'ExpansionPanel', 'caption' => 'Icons abhängig vom Wert', 'items' => [
                    ['type' => 'Label', 'caption' => 'Anzeige aus der Liste auswählen. Neue oder geänderte Anzeigen zuerst übernehmen. Es gilt die kleinste passende Obergrenze (Wert kleiner oder gleich). Oberhalb aller Grenzen gilt das normale Icon der Anzeige.'],
                    ['type' => 'List', 'name' => 'IconRules', 'caption' => 'Icon-Regeln', 'rowCount' => 6, 'add' => count($displayOptions) > 0, 'delete' => true, 'columns' => [
                        ['name' => 'Key', 'caption' => 'Anzeige', 'width' => '200px', 'add' => $displayOptions[0]['value'] ?? '', 'edit' => ['type' => 'Select', 'options' => $displayOptions]],
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
                ]],
                ['type' => 'ExpansionPanel', 'caption' => 'Anzeige und Übergänge', 'items' => [
                    ['type' => 'CheckBox', 'name' => 'ManageAppearance', 'caption' => 'Übergänge und Helligkeit über Symcon steuern'],
                    ['type' => 'Label', 'caption' => 'Gilt für die gesamte Uhr. Zuerst IP übernehmen und Übergänge von Uhr laden. Einstellungen werden beim Übernehmen oder mit Anzeigen jetzt senden abgeglichen. Dauer 0 bedeutet sofortiger Wechsel.'],
                    ['type' => 'CheckBox', 'name' => 'AutoTransition', 'caption' => 'Automatisch zur nächsten Anzeige wechseln'],
                    ['type' => 'Select', 'name' => 'TransitionEffect', 'caption' => 'Übergang', 'options' => $transitionOptions],
                    ['type' => 'NumberSpinner', 'name' => 'TransitionDurationMs', 'caption' => 'Übergangsdauer (Millisekunden, 500 = 0,5 Sekunden)', 'minimum' => 0, 'maximum' => 10000],
                    ['type' => 'Select', 'name' => 'TransitionDirection', 'caption' => 'Bewegungsrichtung', 'options' => [['caption' => 'Normal', 'value' => 'normal'], ['caption' => 'Umgekehrt', 'value' => 'reverse']]],
                    ['type' => 'CheckBox', 'name' => 'AutoBrightness', 'caption' => 'Helligkeit automatisch über den Lichtsensor'],
                    ['type' => 'NumberSpinner', 'name' => 'Brightness', 'caption' => 'Manuelle Helligkeit (0–255, nur bei ausgeschalteter Automatik)', 'minimum' => 0, 'maximum' => 255]
                ]],
                ['type' => 'ExpansionPanel', 'caption' => 'Töne und Lautstärke', 'items' => [
                    ['type' => 'Label', 'caption' => 'Melodien in der Audio-Seite der Uhr anlegen, dann hier laden. Auswahl und Lautstärke zuerst übernehmen. Der Ton wird nur beim Probehören oder einer ausdrücklich mit Ton gesendeten Nachricht abgespielt.'],
                    ['type' => 'Select', 'name' => 'Sound', 'caption' => 'Melodie für Test und Nachrichten mit Ton', 'options' => $soundOptions],
                    ['type' => 'CheckBox', 'name' => 'ManageAudio', 'caption' => 'Lautstärke über Symcon steuern'],
                    ['type' => 'NumberSpinner', 'name' => 'Volume', 'caption' => 'Gesamtlautstärke (0–100, 0 = stumm)', 'minimum' => 0, 'maximum' => 100],
                    ['type' => 'Label', 'caption' => 'Die Gesamtlautstärke gilt für die gesamte Uhr. Der zusätzliche Alerts-Regler in AWTRIX beeinflusst die hörbare Lautstärke ebenfalls.']
                ]]
            ],
            'actions' => [
                ['type' => 'Button', 'caption' => 'Verbindung testen', 'onClick' => 'echo AWNG_TestConnection($id);'],
                ['type' => 'Button', 'caption' => 'Übergänge von Uhr laden', 'onClick' => 'echo AWNG_LoadTransitions($id);'],
                ['type' => 'Button', 'caption' => 'Icon-Galerie öffnen (TC001)', 'link' => true, 'onClick' => 'echo "https://awtrix.de/icons?device=esp32&panel=32x8&caps=audio.rtttl,sensors.light";'],
                ['type' => 'Button', 'caption' => 'Icons auf der Uhr verwalten', 'link' => true, 'onClick' => 'echo AWNG_GetIconsURL($id);'],
                ['type' => 'Label', 'caption' => 'In der Galerie anmelden, Icon öffnen und mit Send to AWTRIX auf die Uhr übertragen. Danach hier Icons von Uhr laden und in der Anzeigenzeile auswählen. Änderungen vorher übernehmen.'],
                ['type' => 'Button', 'caption' => 'Icons von Uhr laden', 'onClick' => 'echo AWNG_LoadIcons($id);'],
                ['type' => 'Button', 'caption' => 'Melodien von Uhr laden', 'onClick' => 'echo AWNG_LoadSounds($id);'],
                ['type' => 'Button', 'caption' => 'Ton probehören', 'onClick' => 'AWNG_PlaySound($id);'],
                ['type' => 'Button', 'caption' => 'Ton stoppen', 'onClick' => 'AWNG_StopSound($id);'],
                ['type' => 'Button', 'caption' => 'Testnachricht mit Ton', 'onClick' => 'AWNG_NotifyWithSound($id, "Hallo von Symcon!", "", 5);'],
                ['type' => 'Button', 'caption' => 'Testnachricht', 'onClick' => 'AWNG_Notify($id, "Hallo von Symcon!", "", 5);'],
                ['type' => 'Button', 'caption' => 'Anzeigen jetzt senden', 'onClick' => 'AWNG_UpdateDisplays($id, true);'],
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

    public function LoadTransitions(): string
    {
        try {
            $capabilities = $this->Capabilities();
            $this->Success();
            $this->ReloadForm();
            return count($capabilities['transitions']) . ' Übergänge geladen.';
        } catch (Throwable $e) {
            $this->Failure($e);
            return $e->getMessage();
        }
    }

    private function Capabilities(): array
    {
        $result = json_decode($this->Request('GET', '/api/v1/capabilities'), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($result) || empty($result['transitions']) || !is_array($result['transitions'])) {
            throw new RuntimeException('Die Uhr liefert keine gültige Übergangsliste.');
        }
        foreach ($result['transitions'] as $name) {
            if (!is_string($name) || $name === '') {
                throw new RuntimeException('Ungültiger Übergangsname von der Uhr.');
            }
        }
        $all = json_decode($this->ReadAttributeString('Transitions'), true) ?: [];
        $all[$this->BaseURL()] = array_values(array_unique($result['transitions']));
        $this->WriteAttributeString('Transitions', json_encode($all, JSON_THROW_ON_ERROR));
        return $result;
    }

    private function SyncAppearance(): void
    {
        if (!$this->ReadPropertyBoolean('ManageAppearance')) {
            return;
        }
        $duration = $this->ReadPropertyInteger('TransitionDurationMs');
        $brightness = $this->ReadPropertyInteger('Brightness');
        $direction = $this->ReadPropertyString('TransitionDirection');
        if ($duration < 0 || $duration > 10000 || $brightness < 0 || $brightness > 255 || !in_array($direction, ['normal', 'reverse'], true)) {
            throw new InvalidArgumentException('Übergangsdauer, Bewegungsrichtung oder Helligkeit ist ungültig.');
        }
        $capabilities = $this->Capabilities();
        $effect = $this->ReadPropertyString('TransitionEffect');
        if (!in_array($effect, $capabilities['transitions'], true)) {
            throw new InvalidArgumentException('Übergang wird von dieser Uhr nicht unterstützt. Übergänge neu laden und auswählen.');
        }
        $automatic = $this->ReadPropertyBoolean('AutoBrightness');
        if ($automatic && empty($capabilities['sensors']['light'])) {
            throw new InvalidArgumentException('Diese Uhr meldet keinen Lichtsensor. Manuelle Helligkeit auswählen.');
        }
        $wanted = [
            'autoTransition' => $this->ReadPropertyBoolean('AutoTransition'),
            'transitionEffect' => $effect,
            'transitionDurationMs' => $duration,
            'transitionDirection' => $direction,
            'autoBrightness' => $automatic
        ];
        if (!$automatic) {
            $wanted['brightness'] = $brightness;
        }
        $current = json_decode($this->Request('GET', '/api/v1/settings'), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($current)) {
            throw new RuntimeException('Ungültige Einstellungen von der Uhr.');
        }
        $changes = [];
        foreach ($wanted as $key => $value) {
            if (!array_key_exists($key, $current) || $current[$key] !== $value) {
                $changes[$key] = $value;
            }
        }
        if ($changes !== []) {
            $this->Request('PATCH', '/api/v1/settings', $changes);
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

    public function UpdateDisplays(bool $SyncOrder = false): bool
    {
        if (!IPS_SemaphoreEnter('AWNG_' . $this->InstanceID, 100)) {
            return false;
        }
        try {
            $base = $this->BaseURL();
            $rows = $this->Rows();
            $rules = $this->IconRules();
            if ($SyncOrder) {
                $this->SyncAudio();
                $this->SyncAppearance();
            }
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
                if ($row['Type'] === 'text') {
                    $value = $row['Text'];
                } elseif ($row['Type'] === 'countdown') {
                    $value = $this->RemainingDays($row['EndDate']);
                } else {
                    $value = GetValue((int) $row['VariableID']);
                }
                if (is_bool($value)) {
                    $text = $value ? 'Ein' : 'Aus';
                } elseif (is_int($value) || is_float($value)) {
                    $text = number_format((float) $value, ($row['Type'] === 'countdown' ? 0 : (int) $row['Decimals']), ',', '');
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
                if ($row['Type'] === 'text') {
                    $payload['scroll'] = ['mode' => 'loop', 'whenFits' => 'scroll', 'speed' => (int) $row['ScrollSpeed']];
                }
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
            if ($SyncOrder) {
                $this->SyncDisplayOrder($wanted);
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

    public function Notify(string $Text, string $Icon = '', int $Duration = 5, string $Sound = ''): bool
    {
        try {
            $payload = ['text' => $Text, 'durationMs' => max(1, min(300, $Duration)) * 1000];
            if ($Icon !== '') {
                $payload['icon'] = $Icon;
            }
            if ($Sound !== '') {
                $this->ValidateSound($Sound);
                $payload['sound'] = ['file' => $Sound];
            }
            $this->Request('POST', '/api/v1/notifications', $payload);
            $this->Success();
            return true;
        } catch (Throwable $e) {
            $this->Failure($e);
            return false;
        }
    }

    private function RemainingDays(string $endDate): int
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $endDate);
        if (!$date || $date->format('Y-m-d') !== $endDate) {
            throw new InvalidArgumentException('Enddatum als JJJJ-MM-TT eingeben, zum Beispiel 2026-12-24.');
        }
        // Calendar days in the Symcon server timezone, including DST changes.
        $today = new DateTimeImmutable('today');
        return max(0, (int) $today->diff($date)->format('%r%a'));
    }

    public function LoadSounds(): string
    {
        try {
            $capabilities = json_decode($this->Request('GET', '/api/v1/capabilities'), true, 512, JSON_THROW_ON_ERROR);
            if (empty($capabilities['audio']['rtttl'])) {
                throw new RuntimeException('Diese Uhr meldet keine Unterstützung für Buzzer-Melodien.');
            }
            $result = json_decode($this->Request('GET', '/api/v1/audio/melodies'), true, 512, JSON_THROW_ON_ERROR);
            if (!isset($result['melodies']) || !is_array($result['melodies'])) {
                throw new RuntimeException('Ungültige Melodienliste von der Uhr.');
            }
            $names = [];
            foreach ($result['melodies'] as $melody) {
                if (($melody['valid'] ?? false) === true) {
                    $name = (string) ($melody['name'] ?? '');
                    $this->ValidateSound($name);
                    $names[] = $name;
                }
            }
            $names = array_values(array_unique($names));
            sort($names, SORT_NATURAL);
            $all = json_decode($this->ReadAttributeString('Melodies'), true) ?: [];
            $all[$this->BaseURL()] = $names;
            $this->WriteAttributeString('Melodies', json_encode($all, JSON_THROW_ON_ERROR));
            $this->Success();
            $this->ReloadForm();
            return count($names) . ' Melodien geladen.';
        } catch (Throwable $e) {
            $this->Failure($e);
            return $e->getMessage();
        }
    }

    private function ValidateSound(string $name): void
    {
        if (!preg_match('/^[A-Za-z0-9_-]{1,32}$/D', $name)) {
            throw new InvalidArgumentException('Melodiennamen ohne Dateiendung verwenden (1–32 Buchstaben, Ziffern, _ oder -).');
        }
    }

    public function PlaySound(string $Sound = ''): bool
    {
        try {
            $name = $Sound !== '' ? $Sound : $this->ReadPropertyString('Sound');
            $this->ValidateSound($name);
            $this->Request('POST', '/api/v1/audio/play', ['file' => $name]);
            $this->Success();
            return true;
        } catch (Throwable $e) {
            $this->Failure($e);
            return false;
        }
    }

    public function StopSound(): bool
    {
        try {
            $this->Request('POST', '/api/v1/audio/stop', ['group' => 'alert']);
            $this->Success();
            return true;
        } catch (Throwable $e) {
            $this->Failure($e);
            return false;
        }
    }

    public function NotifyWithSound(string $Text, string $Icon = '', int $Duration = 5): bool
    {
        return $this->Notify($Text, $Icon, $Duration, $this->ReadPropertyString('Sound'));
    }

    private function SyncAudio(): void
    {
        if (!$this->ReadPropertyBoolean('ManageAudio')) {
            return;
        }
        $volume = $this->ReadPropertyInteger('Volume');
        if ($volume < 0 || $volume > 100) {
            throw new InvalidArgumentException('Lautstärke muss zwischen 0 und 100 liegen.');
        }
        $settings = json_decode($this->Request('GET', '/api/v1/settings'), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($settings)) {
            throw new RuntimeException('Ungültige Einstellungen von der Uhr.');
        }
        if (($settings['volume'] ?? null) !== $volume) {
            $this->Request('PATCH', '/api/v1/settings', ['volume' => $volume]);
        }
    }

    private function SyncDisplayOrder(array $wanted): void
    {
        $apps = json_decode($this->Request('GET', '/api/v1/apps'), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($apps)) {
            throw new RuntimeException('Ungültige App-Liste für die Reihenfolge.');
        }
        $prefix = 'ips_' . $this->InstanceID . '_';
        $order = [];
        $current = [];
        $disabled = [];
        $inserted = false;
        foreach ($apps as $app) {
            $name = $app['name'] ?? '';
            if (!is_string($name) || $name === '' || ($app['origin'] ?? '') === 'module') {
                continue;
            }
            $current[] = $name;
            if (($app['enabled'] ?? true) === false) {
                $disabled[] = $name;
            }
            if (strpos($name, $prefix) === 0) {
                if (!$inserted) {
                    $order = array_merge($order, $wanted);
                    $inserted = true;
                }
                continue;
            }
            $order[] = $name;
        }
        if (!$inserted) {
            $order = array_merge($order, $wanted);
        }
        if ($order !== $current) {
            // The API requires the complete disabled list; preserve all switches.
            $this->Request('PUT', '/api/v1/apps/order', ['order' => $order, 'disabled' => $disabled]);
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
            $row += ['Enabled' => true, 'Key' => '', 'VariableID' => 0, 'Prefix' => '', 'Suffix' => '', 'Decimals' => 1, 'Icon' => '', 'Duration' => 5, 'Color' => 16777215, 'Type' => 'variable', 'Text' => '', 'EndDate' => '', 'ScrollSpeed' => 100];
            if (!$row['Enabled']) {
                continue;
            }
            if (!preg_match('/^[A-Za-z0-9_-]{1,16}$/D', $row['Key']) || isset($keys[$row['Key']])) {
                throw new InvalidArgumentException('Jede aktive Anzeige benötigt eine eindeutige Kennung (1–16 Zeichen).');
            }
            $keys[$row['Key']] = true;
            if (!in_array($row['Type'], ['variable', 'text', 'countdown'], true)) {
                throw new InvalidArgumentException('Ungültiger Inhaltstyp: ' . $row['Key']);
            }
            if ($row['Type'] === 'text' && (!is_string($row['Text']) || trim($row['Text']) === '' || $row['ScrollSpeed'] < 1 || $row['ScrollSpeed'] > 200)) {
                throw new InvalidArgumentException('Laufschrift benötigt Text und ein Tempo von 1 bis 200 Prozent: ' . $row['Key']);
            }
            if ($row['Type'] === 'countdown') {
                $this->RemainingDays($row['EndDate']);
            }
            if ($row['Type'] === 'variable' && !IPS_VariableExists((int) $row['VariableID'])) {
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
