<?php
define('LEAD_API', true);
require __DIR__ . '/../app/core.php';
require_login();
json_response(['ok'=>true,'stats'=>stats()]);
