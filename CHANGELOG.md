# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased](https://github.com/pusher/chatkit-server-php/compare/1.1.0...HEAD)

## [1.1.0](https://github.com/pusher/chatkit-server-php/compare/1.0.0...1.1.0) - 2018-11-08

### Added

- `custom_data` option that can be passed to `createRoom` and `updateRoom`

## [1.0.0](https://github.com/pusher/chatkit-server-php/compare/0.5.9...1.0.0) - 2018-10-30

### Changed

### Breaking Changes

- `getUsersByIds` is now `getUsersById`
- `getUsers` now takes an array with an optional `from_timestamp` key instead of a `from_ts` key
- `getUserReadCursors` is now `getReadCursorsForUser`

### Added

- The following new methods:
  - `generateSuToken`
  - `createUsers`
  - `getUser`
  - `updateRoom`
  - `deleteRoom`
  - `getRoom`
  - `getRooms`
  - `getUserJoinableRooms`
  - `addUsersToRoom`
  - `removeUsersFromRoom`
  - `createGlobalRole`
  - `createRoomRole`
  - `deleteGlobalRole`
  - `deleteRoomRole`
  - `assignGlobalRoleToUser`
  - `assignRoomRoleToUser`
  - `getRoles`
  - `getUserRoles`
  - `removeGlobalRoleForUser`
  - `removeRoomRoleForUser`
  - `getPermissionsForGlobalRole`
  - `getPermissionsForRoomRole`
  - `updatePermissionsForGlobalRole`
  - `updatePermissionsForRoomRole`
  - `getReadCursor`
  - `getReadCursorsForRoom`
  - `apiRequest`
  - `authorizerRequest`
  - `cursorsRequest`

## [0.5.9](https://github.com/pusher/chatkit-server-php/compare/0.5.8...0.5.9) - 2018-08-29

### Added

- `getRooms` functionality added. [#31](https://github.com/pusher/chatkit-server-php/pull/31) by [@philipnjuguna66](https://github.com/philipnjuguna66)

## [0.5.8](https://github.com/pusher/chatkit-server-php/compare/0.5.7...0.5.8) - 2018-08-21

### Added

- `addUsersToRoom` and `removeUsersFromRoom` functionality added. [#29](https://github.com/pusher/chatkit-server-php/pull/29) by [@mludi](https://github.com/mludi)

## [0.5.7](https://github.com/pusher/chatkit-server-php/compare/0.5.6...0.5.7) - 2018-08-14

### Added

- `joinRoom` functionality added. [#27](https://github.com/pusher/chatkit-server-php/pull/27) by [@mludi](https://github.com/mludi)

## [0.5.6](https://github.com/pusher/chatkit-server-php/compare/0.5.5...0.5.6) - 2018-07-30

### Fixed

- `getUsersByIds` and `getUsers` now properly set query parameters

## [0.5.5](https://github.com/pusher/chatkit-server-php/compare/0.5.4...0.5.5) - 2018-07-30

### Added

- `setReadCursor` functionality added. [#22](https://github.com/pusher/chatkit-server-php/pull/22) by [@morrislaptop](https://github.com/morrislaptop)
- `getUserReadCursors` functionality added. [#22](https://github.com/pusher/chatkit-server-php/pull/22) by [@morrislaptop](https://github.com/morrislaptop)
- `getRoomMessages` now supports providing an `initial_id`, a `limit`, and a `direction`. [#22](https://github.com/pusher/chatkit-server-php/pull/22) by [@morrislaptop](https://github.com/morrislaptop)

## [0.5.4](https://github.com/pusher/chatkit-server-php/compare/0.5.3...0.5.4) - 2018-06-25

### Added

- `getRoomMessages` functionality added [#21](https://github.com/pusher/chatkit-server-php/pull/21) by [@morrislaptop](https://github.com/morrislaptop)

## [0.5.3](https://github.com/pusher/chatkit-server-php/compare/0.5.2...0.5.3) - 2018-06-11

### Added

- `deleteRoom` functionality added [#19](https://github.com/pusher/chatkit-server-php/pull/19) by [@morrislaptop](https://github.com/morrislaptop)
- `getUsers` functionality added [#20](https://github.com/pusher/chatkit-server-php/pull/20) by [@morrislaptop](https://github.com/morrislaptop)

## [0.5.2](https://github.com/pusher/chatkit-server-php/compare/0.5.1...0.5.2) - 2018-06-07

### Fixed

- `updateUser` validates information properly [#18](https://github.com/pusher/chatkit-server-php/pull/18) by [@morrislaptop](https://github.com/morrislaptop)

### Added

- `sendMessage` supports send messages with attachments [#18](https://github.com/pusher/chatkit-server-php/pull/18) by [@morrislaptop](https://github.com/morrislaptop)

## [0.5.1](https://github.com/pusher/chatkit-server-php/compare/0.5.0...0.5.1) - 2018-05-25

### Changed

- User ID is validated to be a string as part of `createUser`, `authenticate`, and `generateAccessToken` calls

### Fixed

- `getUserRooms` no longer crashes if `joinable` option not set

## [0.5.0](https://github.com/pusher/chatkit-server-php/compare/0.4.0...0.5.0) - 2018-05-11

### Changed

- API calls to Chatkit servers now return an associative array that has the keys `'status'` and `'body'`.

## [0.4.0](https://github.com/pusher/chatkit-server-php/compare/0.3.0...0.4.0) - 2018-04-20

### Added

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

### Removed

- `getTokenPair` has been removed

### Changed

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
