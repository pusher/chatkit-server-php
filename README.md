# Chatkit PHP Server SDK

Find out more about Chatkit [here](https://pusher.com/chatkit).

## Installation

You can get the Chatkit PHP SDK via a composer package called `pusher-chatkit-server`. See <https://packagist.org/packages/pusher/pusher-chatkit-server>

```bash
$ composer require pusher/pusher-chatkit-server
```

Or add to `composer.json`:

```json
"require": {
    "pusher/pusher-chatkit-server": "^0.5.7"
}
```

and then run `composer update`.

Or you can clone or download the library files.

**We recommend you [use composer](http://getcomposer.org/).**

This library depends on PHP modules for cURL and JSON. See [cURL module installation instructions](http://php.net/manual/en/curl.installation.php) and [JSON module installation instructions](http://php.net/manual/en/json.installation.php).


## Chatkit constructor

Head to your dashbord to find your `instance_locator` and `key` and use them to create a new `Chatkit\Chatkit` instance.

```php
$chatkit = new Chatkit\Chatkit([
  'instance_locator' => 'YOUR_INSTANCE_LOCATOR',
  'key' => 'YOUR_KEY'
]);
```

## Authentication

To authenticate a user (a Chatkit client) use the `authenticate` function.

You need to provide an associative array that has a `user_id` key with a string value. For example:

```php
$chatkit->authenticate([ 'user_id' => 'ham' ]);
```

It returns an associative array that is structured like this:

```php
[
    'status': 200,
    'headers': [
        'Some-Header': 'some-value'
    ],
    'body': [
        'access_token' => 'an.access.token',
        'token_type' => 'bearer',
        'expires_in' => 86400
    ]
]
```

where:

* `status` is the suggested HTTP response status code,
* `headers` are the suggested response headers,
* `body` holds the token payload.

## Creating a user

To create a user you must provide an `id` and a `name`. You can optionally provide an `avatar_url (string)` and `custom_data (array)`.

```php
$chatkit->createUser([
  'id' => 'ham',
  'name' => 'Hamilton Chapman'
]);
```

Or with an `avatar_url` and `custom_data`:

```php
$chatkit->createUser([
  'id' => 'ham',
  'name' => 'Hamilton Chapman',
  'avatar_url' => 'https://placekitten.com/200/300',
  'custom_data' => [
    'my_custom_key' => 'some data'
  ]
]);
```

## Updating a user

To update a user you must provide an `id`. You can optionally provide a `name (string)`, an `avatar_url (string)` and `custom_data (array)`. One of the three optional fields must be provided.

```php
$chatkit->updateUser([
  'id' => 'ham',
  'name' => 'Someone Else'
]);
```

Or with an `avatar_url` and `custom_data`:

```php
$chatkit->updateUser([
  'id' => 'ham',
  'name' => 'Someone Else',
  'avatar_url' => 'https://placekitten.com/400/500',
  'custom_data' => [
    'my_custom_key' => 'some other data'
  ]
]);
```

## Send a message

To send a message you must provide a user `id`, a `room_id` and the `text`.

```php
$chatkit->sendMessage([
  'sender_id' => 'sarah',
  'room_id' => 1001,
  'text' => 'This is a wonderful message.'
]);
```

## Create a room

To create a room you must provide the ID of the user that is creating the room, and then an options array that must contain a `name` and can optionally contain a boolean flag `private` that dictates whether or not the room will be private. You can also provide a list of `user_ids` in this options array, all of which will be added as members of the room upon its creation.

```php
$chatkit->createRoom([
  'creator_id' => 'sarah',
  'name' => 'my room',
  'private' => false,
  'user_ids' => ['tom', 'will', 'kate']
]);
```

## Delete a user

To delete a user you need to provide the ID of the user to delete.

```php
$chatkit->deleteUser([ 'id' => 'sarah' ]);
```

## Get information about users by IDs

You can get information about a list of users by providing their IDs to `getUsersByIds`.

```php
$chatkit->getUsersByIds([ 'user_ids' => ['sarah', 'tom'] ]);
```
