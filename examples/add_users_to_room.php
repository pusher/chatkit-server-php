<?php

require_once(dirname(__FILE__) . '/../vendor/autoload.php');

$chatkit = new Chatkit\Chatkit([
  'instance_locator' => 'your:instance:locator',
  'key' => 'your:key'
]);

print_r($chatkit->addUsersToRoom([
  'room_id' => '123',
  'user_ids' => ['ham', 'another']
]));
