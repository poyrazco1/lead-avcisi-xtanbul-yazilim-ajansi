<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();
$filters = [];
foreach (['q','status','city','district','sector','assigned_to','min_score'] as $k) if (isset($_GET[$k]) && trim((string)$_GET[$k]) !== '') $filters[$k] = trim((string)$_GET[$k]);
json_response(['ok'=>true,'leads'=>list_leads($filters, 1000),'team_members'=>setting_get('team_members', team_members_default())]);
