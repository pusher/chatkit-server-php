<?php

require_once(dirname(__FILE__) . '/../vendor/autoload.php');

$chatkit = new Chatkit\Chatkit([
  'instance_locator' => 'your:instance:locator',
  'key' => 'your:key'
]);

$auth_data = $chatkit->authenticate([ 'user_id' => 'ham' ]);

print_r($auth_data);

print($auth_data['status']);
print(json_encode($auth_data['body']));
