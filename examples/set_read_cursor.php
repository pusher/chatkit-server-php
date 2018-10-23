<?php

require_once(dirname(__FILE__) . '/../vendor/autoload.php');

$chatkit = new Chatkit\Chatkit([
  'instance_locator' => 'your:instance:locator',
  'key' => 'your:key'
]);

print_r($chatkit->setReadCursor([
  'user_id' =>  'ham',
  'room_id' =>  '456789',
  'position' =>  123,
]));
