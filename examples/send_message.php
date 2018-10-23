<?php

require_once(dirname(__FILE__) . '/../vendor/autoload.php');

$chatkit = new Chatkit\Chatkit([
  'instance_locator' => 'your:instance:locator',
  'key' => 'your:key'
]);

print_r($chatkit->sendMessage([
  'sender_id' => 'ham',
  'room_id' => '123456',
  'text' => 'Vivan is the best'
]));
