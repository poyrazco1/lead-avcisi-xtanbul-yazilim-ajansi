<?php
require __DIR__ . '/app/core.php';
logout_user();
header('Location: login.php');
exit;
