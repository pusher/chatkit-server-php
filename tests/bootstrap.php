<?php

$dir = dirname(__FILE__);
$config_path = $dir.'/config.php';
if (file_exists($config_path) === true) {
    require_once $config_path;
} else {
    define('CHATKIT_INSTANCE_LOCATOR', getenv('CHATKIT_INSTANCE_LOCATOR'));
    define('CHATKIT_INSTANCE_KEY', getenv('CHATKIT_INSTANCE_KEY'));
}
