<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_response([
        'ok'=>true,
        'package_prices'=>setting_get('package_prices', package_default_prices()),
        'team_members'=>setting_get('team_members', team_members_default()),
        'packages'=>package_catalog(),
        'runtime'=>public_runtime_settings(),
        'payment_settings'=>setting_get('payment_settings', default_payment_settings()),
        'operation_settings'=>setting_get('operation_settings', default_operation_settings()),
        'message_templates'=>message_templates(),
        'template_defaults'=>default_message_templates(),
        'google_key_masked'=>mask_secret(effective_google_api_key()),
    ]);
}
$data = input_json();
if (!csrf_check($data['csrf'] ?? '')) json_response(['ok'=>false,'error'=>'CSRF doğrulaması başarısız.'], 403);
if (isset($data['package_prices']) && is_array($data['package_prices'])) setting_set('package_prices', $data['package_prices']);
if (isset($data['team_members']) && is_array($data['team_members'])) setting_set('team_members', array_values(array_filter(array_map('trim', $data['team_members']))));
if (isset($data['google_api_key']) && trim((string)$data['google_api_key']) !== '') setting_set('google_api_key', trim((string)$data['google_api_key']));
if (isset($data['payment_settings']) && is_array($data['payment_settings'])) setting_set('payment_settings', $data['payment_settings']);
if (isset($data['operation_settings']) && is_array($data['operation_settings'])) setting_set('operation_settings', $data['operation_settings']);
if (isset($data['message_templates']) && is_array($data['message_templates'])) {
    $clean = [];
    foreach (default_message_templates() as $k => $def) {
        if (isset($data['message_templates'][$k])) $clean[$k] = (string)$data['message_templates'][$k];
    }
    setting_set('message_templates', $clean);
}
json_response([
    'ok'=>true,
    'package_prices'=>setting_get('package_prices', package_default_prices()),
    'team_members'=>setting_get('team_members', team_members_default()),
    'runtime'=>public_runtime_settings(),
    'payment_settings'=>setting_get('payment_settings', default_payment_settings()),
    'operation_settings'=>setting_get('operation_settings', default_operation_settings()),
    'message_templates'=>message_templates(),
]);
