🚨🚨🚨 Very much a work in progress so expect very rough edges! 🚨🚨🚨

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
    "pusher/pusher-chatkit-server": "^0.1.1"
}
```

and then run `composer update`.

Or you can clone or download the library files.

**We recommend you [use composer](http://getcomposer.org/).**

This library depends on PHP modules for cURL and JSON. See [cURL module installation instructions](http://php.net/manual/en/curl.installation.php) and [JSON module installation instructions](http://php.net/manual/en/json.installation.php).


## Chatkit constructor

Head to your dashbord to find your `instance_locator` and `key` and use them to create a new `Chatkit\Chatkit` instance.

```php
$instance_locator = 'YOUR_INSTANCE_LOCATOR';
$key = 'YOUR_KEY';

$chatkit = new Chatkit\Chatkit($instance_locator, $key, array());
```

## Generating a token pair

To generate token pair (access token and refresh token) for usage by a Chatkit client use the `generate_token_pair` function.

```php
$chatkit->generate_token_pair(array(
  "user_id" => "ham"
))
```

## Creating a user

To create a user you must provide an `id` and a `name`. You can optionally provide an `avatar_url (string)` and `custom_data (array)`.

```php
$chatkit->create_user("ham", "Hamilton Chapman")
```

Or with an `avatar_url` and `custom_data`:

```php
$chatkit->create_user(
  "ham",
  "Hamilton Chapman"
  "http://cat.com/cat.jpg",
  array(
    "my_custom_key" => "some data"
  )
)
```
