<?php
// Run with php -n: isolated Symcon and HTTP doubles, no device is contacted.
declare(strict_types=1);
define('VM_UPDATE', 10603);
foreach (['CURLOPT_RETURNTRANSFER', 'CURLOPT_CUSTOMREQUEST', 'CURLOPT_CONNECTTIMEOUT', 'CURLOPT_TIMEOUT', 'CURLOPT_FOLLOWLOCATION', 'CURLOPT_HTTPHEADER', 'CURLOPT_HTTPAUTH', 'CURLAUTH_BASIC', 'CURLOPT_USERPWD', 'CURLOPT_POSTFIELDS', 'CURLINFO_HTTP_CODE'] as $i => $constant) {
    define($constant, $i + 1);
}
$requests = [];
$values = [22180 => 18.4];
$status = 200;
$apps = [];
$settings = [];
$capabilities = ['transitions' => ['Fade', 'Rain'], 'sensors' => ['light' => true], 'audio' => ['rtttl' => true]];
function curl_init($url) { return (object) ['url' => $url, 'options' => []]; }
function curl_setopt_array($handle, $options) { $handle->options = $options; }
function curl_setopt($handle, $key, $value) { $handle->options[$key] = $value; }
function curl_exec($handle) {
    global $requests, $apps, $settings, $capabilities;
    $requests[] = [$handle->options[CURLOPT_CUSTOMREQUEST], $handle->url, isset($handle->options[CURLOPT_POSTFIELDS]) ? json_decode($handle->options[CURLOPT_POSTFIELDS], true) : null];
    if (strpos($handle->url, '/files?') !== false) {
        return '{"files":[{"name":"sun.gif","size":42},{"name":"temp.jpg","size":20}]}';
    }
    if (substr($handle->url, -12) === '/api/v1/apps') {
        return json_encode($apps);
    }
    if (strpos($handle->url, '/api/v1/audio/melodies') !== false) {
        return '{"melodies":[{"name":"bell","valid":true},{"name":"broken","valid":false}]}';
    }
    if (substr($handle->url, -20) === '/api/v1/capabilities') {
        return json_encode($capabilities);
    }
    if (substr($handle->url, -16) === '/api/v1/settings') {
        if ($handle->options[CURLOPT_CUSTOMREQUEST] === 'PATCH') {
            $settings = array_merge($settings, end($requests)[2]);
        }
        return json_encode($settings);
    }
    return '{"ok":true}';
}
function curl_getinfo($handle, $key) { global $status; return $status; }
function curl_close($handle) {}
function curl_error($handle) { return 'test'; }
function IPS_SemaphoreEnter($key, $timeout) { return true; }
function IPS_SemaphoreLeave($key) {}
function IPS_VariableExists($id) { global $values; return array_key_exists($id, $values); }
function IPS_GetName($id) { return 'Testvariable'; }
function GetValue($id) { global $values; return $values[$id]; }
class IPSModule {
    public $InstanceID = 12345;
    public $properties = [];
    public $attributes = [];
    public $variables = [];
    public $status = 0;
    public $timers = [];
    public $formUpdates = [];
    public function Create() {}
    public function ApplyChanges() {}
    public function Destroy() {}
    protected function RegisterPropertyString($name, $value) { $this->properties[$name] = $value; }
    protected function RegisterPropertyInteger($name, $value) { $this->properties[$name] = $value; }
    protected function RegisterPropertyBoolean($name, $value) { $this->properties[$name] = $value; }
    protected function RegisterAttributeString($name, $value) { $this->attributes[$name] = $value; }
    protected function RegisterTimer($name, $value, $script) { $this->timers[$name] = $script; }
    protected function RegisterVariableString($ident, $name, $profile, $position) {}
    protected function ReadPropertyString($name) { return $this->properties[$name]; }
    protected function ReadPropertyInteger($name) { return $this->properties[$name]; }
    protected function ReadPropertyBoolean($name) { return $this->properties[$name]; }
    protected function ReadAttributeString($name) { return $this->attributes[$name]; }
    protected function WriteAttributeString($name, $value) { $this->attributes[$name] = $value; }
    protected function SetValue($name, $value) { $this->variables[$name] = $value; }
    protected function SetStatus($value) { $this->status = $value; }
    protected function ReloadForm() {}
    protected function UpdateFormField($name, $field, $value) { $this->formUpdates[$name][$field] = $value; }
    protected function GetMessageList() { return []; }
    protected function RegisterMessage($id, $message) {}
    protected function UnregisterMessage($id, $message) {}
    protected function SetTimerInterval($name, $interval) {}
}
require __DIR__ . '/../AWTRIXNG/module.php';
function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}
$module = new AWTRIXNG();
$module->Create();
$module->properties['Host'] = '192.168.98.50';
check($module->GetIconsURL() === 'http://192.168.98.50/#/icons', 'Icon management URL');
$row = ['Enabled' => true, 'Key' => 'aussen', 'VariableID' => 22180, 'Suffix' => ' °C'];
$module->properties['Displays'] = json_encode([$row]);
check($module->UpdateDisplays(), 'Initial push failed');
check($requests[0][0] === 'PUT', 'Expected PUT');
check($requests[0][2]['text'] === '18,4 °C', 'Number formatting');
check($requests[0][2]['durationMs'] === 5000, 'Duration conversion');
check($requests[0][2]['lifetimeMs'] === 180000, 'Stale value handling');
check(strpos($requests[0][1], '/ips_12345_aussen') !== false, 'App ownership');
$module->properties['Displays'] = '[]';
check($module->UpdateDisplays(), 'Cleanup failed');
check($requests[1][0] === 'DELETE', 'Removed row must delete own app');
$module->properties['Displays'] = json_encode([$row, $row]);
$before = count($requests);
check(!$module->UpdateDisplays(), 'Duplicate names must fail');
check(count($requests) === $before, 'Invalid configuration must not send');
$module->properties['Displays'] = json_encode([$row]);
check($module->UpdateDisplays(), 'Recreate failed');
$module->properties['Host'] = '192.168.98.51';
$module->properties['Displays'] = '[]';
$before = count($requests);
check($module->UpdateDisplays() && count($requests) === $before, 'Host change must not delete on new clock');
$status = 401;
check(!$module->Notify('Test'), 'Authentication failure must propagate');
check($module->status === 201, 'Error status missing');
$status = 200;
check($module->LoadIcons() === '2 Icons geladen.', 'Icon loading');
$module->properties['Displays'] = json_encode([$row]);
$form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
check(count(array_column($form['elements'][5]['columns'], null, 'name')['Icon']['edit']['options']) === 3, 'Icon selection');
$keyPattern = '/' . $form['elements'][5]['columns'][1]['edit']['validate'] . '/D';
foreach (['', ' ', 'mit leerzeichen', 'ä', str_repeat('a', 17)] as $invalidKey) {
    check(preg_match($keyPattern, $invalidKey) === 0, 'Invalid key must be rejected by form');
}
check(preg_match($keyPattern, 'pv_akku-1') === 1, 'Valid key must be accepted by form');
$ruleSelect = $form['elements'][6]['items'][1]['columns'][0]['edit'];
check($ruleSelect['type'] === 'Select', 'Icon rule display must be a dropdown');
check($ruleSelect['options'] === [['caption' => 'aussen', 'value' => 'aussen']], 'Dropdown must list saved displays');
$module->properties['IconRules'] = '[{"Key":"removed","Maximum":0,"Icon":""}]';
$orphanForm = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
check($orphanForm['elements'][6]['items'][1]['columns'][0]['edit']['options'][1]['caption'] === 'removed (Anzeige fehlt)', 'Preserve missing display references');
$module->properties['IconRules'] = '[]';
$module->properties['Displays'] = '[]';
$module->properties['Host'] = '192.168.98.51/path';
check(!$module->Notify('Test'), 'Host paths must be rejected');
$module->properties['Host'] = '192.168.98.51';
$module->properties['ManageBuiltins'] = true;
$apps = [
    ['name' => 'Time', 'origin' => 'builtin', 'enabled' => true],
    ['name' => 'Date', 'origin' => 'builtin', 'enabled' => false],
    ['name' => 'Temperature', 'origin' => 'builtin', 'enabled' => true],
    ['name' => 'Battery', 'origin' => 'script', 'enabled' => true]
];
$before = count($requests);
check($module->UpdateDisplays(), 'Builtin sync failed');
$new = array_slice($requests, $before);
check(count($new) === 3, 'Only mismatched builtins should be changed');
check($new[1][2] === true && strpos($new[1][1], '/Date/enabled') !== false, 'Date should be enabled with JSON true');
check($new[2][2] === false && strpos($new[2][1], '/Temperature/enabled') !== false, 'Temperature should be disabled with JSON false');
$apps[1]['enabled'] = true;
$apps[2]['enabled'] = false;
$before = count($requests);
check($module->UpdateDisplays() && count($requests) === $before + 1, 'Unchanged builtins must not be written again');
$module->properties['ManageBuiltins'] = false;
$before = count($requests);
check($module->UpdateDisplays() && count($requests) === $before, 'Disabled management must leave clock alone');
$module->properties['Displays'] = json_encode([array_merge($row, ['Icon' => 'pv'])]);
$module->properties['IconRules'] = json_encode([
    ['Key' => 'aussen', 'Maximum' => 0, 'Icon' => 'no_pv']
]);
foreach ([[-0.1, 'no_pv'], [0, 'no_pv'], [0.001, 'pv']] as [$value, $expected]) {
    $values[22180] = $value;
    check($module->UpdateDisplays(), 'PV rule update failed');
    check(end($requests)[2]['icon'] === $expected, 'PV zero boundary');
}
$module->properties['Displays'] = json_encode([array_merge($row, ['Icon' => 'akku100'])]);
$module->properties['IconRules'] = json_encode([
    ['Key' => 'aussen', 'Maximum' => 80, 'Icon' => 'akku80'],
    ['Key' => 'aussen', 'Maximum' => 10, 'Icon' => 'akku00'],
    ['Key' => 'aussen', 'Maximum' => 20, 'Icon' => 'akku20'],
    ['Key' => 'aussen', 'Maximum' => 40, 'Icon' => 'akku40'],
    ['Key' => 'aussen', 'Maximum' => 60, 'Icon' => 'akku60']
]);
foreach ([[0, 'akku00'], [10, 'akku00'], [10.1, 'akku20'], [20, 'akku20'], [20.1, 'akku40'], [40, 'akku40'], [40.1, 'akku60'], [60, 'akku60'], [60.1, 'akku80'], [80, 'akku80'], [80.1, 'akku100'], [100, 'akku100'], ['offline', 'akku100']] as [$value, $expected]) {
    $values[22180] = $value;
    check($module->UpdateDisplays(), 'Battery rule update failed');
    check(end($requests)[2]['icon'] === $expected, 'Battery boundary ' . $value);
}
$module->properties['IconRules'] = '[{"Key":"aussen","Maximum":10,"Icon":"one"},{"Key":"aussen","Maximum":10,"Icon":"two"}]';
$before = count($requests);
check(!$module->UpdateDisplays() && count($requests) === $before, 'Duplicate thresholds must fail before sending');
$module->properties['IconRules'] = '[]';
$module->properties['Displays'] = json_encode([
    array_merge($row, ['Key' => 'second']), array_merge($row, ['Key' => 'first'])
]);
$apps = [
    ['name' => 'Time', 'origin' => 'builtin', 'enabled' => true],
    ['name' => 'ips_12345_first', 'origin' => 'pushed', 'enabled' => true],
    ['name' => 'external', 'origin' => 'script', 'enabled' => false],
    ['name' => 'ips_12345_second', 'origin' => 'pushed', 'enabled' => true]
];
check($module->UpdateDisplays(true), 'Reordering failed');
$orderRequest = end($requests);
check($orderRequest[2]['order'] === ['Time', 'ips_12345_second', 'ips_12345_first', 'external'], 'Order must follow table and preserve external apps');
check($orderRequest[2]['disabled'] === ['external'], 'External disabled state must be preserved');
$module->properties['Displays'] = '[]';
$module->attributes['Managed'] = '{}';
$apps = [];
check($module->LoadTransitions() === '2 Übergänge geladen.', 'Load device transition choices');
$module->properties['ManageAppearance'] = true;
$before = count($requests);
check($module->UpdateDisplays(true), 'Appearance sync failed');
$patches = array_values(array_filter(array_slice($requests, $before), static function ($r) { return $r[0] === 'PATCH'; }));
check(count($patches) === 1 && $patches[0][2]['transitionDurationMs'] === 500, 'Milliseconds and settings patch');
check(!isset($patches[0][2]['brightness']), 'Automatic mode must preserve manual brightness');
$before = count($requests);
check($module->UpdateDisplays(true), 'Unchanged settings sync failed');
check(count(array_filter(array_slice($requests, $before), static function ($r) { return $r[0] === 'PATCH'; })) === 0, 'Unchanged settings must not be rewritten');
$before = count($requests);
check($module->UpdateDisplays() && count($requests) === $before, 'Timer must not read or write appearance settings');
$module->properties['AutoBrightness'] = false;
$module->properties['Brightness'] = 42;
$module->properties['TransitionDurationMs'] = 0;
check($module->UpdateDisplays(true) && $settings['brightness'] === 42 && $settings['transitionDurationMs'] === 0, 'Manual brightness and instant transition');
$module->properties['AutoBrightness'] = true;
$capabilities['sensors']['light'] = false;
check(!$module->UpdateDisplays(true), 'Missing light sensor must reject automatic mode');
$capabilities['sensors']['light'] = true;
$module->properties['TransitionEffect'] = 'Unsupported';
check(!$module->UpdateDisplays(true), 'Unsupported transition must fail');
$module->properties['TransitionEffect'] = 'Fade';
$module->properties['Brightness'] = 256;
$before = count($requests);
check(!$module->UpdateDisplays(true) && count($requests) === $before, 'Invalid settings must fail before requests');
$module->properties['ManageAppearance'] = false;
$before = count($requests);
check($module->UpdateDisplays(true), 'Disabled appearance management');
check(count($requests) === $before + 1, 'Disabled management must only read app order');
// New content types need no variable and preserve the existing app ownership.
$module->properties['Displays'] = json_encode([['Key' => 'message', 'Type' => 'text', 'Text' => 'Hallo aus Symcon', 'ScrollSpeed' => 80]]);
check($module->UpdateDisplays(), 'Free text without variable');
check(end($requests)[2]['text'] === 'Hallo aus Symcon', 'Free text content');
check(end($requests)[2]['scroll'] === ['mode' => 'loop', 'whenFits' => 'scroll', 'speed' => 80], 'Forced continuous scrolling');
$module->attributes['Managed'] = '{}';
foreach (['yesterday' => 0, 'today' => 0, 'tomorrow' => 1, '+10 days' => 10] as $date => $days) {
    $module->properties['Displays'] = json_encode([['Key' => 'count', 'Type' => 'countdown', 'EndDate' => (new DateTimeImmutable($date))->format('Y-m-d'), 'Suffix' => ' Tage']]);
    check($module->UpdateDisplays(), 'Countdown update');
    check(end($requests)[2]['text'] === $days . ' Tage', 'Countdown calendar days and integer formatting');
}
foreach (['2027-02-29', '2026-02-30', '24.12.2026', ''] as $invalidDate) {
    $module->properties['Displays'] = json_encode([['Key' => 'count', 'Type' => 'countdown', 'EndDate' => $invalidDate]]);
    $before = count($requests);
    check(!$module->UpdateDisplays() && count($requests) === $before, 'Invalid date must fail before sending');
}
$previousTimezone = date_default_timezone_get();
date_default_timezone_set('Europe/Vienna');
$target = new DateTimeImmutable('2027-04-01');
$expectedDays = max(0, (int) (new DateTimeImmutable('today'))->diff($target)->format('%r%a'));
$module->properties['Displays'] = json_encode([['Key' => 'count', 'Type' => 'countdown', 'EndDate' => '2027-04-01']]);
check($module->UpdateDisplays() && end($requests)[2]['text'] === (string) $expectedDays, 'Calendar calculation across DST');
date_default_timezone_set($previousTimezone);
$module->properties['Displays'] = '[]';
$module->attributes['Managed'] = '{}';
check($module->LoadSounds() === '1 Melodien geladen.', 'Only valid melodies are loaded');
$module->properties['Sound'] = 'bell';
check($module->PlaySound(), 'Play configured sound');
check(end($requests)[2] === ['file' => 'bell'], 'Stored sound payload');
check($module->NotifyWithSound('Fertig', '', 10), 'Notification with configured sound');
check(end($requests)[2]['sound'] === ['file' => 'bell'], 'Notification sound payload');
check($module->Notify('Leise'), 'Existing notification API remains silent');
check(!isset(end($requests)[2]['sound']), 'No implicit sound in existing notifications');
check($module->NotifyWithMelody('Andere Melodie', '', 5, 'other'), 'Explicit notification melody');
check(end($requests)[2]['sound']['file'] === 'other', 'Explicit melody payload');
check($module->StopSound() && end($requests)[2] === ['group' => 'alert'], 'Stop alerts only');
$before = count($requests);
check(!$module->PlaySound('../bad') && count($requests) === $before, 'Invalid sound name rejected');
$module->properties['ManageAudio'] = true;
$module->properties['Volume'] = 0;
check($module->UpdateDisplays(true) && $settings['volume'] === 0, 'Mute master volume');
$before = count($requests);
check($module->UpdateDisplays(true), 'Unchanged audio sync');
check(count(array_filter(array_slice($requests, $before), static function ($r) { return $r[0] === 'PATCH'; })) === 0, 'No repeated volume writes');
$before = count($requests);
check($module->UpdateDisplays() && count($requests) === $before, 'Timer must not play sounds or change volume');
$module->properties['Volume'] = 101;
$before = count($requests);
check(!$module->UpdateDisplays(true) && count($requests) === $before, 'Invalid volume rejected');
$capabilities['audio']['rtttl'] = false;
check(strpos($module->LoadSounds(), 'keine Unterstützung') !== false, 'Unsupported sound hardware reported');
// Symcon generates global functions without PHP parameter defaults.
foreach (['UpdateDisplays', 'Notify', 'NotifyWithMelody', 'NotifyWithSound', 'PlaySound'] as $method) {
    $args = array_map(static function ($parameter) { return '$' . $parameter->getName(); }, (new ReflectionMethod(AWTRIXNG::class, $method))->getParameters());
    eval('function AWNG_' . $method . '($id, ' . implode(', ', $args) . ') { global $module; return $module->' . $method . '(' . implode(', ', $args) . '); }');
}
$module->properties['ManageAudio'] = false;
$module->properties['Displays'] = '[]';
$module->attributes['Managed'] = '{}';
$_IPS = ['TARGET' => $module->InstanceID];
eval($module->timers['Refresh']);
$form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$id = $module->InstanceID;
function flattenActions(array $items): array {
    $result = [];
    foreach ($items as $item) {
        $result[] = $item;
        $result = array_merge($result, flattenActions($item['items'] ?? []));
    }
    return $result;
}
foreach (flattenActions($form['actions']) as $action) {
    if (in_array($action['caption'] ?? '', ['Ton probehören', 'Testnachricht', 'Testnachricht mit Ton', 'Anzeigen jetzt senden'], true)) {
        eval($action['onClick']);
    }
}
check((new ReflectionFunction('AWNG_Notify'))->getNumberOfRequiredParameters() === 4, 'Existing notification global signature must stay compatible');
foreach (['variable', 'text', 'countdown'] as $type) {
    $dialog = json_decode($module->UIDisplayForm($type), true, 512, JSON_THROW_ON_ERROR);
    check(array_column(array_slice($dialog, 0, 3), 'name') === ['Enabled', 'Key', 'Type'], 'Content selector immediately below key');
    $fields = array_column($dialog, null, 'name');
    check($fields['EndDate']['type'] === 'SelectDate', 'Countdown calendar picker');
    check($fields['VariableID']['visible'] === ($type === 'variable'), 'Variable field visibility');
    check($fields['Text']['visible'] === ($type === 'text'), 'Text field visibility');
    check($fields['EndDate']['visible'] === ($type === 'countdown'), 'Calendar field visibility');
    $module->UIDisplayTypeChanged($type);
    check($module->formUpdates['EndDate']['visible'] === ($type === 'countdown'), 'Changing content updates current dialog');
}
$tomorrow = new DateTimeImmutable('tomorrow');
$calendar = ['year' => (int) $tomorrow->format('Y'), 'month' => (int) $tomorrow->format('m'), 'day' => (int) $tomorrow->format('d')];
foreach ([$calendar, json_encode($calendar)] as $dateValue) {
    $module->properties['Displays'] = json_encode([['Key' => 'calendar', 'Type' => 'countdown', 'EndDate' => $dateValue]]);
    check($module->UpdateDisplays() && end($requests)[2]['text'] === '1', 'Calendar date countdown');
}
$module->properties['Displays'] = json_encode([['Key' => 'legacy', 'Type' => 'countdown', 'EndDate' => '2026-12-24', 'Icon' => 'sun']]);
$form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$converted = $form['elements'][5]['values'][0];
check(json_decode($converted['EndDate'], true) === ['year' => 2026, 'month' => 12, 'day' => 24], 'Existing ISO date migrates to calendar value');
check($converted['Icon'] === 'sun', 'Migration preserves other settings');
$module->properties['Displays'] = json_encode([['Key' => 'calendar', 'Type' => 'countdown', 'EndDate' => '{"year":0,"month":0,"day":0}']]);
check(!$module->UpdateDisplays(), 'Empty calendar date must be rejected');
// Check the distributed tree, ignoring local empty/ignored directories.
$root = dirname(__DIR__);
$files = [];
exec('git -C ' . escapeshellarg($root) . ' ls-files --cached --others --exclude-standard', $files, $gitResult);
check($gitResult === 0, 'Repository structure inventory failed');
$ignoredFolders = ['libs', 'docs', 'imgs', 'tests', 'actions'];
foreach ($files as $file) {
    $parts = explode('/', $file);
    if (count($parts) < 2 || $parts[0][0] === '.' || in_array($parts[0], $ignoredFolders, true) || !is_file($root . '/' . $file)) {
        continue;
    }
    check(is_file($root . '/' . $parts[0] . '/module.json'), 'Invalid library folder: ' . $parts[0]);
}
$module->properties['Displays'] = '[]';
$module->properties['ManageAudio'] = false;
$module->properties['ManageAppearance'] = false;
$module->ApplyChanges();
$module->Destroy();
$before = count($requests);
$module->MessageSink(time(), 22180, VM_UPDATE, [42, true]);
check(!$module->UpdateDisplays(), 'Unloaded instance must reject timer updates');
check(count($requests) === $before, 'Unloaded instance must not process queued value changes');
echo "All module tests passed.\n";
