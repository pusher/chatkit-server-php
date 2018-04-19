<?php

require_once(dirname(__FILE__) . '/../vendor/autoload.php');

$chatkit = new Chatkit\Chatkit([
  'instance_locator' => 'your:instance:locator',
  'key' => 'your:key'
]);

print_r($chatkit->createUser([
  'id' => 'phptest',
  'name' => 'PHP IS KING',
  'avatar_url' => 'https://placekitten.com/400/500',
  'custom_data' => [
    'a' => 'test'
  ]
]));
