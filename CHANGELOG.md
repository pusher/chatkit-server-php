# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/pusher/chatkit-server-php/compare/0.5.3...HEAD)

## [0.5.3](https://github.com/pusher/chatkit-server-php/compare/0.5.2...0.5.3) - 2018-06-11

### Additions

- `deleteRoom` functionality added [#19](https://github.com/pusher/chatkit-server-php/pull/19) by [@morrislaptop](https://github.com/morrislaptop)
- `getUsers` functionality added [#20](https://github.com/pusher/chatkit-server-php/pull/20) by [@morrislaptop](https://github.com/morrislaptop)

## [0.5.2](https://github.com/pusher/chatkit-server-php/compare/0.5.1...0.5.2) - 2018-06-07

### Fixes

- `updateUser` validates information properly [#18](https://github.com/pusher/chatkit-server-php/pull/18) by [@morrislaptop](https://github.com/morrislaptop)

### Additions

- `sendMessage` supports send messages with attachments [#18](https://github.com/pusher/chatkit-server-php/pull/18) by [@morrislaptop](https://github.com/morrislaptop)

## [0.5.1](https://github.com/pusher/chatkit-server-php/compare/0.5.0...0.5.1) - 2018-05-25

### Changes

- User ID is validated to be a string as part of `createUser`, `authenticate`, and `generateAccessToken` calls

### Fixes

- `getUserRooms` no longer crashes if `joinable` option not set

## [0.5.0](https://github.com/pusher/chatkit-server-php/compare/0.4.0...0.5.0) - 2018-05-11

### Changes

- API calls to Chatkit servers now return an associative array that has the keys `'status'` and `'body'`.

## [0.4.0](https://github.com/pusher/chatkit-server-php/compare/0.3.0...0.4.0) - 2018-04-20

### Additions

- `authenticate` has been added. This should be the function you use to authenticate your users for Chatkit.

You need to provide an associative array that has a `user_id` key with a string value. For example:

```php
$chatkit->authenticate([ 'user_id' => 'ham' ]);
```

It returns an associative array that is structured like this:

```php
[
    'status' => 200,
    'headers' => [
        'Some-Header' => 'some-value'
    ],
    'body' => [
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

### Removals

- `getTokenPair` has been removed

### Changes

- Authentication no longer returns refresh tokens.

If your client devices are running the:

* Swift SDK - (**breaking change**) you must be using version `>= 0.8.0` of [chatkit-swift](https://github.com/pusher/chatkit-swift).
* Android SDK - you won't be affected regardless of which version you are running.
* JS SDK - you won't be affected regardless of which version you are running.

- (Nearly) all functions now take an object (an associative array) as their sole parameter. As examples:

* Constructing a Chatkit object is done like this:

```php
$chatkit = new Chatkit\Chatkit([
    'instance_locator' => 'your:instance:locator',
    'key' => 'your:key'
]);
```

* `createUser` is now called like this:

```php
$chatkit->createUser([
    'id' => 'dr_php',
    'name' => 'Dr PHP',
    'avatar_url' => 'https://placekitten.com/400/500',
    'custom_data' => [
        'a' => 'piece of data'
    ]
]);
```

* `authenticate` (previously `getTokenPair`) is now called like this:

```php
$chatkit->authenticate([
    'user_id' => 'dr_php'
]);
```

* `createRoom` is now called like this:

```php
$chatkit->createRoom([
    'creator_id' => 'dr_php',
    'name' => 'A room with a name',
    'private' => false
]);
```
