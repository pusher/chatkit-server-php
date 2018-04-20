<?php

require_once(dirname(__FILE__) . '/../vendor/autoload.php');

$chatkit = new Chatkit\Chatkit([
  'instance_locator' => 'your:instance:locator',
  'key' => 'your:key'
]);

print_r($chatkit->createRoom([
  'creator_id' => 'ham',
  'name' => 'A BIG ROOM',
  'private' => false
]));
