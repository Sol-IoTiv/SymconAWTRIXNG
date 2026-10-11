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
function curl_init($url) { return (object) ['url' => $url, 'options' => []]; }
function curl_setopt_array($handle, $options) { $handle->options = $options; }
function curl_setopt($handle, $key, $value) { $handle->options[$key] = $value; }
function curl_exec($handle) {
    global $requests, $apps;
    $requests[] = [$handle->options[CURLOPT_CUSTOMREQUEST], $handle->url, isset($handle->options[CURLOPT_POSTFIELDS]) ? json_decode($handle->options[CURLOPT_POSTFIELDS], true) : null];
    if (strpos($handle->url, '/files?') !== false) {
        return '{"files":[{"name":"sun.gif","size":42},{"name":"temp.jpg","size":20}]}';
    }
    if (substr($handle->url, -12) === '/api/v1/apps') {
        return json_encode($apps);
    }
    return '{"ok":true}';
}
function curl_getinfo($handle, $key) { global $status; return $status; }
function curl_close($handle) {}
function curl_error($handle) { return 'test'; }
function IPS_SemaphoreEnter($key, $timeout) { return true; }
function IPS_SemaphoreLeave($key) {}
function IPS_VariableExists($id) { global $values; return array_key_exists($id, $values); }
function GetValue($id) { global $values; return $values[$id]; }
class IPSModule {
    public $InstanceID = 12345;
    public $properties = [];
    public $attributes = [];
    public $variables = [];
    public $status = 0;
    public function Create() {}
    public function ApplyChanges() {}
    protected function RegisterPropertyString($name, $value) { $this->properties[$name] = $value; }
    protected function RegisterPropertyInteger($name, $value) { $this->properties[$name] = $value; }
    protected function RegisterPropertyBoolean($name, $value) { $this->properties[$name] = $value; }
    protected function RegisterAttributeString($name, $value) { $this->attributes[$name] = $value; }
    protected function RegisterTimer($name, $value, $script) {}
    protected function RegisterVariableString($ident, $name, $profile, $position) {}
    protected function ReadPropertyString($name) { return $this->properties[$name]; }
    protected function ReadPropertyInteger($name) { return $this->properties[$name]; }
    protected function ReadPropertyBoolean($name) { return $this->properties[$name]; }
    protected function ReadAttributeString($name) { return $this->attributes[$name]; }
    protected function WriteAttributeString($name, $value) { $this->attributes[$name] = $value; }
    protected function SetValue($name, $value) { $this->variables[$name] = $value; }
    protected function SetStatus($value) { $this->status = $value; }
    protected function ReloadForm() {}
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
$form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
check(count($form['elements'][5]['columns'][6]['edit']['options']) === 3, 'Icon selection');
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
echo "All module tests passed.\n";
