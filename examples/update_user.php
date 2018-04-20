<?php

require_once(dirname(__FILE__) . '/../vendor/autoload.php');

$chatkit = new Chatkit\Chatkit([
  'instance_locator' => 'your:instance:locator',
  'key' => 'your:key'
]);

print_r($chatkit->updateUser([
  'id' => 'phptest',
  'name' => 'PHP IS THE BEST',
  'avatar_url' => 'https://placekitten.com/200/300',
  'custom_data' => [
    'a' => 'test'
  ]
]));
