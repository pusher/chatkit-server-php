<?php

namespace Chatkit;

use Chatkit\Exceptions\ChatkitException;
use Chatkit\Exceptions\ConfigurationException;
use Chatkit\Exceptions\ConnectionException;
use Chatkit\Exceptions\ConnectionException as ConnectionExceptionAlias;
use Chatkit\Exceptions\MissingArgumentException;
use Chatkit\Exceptions\TypeMismatchException;
use Chatkit\Exceptions\UnexpectedArgumentException;
use Chatkit\Exceptions\UploadException;
use Firebase\JWT\JWT;

class Chatkit
{
    protected $settings = array(
        'scheme'       => 'https',
        'port'         => 80,
        'timeout'      => 30,
        'debug'        => false,
        'curl_options' => array(),
    );

    protected $api_settings_v2 = array();

    protected $scheduler_settings = array();

    /** @var object|null Implementing a public function log(string) */
    protected $logger = null;
    protected $ch = null; // Curl handler

    protected $api_settings = array();
    protected $authorizer_settings = array();
    protected $cursor_settings = array();

    const GLOBAL_SCOPE = 'global';
    const ROOM_SCOPE = 'room';

    /**
     * Chatkit constructor.
     *
     * Initializes a new Chatkit instance.
     * @param array $options   Options to configure the Chatkit instance.
     *                         instance_locator - your Chatkit instance locator
     *                         key - your Chatkit instance's key
     *                         scheme - e.g. http or https
     *                         host - the host; no trailing forward slash.
     *                         port - the http port
     *                         timeout - the http timeout
     * @throws ConfigurationException
     * @throws MissingArgumentException
     */
    public function __construct($options)
    {
        $this->checkCompatibility();

        if (!isset($options['instance_locator'])) {
            throw new MissingArgumentException('You must provide an instance_locator');
        }
        if (!isset($options['key'])) {
            throw new MissingArgumentException('You must provide a key');
        }

        $this->settings['instance_locator'] = $options['instance_locator'];
        $this->settings['key'] = $options['key'];
        $this->api_settings['service_name'] = 'chatkit';
        $this->api_settings['service_version'] = 'v6';
        $this->api_settings_v2['service_name'] = 'chatkit';
        $this->api_settings_v2['service_version'] = 'v2';
        $this->authorizer_settings['service_name'] = 'chatkit_authorizer';
        $this->authorizer_settings['service_version'] = 'v2';
        $this->cursor_settings['service_name'] = 'chatkit_cursors';
        $this->cursor_settings['service_version'] = 'v2';
        $this->scheduler_settings['service_name'] = 'chatkit_scheduler';
        $this->scheduler_settings['service_version'] = 'v1';

        foreach ($options as $key => $value) {
            // only set if valid setting/option
            if (isset($this->settings[$key])) {
                $this->settings[$key] = $value;
            }
        }
    }

    /**
     * @param $auth_options
     * @return array
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     */
    public function authenticate($auth_options)
    {
        if (!isset($auth_options['user_id'])) {
            throw new MissingArgumentException('You must provide a user ID');
        }

        $access_token = $this->generateAccessToken($auth_options)['token'];

        return [
            'status' => 200,
            'headers' => array(),
            'body' => [
                'access_token' => $access_token,
                'token_type' => 'bearer',
                'expires_in' => 24 * 60 * 60
            ]
        ];
    }

    /**
     * @param $auth_options
     * @return array
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     */
    public function generateAccessToken($auth_options)
    {
        if (empty($auth_options)) {
            throw new MissingArgumentException('You must provide a either a user_id or `su: true`');
        }
        return $this->generateToken($auth_options);
    }

    /**
     * @param array $auth_options
     * @return array
     * @throws TypeMismatchException
     */
    public function generateSuToken($auth_options = [])
    {
        $auth_options = array_merge($auth_options, [ 'su' => true ]);
        return $this->generateToken($auth_options);
    }

    /**
     * @param array $auth_options
     * @return array
     * @throws TypeMismatchException
     */
    public function generateToken($auth_options = [])
    {
        $split_instance_locator = explode(':', $this->settings['instance_locator']);
        $split_key = explode(':', $this->settings['key']);

        $now = time();
        $claims = array(
            'instance' => $split_instance_locator[2],
            'iss' => 'api_keys/'.$split_key[0],
            'iat' => $now
        );

        if (isset($auth_options['user_id'])) {
            if (gettype($auth_options['user_id']) != 'string') {
                throw new TypeMismatchException('User ID must be a string');
            }
            $claims['sub'] = $auth_options['user_id'];
        }

        if (isset($auth_options['su']) && $auth_options['su'] === true) {
            $claims['su'] = true;
        }

        // not using functions such as `strtotime` as timezones/daylight savings are problematic
        $claims['exp'] = $now + 24 * 60 * 60;

        $jwt = JWT::encode($claims, $split_key[1]);
        return [
            'token' => $jwt,
            'expires_in' => 24 * 60 * 60
        ];
    }

    /**
     * Set a logger to be informed of internal log messages.
     *
     * @param object $logger A object with a public function log($message) method
     *
     * @return void
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Log a string.
     *
     * @param string $msg The message to log
     *
     * @return void
     */
    protected function log($msg)
    {
        if (is_null($this->logger) === false) {
            $this->logger->log('Chatkit: '.$msg);
        }
    }

    /**
     * Check if the current PHP setup is sufficient to run this class.
     *
     * @throws ConfigurationException if any required dependencies are missing
     *
     * @return void
     */
    protected function checkCompatibility()
    {
        if (!extension_loaded('curl')) {
            throw new ConfigurationException('The Chatkit SDK requires the PHP cURL module. Please ensure it is installed');
        }

        if (!extension_loaded('json')) {
            throw new ConfigurationException('The Chatkit SDK requires the PHP JSON module. Please ensure it is installed');
        }
    }

    // User API

    /**
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function createUser($options)
    {
        if (!isset($options['id'])) {
            throw new MissingArgumentException('You must provide an ID');
        }
        if (gettype($options['id']) != 'string') {
            throw new TypeMismatchException('User ID must be a string');
        }
        if (!isset($options['name'])) {
            throw new MissingArgumentException('You must provide a name');
        }

        $body = array(
            'id' => $options['id'],
            'name' => $options['name']
        );

        if (isset($options['avatar_url']) && !is_null($options['avatar_url'])) {
            $body['avatar_url'] = $options['avatar_url'];
        }

        if (isset($options['custom_data']) && !is_null($options['custom_data'])) {
            $body['custom_data'] = $options['custom_data'];
        }

        $options = [
            'method' => 'POST',
            'path' => '/users',
            'jwt' => $this->getServerToken()['token'],
            'body' => $body
        ];

        return $this->apiRequest($options);
    }

    /**
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function createUsers($options)
    {
        if (!isset($options['users'])) {
            throw new MissingArgumentException('You must provide a list of users you want to create');
        }

        $options = [
            'method' => 'POST',
            'path' => '/batch_users',
            'jwt' => $this->getServerToken()['token'],
            'body' => $options
        ];

        return $this->apiRequest($options);
    }

    /**
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function updateUser($options)
    {
        if (!isset($options['id'])) {
            throw new MissingArgumentException('You must provide the ID of the user you want to update');
        }

        $body = array();

        if (isset($options['name']) && !is_null($options['name'])) {
            $body['name'] = $options['name'];
        }
        if (isset($options['avatar_url']) && !is_null($options['avatar_url'])) {
            $body['avatar_url'] = $options['avatar_url'];
        }
        if (isset($options['custom_data']) && !is_null($options['custom_data'])) {
            $body['custom_data'] = $options['custom_data'];
        }

        if (empty($body)) {
            throw new MissingArgumentException('At least one of the following are required: name, avatar_url, or custom_data.');
        }

        $user_id = rawurlencode($options['id']);
        $token = $this->getServerToken([ 'user_id' => $user_id ])['token'];

        return $this->apiRequest([
            'method' => 'PUT',
            'path' => "/users/$user_id",
            'jwt' => $token,
            'body' => $body
        ]);
    }

    /**
     * Delete a user
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function deleteUser($options)
    {
        if (!isset($options['id'])) {
            throw new MissingArgumentException('You must provide the ID of the user you want to delete');
        }

        $user_id = rawurlencode($options['id']);

        return $this->apiRequest([
            'method' => 'DELETE',
            'path' => "/users/$user_id",
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    /**
     * Asynchronously delete a user.
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function asyncDeleteUser($options)
    {
        if (!isset($options['id'])) {
            throw new MissingArgumentException('You must provide the ID of the user you want to delete');
        }

        $user_id = rawurlencode($options['id']);

        return $this->schedulerRequest([
            'method' => 'PUT',
            'path' => "/users/$user_id",
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    /**
     * Check the status of a user being asynchronously deleted.
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getDeleteStatus($options)
    {
        if (!isset($options['id'])) {
            throw new MissingArgumentException('You must provide the ID of the job to query status of');
        }

        $job_id = rawurlencode($options['id']);

        return $this->schedulerRequest([
            'method' => 'GET',
            'path' => "/status/$job_id",
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    /**
     * Get a user
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getUser($options)
    {
        if (!isset($options['id'])) {
            throw new MissingArgumentException('You must provide the ID of the user you want to fetch');
        }

        $user_id = rawurlencode($options['id']);

        return $this->apiRequest([
            'method' => 'GET',
            'path' => "/users/$user_id",
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    /**
     * $options['from_timestamp'] should be in the B8601DZw.d format
     *
     * e.g. 2018-04-17T14:02:00Z
     *
     * @param array $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getUsers($options = [])
    {
        $query_params = [];

        if (!empty($options['from_timestamp'])) {
            $query_params['from_ts'] = $options['from_timestamp'];
        }

        if (!empty($options['limit'])) {
            $query_params['limit'] = $options['limit'];
        }

        return $this->apiRequest([
            'method' => 'GET',
            'path' => '/users',
            'jwt' => $this->getServerToken()['token'],
            'query' => $query_params
        ]);
    }

    /**
     * Get users by their Chatkit id.
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getUsersByID($options)
    {
        if (!isset($options['user_ids'])) {
            throw new MissingArgumentException('You must provide the IDs of the users you want to fetch');
        }

        return $this->apiRequest([
            'method' => 'GET',
            'path' => '/users_by_ids',
            'jwt' => $this->getServerToken()['token'],
            'query' => [ 'id' => $options['user_ids'] ]
        ]);
    }

    // Room API

    /**
     * Creates a new room.
     *
     * @param array $options The room options
     *                          [Available Options]
     *                          • creator_id (string|required): Represents the ID of the user that you want to create the room.
     *                          • name (string|optional): Represents the name with which the room is identified.
     *                              A room name must not be longer than 40 characters and can only contain lowercase letters,
     *                              numbers, underscores and hyphens.
     *                          • private (boolean|optional): Indicates if a room should be private or public. Private by default.
     *                          • user_ids (array|optional): If you wish to add users to the room at the point of creation,
     *                              you may provide their user IDs.
     *                          • custom_data (assoc array|optional): If you wish to attach some custom data to a room,
     *                              you may provide a list of key value pairs.
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function createRoom($options)
    {
        if (!isset($options['creator_id'])) {
            throw new MissingArgumentException('You must provide the ID of the user creating the room');
        }
        if (!isset($options['name'])) {
            throw new MissingArgumentException('You must provide a name for the room');
        }

        $body = [
            'name' => $options['name'],
            'private' => false
        ];
        if (isset($options['id'])) {
            $body['id'] = $options['id'];
        }
        if (isset($options['private'])) {
            $body['private'] = $options['private'];
        }
        if (isset($options['user_ids'])) {
            $body['user_ids'] = $options['user_ids'];
        }
        if (isset($options['push_notification_title_override'])) {
            $body['push_notification_title_override'] = $options['push_notification_title_override'];
        }
        if (isset($options['custom_data'])) {
            $body['custom_data'] = $options['custom_data'];
        }

        $token = $this->getServerToken([ 'user_id' => $options['creator_id'] ])['token'];

        return $this->apiRequest([
            'method' => 'POST',
            'path' => '/rooms',
            'jwt' => $token,
            'body' => $body
        ]);
    }

    /**
     * Updates a Room
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function updateRoom($options)
    {
        if (!isset($options['id'])) {
            throw new MissingArgumentException('You must provide the ID of the room to update');
        }

        $body = [];
        if (isset($options['private'])) {
            $body['private'] = $options['private'];
        }
        if (isset($options['name'])) {
            $body['name'] = $options['name'];
        }
        if (array_key_exists('push_notification_title_override', $options)) { // We want to accept null
            $body['push_notification_title_override'] = $options['push_notification_title_override'];
        }
        if (isset($options['custom_data'])) {
            $body['custom_data'] = $options['custom_data'];
        }

        $room_id = rawurlencode($options['id']);

        return $this->apiRequest([
            'method' => 'PUT',
            'path' => "/rooms/$room_id",
            'jwt' => $this->getServerToken()['token'],
            'body' => $body
        ]);
    }

    /**
     * Delete a room
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function deleteRoom($options)
    {
        if (!isset($options['id'])) {
            throw new MissingArgumentException('You must provide the ID of the room to delete');
        }

        $room_id = rawurlencode($options['id']);

        return $this->apiRequest([
            'method' => 'DELETE',
            'path' => "/rooms/$room_id",
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    /**
     * Asynchronously delete a room.
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function asyncDeleteRoom($options)
    {
        if (!isset($options['id'])) {
            throw new MissingArgumentException('You must provide the ID of the room to delete');
        }

        $room_id = rawurlencode($options['id']);

        return $this->schedulerRequest([
            'method' => 'PUT',
            'path' => "/rooms/$room_id",
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    /**
     * Get a Room with a provided ID
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getRoom($options)
    {
        if (!isset($options['id'])) {
            throw new MissingArgumentException('You must provide the ID of the room to fetch');
        }

        $room_id = rawurlencode($options['id']);

        return $this->apiRequest([
            'method' => 'GET',
            'path' => "/rooms/$room_id",
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    /**
     * Get rooms by ID, paged 100 at a time.
     *
     * @param array $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getRooms($options = [])
    {
        $query_params = [];

        if (!empty($options['from_id'])) {
            $query_params['from_id'] = $options['from_id'];
        }

        if (!empty($options['include_private'])) {
            $query_params['include_private'] = $options['include_private'];
        }

        return $this->apiRequest([
            'method' => 'GET',
            'path' => "/rooms",
            'jwt' => $this->getServerToken()['token'],
            'query' => $query_params
        ]);
    }

    /**
     * Get all rooms a user belongs to
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getUserRooms($options)
    {
        return $this->getRoomsForUser($options);
    }


    /**
     * Get all rooms that are joinable for a given user
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getUserJoinableRooms($options)
    {
        $options = array_merge($options, [ 'joinable' => true ]);
        return $this->getRoomsForUser($options);
    }

    /**
     * Add a set of users to a room.
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function addUsersToRoom($options)
    {
        if (!isset($options['room_id'])) {
            throw new MissingArgumentException('You must provide the ID of the room you want to add users to');
        }
        if (!isset($options['user_ids'])) {
            throw new MissingArgumentException('You must provide a list of IDs of the users you want to add to the room');
        }

        $room_id = rawurlencode($options['room_id']);

        return $this->apiRequest([
            'method' => 'PUT',
            'path' => "/rooms/$room_id/users/add",
            'jwt' => $this->getServerToken()['token'],
            'body' => [ 'user_ids' => $options['user_ids'] ]
        ]);
    }

    /**
     * Remove a set of users from a room.
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function removeUsersFromRoom($options)
    {
        if (!isset($options['room_id'])) {
            throw new MissingArgumentException('You must provide the ID of the room you want to remove users from');
        }
        if (!isset($options['user_ids'])) {
            throw new MissingArgumentException('You must provide a list of IDs of the users you want to remove from the room');
        }

        $room_id = rawurlencode($options['room_id']);

        return $this->apiRequest([
            'method' => 'PUT',
            'path' => "/rooms/$room_id/users/remove",
            'jwt' => $this->getServerToken()['token'],
            'body' => [ 'user_ids' => $options['user_ids'] ]
        ]);
    }

    # Messages API

    /**
     * Get messages in a room
     *
     * @param array $options
     *              [Available Options]
     *              • room_id (string|required): Represents the ID of the room that you want to get the messages for.
     *              • initial_id (integer|optional): Starting ID of the range of messages.
     *              • limit (integer|optional): Number of messages to return
     *              • direction (string|optional): Order of messages - one of newer or older
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getRoomMessages($options)
    {
        if (!isset($options['room_id'])) {
            throw new MissingArgumentException('You must provide the ID of the room to fetch messages from');
        }

        $query_params = [];
        if (!empty($options['initial_id'])) {
            $query_params['initial_id'] = $options['initial_id'];
        }
        if (!empty($options['limit'])) {
            $query_params['limit'] = $options['limit'];
        }
        if (!empty($options['direction'])) {
            $query_params['direction'] = $options['direction'];
        }

        $room_id = rawurlencode($options['room_id']);

        return $this->apiRequestV2([
            'method' => 'GET',
            'path' => "/rooms/$room_id/messages",
            'jwt' => $this->getServerToken()['token'],
            'query' => $query_params
        ]);
    }

    /**
     * Send a message to a room.
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function sendMessage($options)
    {
        verify([SENDER_ID,
                ROOM_ID,
                [ 'text' => [
                    'type' => 'string',
                    'missing_message' =>
                    'You must provide some text for the message' ]
                ]
        ], $options);

        $body = array(
            'text' => $options['text']
        );

        if (isset($options['attachment'])) {
            if (!isset($options['attachment']['resource_link'])) {
                throw new MissingArgumentException('You must provide a resource_link for the message attachment');
            }

            $valid_file_types = ['image', 'video', 'audio', 'file'];
            if (!isset($options['attachment']['type']) || !in_array($options['attachment']['type'], $valid_file_types)) {
                $valid_file_types_str = implode(',', $valid_file_types);
                throw new MissingArgumentException("You must provide the type for the attachment. This can be one of $valid_file_types_str");
            }

            $body['attachment'] = array(
                'resource_link' => $options['attachment']['resource_link'],
                'type' => $options['attachment']['type']
            );
        }

        $token = $this->getServerToken([ 'user_id' => $options['sender_id'] ])['token'];
        $room_id = rawurlencode($options['room_id']);

        return $this->apiRequestV2([
            'method' => 'POST',
            'path' => "/rooms/$room_id/messages",
            'jwt' => $token,
            'body' => $body
        ]);
    }

    /**
     * Send a simple text based message to a room.
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     * @throws UploadException
     */
    public function sendSimpleMessage($options)
    {
        verify([ [ 'text' => [
            'type' => 'string',
            'missing_message' =>
            'You must provide some text for the message' ] ]
        ], $options);

        $options['parts'] = [ [ 'type' => 'text/plain',
                                'content' => $options['text'] ]
        ];
        unset($options['text']);
        return $this->sendMultipartMessage($options);
    }

    /**
     * Send a multi-part message to a room.
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     * @throws UploadException
     */
    public function sendMultipartMessage($options)
    {
        verify([SENDER_ID,
                ROOM_ID,
                [ 'parts' => [
                    'type' => 'non_empty_array',
                    'missing_message' =>
                    'You must provide a non-empty parts array' ]
                ]
        ], $options);

        // this assumes the token lives long enough to finish all S3 uploads
        $token = $this->getServerToken([ 'user_id' => $options['sender_id'] ])['token'];
        $room_id = rawurlencode($options['room_id']);

        foreach($options['parts'] as &$part) {
            verify([ [ 'type' => [ 'type' => 'string',
                                   'missing_message' => 'Each part must have a type' ] ],
                     [ 'file' => OPTIONAL_STRING ],
                     [ 'content' => OPTIONAL_STRING ],
                     [ 'url' => OPTIONAL_STRING ],
                     [ 'name' => OPTIONAL_STRING ],
                     [ 'customData' => [ 'type' => 'json', 'optional' => true ] ]
            ], $part);

            if (!isset($part['content']) && !isset($part['url']) && !isset($part['file'])) {
                throw new MissingArgumentException('Each part must define either file, content or url');
            }

            if (isset($part['file'])) {
                $attachment_id = $this->uploadAttachment($token, $room_id, $part);
                $part['attachment'] = [ 'id' => $attachment_id ];
                unset($part['file']);
            }

        }

        return $this->apiRequest([
            'method' => 'POST',
            'path' => "/rooms/$room_id/messages",
            'jwt' => $token,
            'body' => [ 'parts' => $options['parts'] ]
        ]);
    }

    /**
     * Fetch a Multipart Messages
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function fetchMultipartMessages($options)
    {
        verify([ROOM_ID,
                [ 'limit' => OPTIONAL_INT,
                  'direction' => OPTIONAL_STRING,
                  'initial_id' => OPTIONAL_STRING,
                ]
        ], $options);

        $optional_fields = ['limit', 'direction', 'initial_id'];
        $query_params = $this->getOptionalFields($optional_fields, $options);
        $room_id = rawurlencode($options['room_id']);

        return $this->apiRequest([
            'method' => 'GET',
            'path' => "/rooms/$room_id/messages",
            'jwt' => $this->getServerToken()['token'],
            'query' => $query_params
        ]);
    }

    /**
     * @param $room_id
     * @param $message_id
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws UnexpectedArgumentException
     * @throws ChatkitException
     */
    public function editMessage($room_id, $message_id, $options)
    {
        verify([ROOM_ID,MESSAGE_ID], ['room_id' => $room_id, 'message_id' => $message_id]);
        verify([SENDER_ID,
                [ 'text' => [
                    'type' => 'string',
                    'missing_message' =>
                    'You must provide some text for the message' ]
                ]
        ], $options);
        if (isset($options['room_id'])) {
          throw new UnexpectedArgumentException('a messages room ids cannot be edited');
        }
        if (isset($options['message_id'])) {
          throw new UnexpectedArgumentException('a messages id cannot be edited');
        }

        $body = array(
            'text' => $options['text']
        );

        if (isset($options['attachment'])) {
            if (!isset($options['attachment']['resource_link'])) {
                throw new MissingArgumentException('You must provide a resource_link for the message attachment');
            }

            $valid_file_types = ['image', 'video', 'audio', 'file'];
            if (!isset($options['attachment']['type']) || !in_array($options['attachment']['type'], $valid_file_types)) {
                $valid_file_types_str = implode(',', $valid_file_types);
                throw new MissingArgumentException("You must provide the type for the attachment. This can be one of $valid_file_types_str");
            }

            $body['attachment'] = array(
                'resource_link' => $options['attachment']['resource_link'],
                'type' => $options['attachment']['type']
            );
        }

        $token = $this->getServerToken([ 'user_id' => $options['sender_id'] ])['token'];
        $room_id = rawurlencode($room_id);

        return $this->apiRequestV2([
            'method' => 'PUT',
            'path' => "/rooms/$room_id/messages/$message_id",
            'jwt' => $token,
            'body' => $body
        ]);
    }

    /**
     * @param $room_id
     * @param $message_id
     * @param $options
     * @return array
     * @throws ChatkitException
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws UnexpectedArgumentException
     * @throws UploadException
     */
    public function editSimpleMessage($room_id, $message_id, $options)
    {
        verify([ [ 'text' => [
            'type' => 'string',
            'missing_message' =>
            'You must provide some text for the message' ] ]
        ], $options);

        $options['parts'] = [ [ 'type' => 'text/plain',
                                'content' => $options['text'] ]
        ];
        unset($options['text']);
        return $this->editMultipartMessage($room_id, $message_id, $options);
    }

    /**
     * @param $room_id
     * @param $message_id
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws UnexpectedArgumentException
     * @throws UploadException
     * @throws ChatkitException
     */
    public function editMultipartMessage($room_id, $message_id, $options)
    {
        verify([ROOM_ID,MESSAGE_ID], ['room_id' => $room_id, 'message_id' => $message_id]);
        verify([SENDER_ID,
                [ 'parts' => [
                    'type' => 'non_empty_array',
                    'missing_message' =>
                    'You must provide a non-empty parts array' ]
                ]
        ], $options);
        if (isset($options['room_id'])) {
          throw new UnexpectedArgumentException('a messages room id cannot be edited');
        }
        if (isset($options['message_id'])) {
          throw new UnexpectedArgumentException('a message id cannot be edited');
        }

        // this assumes the token lives long enough to finish all S3 uploads
        $token = $this->getServerToken([ 'user_id' => $options['sender_id'] ])['token'];
        $room_id = rawurlencode($room_id);

        foreach($options['parts'] as &$part) {
            verify([ [ 'type' => [ 'type' => 'string',
                                   'missing_message' => 'Each part must have a type' ] ],
                     [ 'file' => OPTIONAL_STRING ],
                     [ 'content' => OPTIONAL_STRING ],
                     [ 'url' => OPTIONAL_STRING ],
                     [ 'name' => OPTIONAL_STRING ],
                     [ 'customData' => [ 'type' => 'json', 'optional' => true ] ]
            ], $part);

            if (!isset($part['content']) && !isset($part['url']) && !isset($part['file'])) {
                throw new MissingArgumentException('Each part must define either file, content or url');
            }

            if (isset($part['file'])) {
                $attachment_id = $this->uploadAttachment($token, $room_id, $part);
                $part['attachment'] = [ 'id' => $attachment_id ];
                unset($part['file']);
            }

        }
        return $this->apiRequest([
            'method' => 'PUT',
            'path' => "/rooms/$room_id/messages/$message_id",
            'jwt' => $token,
            'body' => [ 'parts' => $options['parts'] ]
        ]);
    }

    /**
     * Delete a Message from a room
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function deleteMessage($options)
    {
        if (!isset($options['message_id'])) {
            throw new MissingArgumentException('You must provide the ID of the message to delete');
        }

        if (!isset($options['room_id'])) {
            throw new MissingArgumentException('You must provide the ID of the room to which the message belongs');
        }

        $message_id = $options['message_id'];
        $room_id = $options['room_id'];

        return $this->apiRequest([
            'method' => 'DELETE',
            'path' => "/rooms/$room_id/messages/$message_id",
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    // Roles and permissions API

    /**
     * Create a global role
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function createGlobalRole($options)
    {
        $options['scope'] = self::GLOBAL_SCOPE;
        return $this->createRole($options);
    }

    /**
     * Create a role for a room
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function createRoomRole($options)
    {
        $options['scope'] = self::ROOM_SCOPE;
        return $this->createRole($options);
    }

    /**
     * Delete a global role
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function deleteGlobalRole($options)
    {
        $options['scope'] = self::GLOBAL_SCOPE;
        return $this->deleteRole($options);
    }

    /**
     * Delete a role for a room
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function deleteRoomRole($options)
    {
        $options['scope'] = self::ROOM_SCOPE;
        return $this->deleteRole($options);
    }

    /**
     * Assign a global role to a user
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function assignGlobalRoleToUser($options)
    {
        return $this->assignRoleToUser($options);
    }

    /**
     * Assign a room role to a user
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function assignRoomRoleToUser($options)
    {
        if (!isset($options['room_id'])) {
            throw new MissingArgumentException('You must provide a room ID to assign a room role to a user');
        }
        return $this->assignRoleToUser($options);
    }

    /**
     * Get available roles
     *
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getRoles()
    {
        return $this->authorizerRequest([
            'method' => 'GET',
            'path' => '/roles',
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    /**
     * Get roles that a user has
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getUserRoles($options)
    {
        if (!isset($options['user_id'])) {
            throw new MissingArgumentException('You must provide the ID of the user whose roles you want to fetch');
        }

        $user_id = rawurlencode($options['user_id']);

        return $this->authorizerRequest([
            'method' => 'GET',
            'path' => "/users/$user_id/roles",
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    /**
     * Remove a global role from a user
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function removeGlobalRoleForUser($options)
    {
        return $this->removeRoleForUser($options);
    }

    /**
     * Remove a room role from a user
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function removeRoomRoleForUser($options)
    {
        if (!isset($options['room_id'])) {
            throw new MissingArgumentException('You must provide a room ID to remove a room role for a user');
        }
        return $this->removeRoleForUser($options);
    }

    /**
     * Get permissions for a global role
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getPermissionsForGlobalRole($options)
    {
        $options['scope'] = self::GLOBAL_SCOPE;
        return $this->getPermissionsForRole($options);
    }

    /**
     * Get permissions for a room role
     *
     * @param $options
     * @return array
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    public function getPermissionsForRoomRole($options)
    {
        $options['scope'] = self::ROOM_SCOPE;
        return $this->getPermissionsForRole($options);
    }

    /**
     * Update permissions for a global role
     *
     * @param $options
     * @return array
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    public function updatePermissionsForGlobalRole($options)
    {
        $options['scope'] = self::GLOBAL_SCOPE;
        return $this->updatePermissionsForRole($options);
    }

    /**
     * Update permissions for a room role
     *
     * @param $options
     * @return array
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    public function updatePermissionsForRoomRole($options)
    {
        $options['scope'] = self::ROOM_SCOPE;
        return $this->updatePermissionsForRole($options);
    }

    // Cursors API

    /**
     * Get the read cursor for a user in a given room
     *
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getReadCursor($options)
    {
        if (!isset($options['user_id'])) {
            throw new MissingArgumentException('You must provide the ID of the user whose read cursor you want to fetch');
        }
        if (!isset($options['room_id'])) {
            throw new MissingArgumentException('You must provide the ID of the room that you want the read cursor for');
        }

        $user_id = rawurlencode($options['user_id']);
        $room_id = rawurlencode($options['room_id']);

        return $this->cursorsRequest([
            'method' => 'GET',
            'path' => "/cursors/0/rooms/$room_id/users/$user_id",
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    /**
     * Sets the read cursor for a user in a room.
     *
     * @param array $options
     *              [Available Options]
     *              • user_id (string|required): Represents the ID of the user that you want to set the cursor for.
     *              • room_id (string|required): Represents the ID of the room that you want to set the cursor for.
     *              • position (integer|required): Represents the ID of the message the user has read.
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function setReadCursor($options)
    {
        if (!isset($options['user_id'])) {
            throw new MissingArgumentException('You must provide the ID of the user that you want to get the cursor for');
        }
        if (!isset($options['room_id'])) {
            throw new MissingArgumentException('You must provide the room ID of the room that you want to set the cursor for');
        }
        if (!isset($options['position'])) {
            throw new MissingArgumentException('You must provide the position of the cursor');
        }

        $user_id = rawurlencode($options['user_id']);
        $room_id = rawurlencode($options['room_id']);

        return $this->cursorsRequest([
            'method' => 'PUT',
            'path' => "/cursors/0/rooms/$room_id/users/$user_id",
            'jwt' => $this->getServerToken()['token'],
            'body' => ['position' => $options['position']]
        ]);
    }

    /**
     * Get all read cursors for a user
     *
     * @param array $options
     *              [Available Options]
     *              • user_id (string|required): Represents the ID of the user that you want to get the read cursors for.
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getReadCursorsForUser($options)
    {
        if (!isset($options['user_id'])) {
            throw new MissingArgumentException('You must provide the ID of the user that you want the read cursors for');
        }

        $user_id = rawurlencode($options['user_id']);

        return $this->cursorsRequest([
            'method' => 'GET',
            'path' => "/cursors/0/users/$user_id",
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    /**
     * Get all read cursors for a room
     *
     * @param array $options
     *              [Available Options]
     *              • room_id (string|required): Represents the ID of the room that you want to get the read cursors for.
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ChatkitException
     */
    public function getReadCursorsForRoom($options)
    {
        if (!isset($options['room_id'])) {
            throw new MissingArgumentException('You must provide the ID of the room that you want the read cursors for');
        }

        $room_id = rawurlencode($options['room_id']);

        return $this->cursorsRequest([
            'method' => 'GET',
            'path' => "/cursors/0/rooms/$room_id",
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    // Service-specific helpers

    /**
     * @param $options
     * @return array
     * @throws ConnectionExceptionAlias
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    public function apiRequest($options)
    {
        return $this->makeRequest($this->api_settings, $options);
    }

    /**
     * Keep v2 for backwards compatibility
     *
     * @param $options
     * @return array
     * @throws ConnectionExceptionAlias
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    public function apiRequestV2($options)
    {
        return $this->makeRequest($this->api_settings_v2, $options);
    }

    /**
     * @param $options
     * @return array
     * @throws ConnectionExceptionAlias
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    public function authorizerRequest($options)
    {
        return $this->makeRequest($this->authorizer_settings, $options);
    }

    /**
     * @param $options
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws ChatkitException
     */
    public function cursorsRequest($options)
    {
        return $this->makeRequest($this->cursor_settings, $options);
    }

    /**
     * @param $options
     * @return array
     * @throws ConnectionExceptionAlias
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    public function schedulerRequest($options)
    {
        return $this->makeRequest($this->scheduler_settings, $options);
    }

    /**
     * @param $instance_settings
     * @param $options
     * @return array
     * @throws ConnectionExceptionAlias
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    protected function makeRequest($instance_settings, $options)
    {
        $options = array_merge($options, [ 'Content-Type' => 'application/json' ]);

        $ch = $this->createCurl(
            $instance_settings,
            $options['path'],
            $options['jwt'],
            $options['method'],
            isset($options['body']) ? $options['body'] : null,
            isset($options['query']) ? $options['query'] : []
        );

        return $this->execCurl($ch);
    }

    /**
     * @param $options
     * @return array
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    protected function getRoomsForUser($options)
    {
        if (!isset($options['id'])) {
            throw new MissingArgumentException('You must provide the ID of the user that you want to get the rooms for');
        }

        $query_params = [];
        if (!empty($options['joinable'])) {
            $query_params['joinable'] = $options['joinable'];
        }

        $user_id = rawurlencode($options['id']);

        return $this->apiRequest([
            'method' => 'GET',
            'path' => "/users/$user_id/rooms",
            'jwt' => $this->getServerToken()['token'],
            'query' => $query_params
        ]);
    }

    /**
     * @param $options
     * @return array
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    protected function createRole($options)
    {
        if (!isset($options['name'])) {
            throw new MissingArgumentException('You must provide a name for the role');
        }

        if (!isset($options['permissions'])) {
            throw new MissingArgumentException("You must provide permissions for the role, even if it's an empty list");
        }

        return $this->authorizerRequest([
            'method' => 'POST',
            'path' => '/roles',
            'jwt' => $this->getServerToken()['token'],
            'body' => [
                'scope' => $options['scope'],
                'name' => $options['name'],
                'permissions' => $options['permissions']
            ]
        ]);
    }

    /**
     * @param $options
     * @return array
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    protected function deleteRole($options)
    {
        if (!isset($options['name'])) {
            throw new MissingArgumentException("You must provide the role's name");
        }

        $role_name = rawurlencode($options['name']);
        $scope = $options['scope'];

        return $this->authorizerRequest([
            'method' => 'DELETE',
            'path' => "/roles/$role_name/scope/$scope",
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    /**
     * @param $options
     * @return array
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    protected function assignRoleToUser($options)
    {
        if (!isset($options['name'])) {
            throw new MissingArgumentException("You must provide the role's name");
        }

        if (!isset($options['user_id'])) {
            throw new MissingArgumentException('You must provide the ID of the user you want to assign the role to');
        }

        $body = [
            'name' => $options['name']
        ];

        if (isset($options['room_id'])) {
            $body['room_id'] = $options['room_id'];
        }

        $user_id = rawurlencode($options['user_id']);

        return $this->authorizerRequest([
            'method' => 'PUT',
            'path' => "/users/$user_id/roles",
            'jwt' => $this->getServerToken()['token'],
            'body' => $body
        ]);
    }

    /**
     * @param $options
     * @return array
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    protected function removeRoleForUser($options)
    {
        if (!isset($options['user_id'])) {
            throw new MissingArgumentException('You must provide the ID of the user you want to remove the role for');
        }

        $user_id = rawurlencode($options['user_id']);

        $req_opts = [
            'method' => 'DELETE',
            'path' => "/users/$user_id/roles",
            'jwt' => $this->getServerToken()['token'],
        ];

        if (isset($options['room_id'])) {
            $req_opts['query'] = [ 'room_id' => $options['room_id'] ];
        }

        return $this->authorizerRequest($req_opts);
    }

    /**
     * @param $options
     * @return array
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    protected function getPermissionsForRole($options)
    {
        if (!isset($options['name'])) {
            throw new MissingArgumentException('You must provide the name of the role you want to fetch the permissions of');
        }

        $role_name = rawurlencode($options['name']);
        $scope = $options['scope'];

        return $this->authorizerRequest([
            'method' => 'GET',
            'path' => "/roles/$role_name/scope/$scope/permissions",
            'jwt' => $this->getServerToken()['token']
        ]);
    }

    /**
     * @param $options
     * @return array
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     * @throws ConfigurationException
     * @throws ChatkitException
     */
    protected function updatePermissionsForRole($options)
    {
        if (!isset($options['name'])) {
            throw new MissingArgumentException('You must provide the name of the role you want to update the permissions of');
        }

        if ((!isset($options['permissions_to_add']) || empty($options['permissions_to_add'])) && (!isset($options['permissions_to_remove']) || empty($options['permissions_to_remove']))) {
            throw new MissingArgumentException('permissions_to_add and permissions_to_remove cannot both be empty');
        }

        $role_name = rawurlencode($options['name']);
        $scope = $options['scope'];

        $body = [];
        if (isset($options['permissions_to_add']) && !empty($options['permissions_to_add'])) {
            $body['add_permissions'] = $options['permissions_to_add'];
        }
        if (isset($options['permissions_to_remove']) && !empty($options['permissions_to_remove'])) {
            $body['remove_permissions'] = $options['permissions_to_remove'];
        }

        return $this->authorizerRequest([
            'method' => 'PUT',
            'path' => "/roles/$role_name/scope/$scope/permissions",
            'jwt' => $this->getServerToken()['token'],
            'body' => $body
        ]);
    }

    /**
     * @param $token
     * @param $room_id
     * @param $file_part
     * @return mixed
     * @throws ConfigurationException
     * @throws ConnectionExceptionAlias
     * @throws MissingArgumentException
     * @throws UploadException
     * @throws ChatkitException
     */
    protected function uploadAttachment($token, $room_id, $file_part) {
        $body = $file_part['file'];
        $content_length = strlen($body);
        $content_type = $file_part['type'];

        if ($content_length <= 0 || is_null($body)) {
            throw new MissingArgumentException('File contents size must be greater than 0');
        }

        $attachment_req = [ 'content_type' => $content_type,
                            'content_length' => $content_length ];

        foreach (['origin', 'name', 'customData'] as $field_name) {
            if (isset($file_part[$field_name])) {
                $attachment_req[$field_name] = $file_part[$field_name];
            }
        }

        $attachment_response =  $this->apiRequest([
            'method' => 'POST',
            'path' => "/rooms/$room_id/attachments",
            'jwt' => $token,
            'body' => $attachment_req
        ]);

        $url = $attachment_response['body']['upload_url'];
        $ch = $this->createRawCurl('PUT', $url, $body, $content_type);
        $upload_response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if($status !== 200) {
            throw (new UploadException('Failed to upload attachment', $status))->setBody($upload_response['body']);
        }

        $attachment_id = $attachment_response['body']['attachment_id'];
        return $attachment_id;
    }

    /**
     * @param $field_names
     * @param $options
     * @return array
     */
    protected function getOptionalFields($field_names, $options) {
        $fields = [];
        foreach ($field_names as $field_name) {
            if(isset($options[$field_name])) {
                $fields[$field_name] = $options[$field_name];
            }
        }

        return $fields;
    }

    /**
     * Utility function used to create the curl object setup to interact with the Pusher API
     *
     * @param $service_settings
     * @param $path
     * @param $jwt
     * @param $request_method
     * @param null $body
     * @param array $query_params
     * @return false|resource|null
     * @throws ConfigurationException
     */
    protected function createCurl($service_settings, $path, $jwt, $request_method, $body = null, $query_params = array())
    {
        $split_instance_locator = explode(':', $this->settings['instance_locator']);

        $scheme = 'https';
        $host = $split_instance_locator[1].'.pusherplatform.io';
        $service_path_fragment = $service_settings['service_name'].'/'.$service_settings['service_version'];
        $instance_id = $split_instance_locator[2];

        $full_url = $scheme.'://'.$host.'/services/'.$service_path_fragment.'/'.$instance_id.$path;
        $query = http_build_query($query_params);
        // Passing foo = [1, 2, 3] to query params will encode it as foo[0]=1&foo[1]=2
        // however, we want foo=1&foo=2 (to treat them as an array)
        $query_string = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $query);
        $final_url = $full_url.'?'.$query_string;

        $this->log('INFO: createCurl( '.$final_url.' )');

        return $this->createRawCurl($request_method, $final_url, $body, null, $jwt, true);
    }


    /**
     * Utility function used to create the curl object with common settings.
     *
     * @param $request_method
     * @param $url
     * @param null $body
     * @param null $content_type
     * @param null $jwt
     * @param bool $encode_json
     * @return false|resource|null
     * @throws ConfigurationException
     */
    protected function createRawCurl($request_method, $url, $body = null, $content_type = null, $jwt = null, $encode_json = false)
    {
        // Create or reuse existing curl handle
        if (null === $this->ch) {
            $this->ch = curl_init();
        }

        if ($this->ch === false) {
            throw new ConfigurationException('Could not initialise cURL!');
        }

        $ch = $this->ch;

        // curl handle is not reusable unless reset
        if (function_exists('curl_reset')) {
            curl_reset($ch);
        }

        $headers = array();

        if(!is_null($jwt)) {
            array_push($headers, 'Authorization: Bearer '.$jwt);
        }
        if(!is_null($content_type)) {
            array_push($headers, 'Content-Type: '.$content_type);
        }
        // Set cURL opts and execute request
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->settings['timeout']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_method);

        if (!is_null($body)) {
            if ($encode_json) {
                $body = json_encode($body, JSON_ERROR_UTF8);
                array_push($headers, 'Content-Type: application/json');
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            array_push($headers, 'Content-Length: '.strlen($body));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Set custom curl options
        if (!empty($this->settings['curl_options'])) {
            foreach ($this->settings['curl_options'] as $option => $value) {
                curl_setopt($ch, $option, $value);
            }
        }

        return $ch;
    }

    /**
     * @param array $options
     * @return array
     * @throws MissingArgumentException
     * @throws TypeMismatchException
     */
    protected function getServerToken($options = [])
    {
        $token_options = [ 'su' => true ];
        if (isset($options['user_id']) && !is_null($options['user_id'])) {
            $token_options['user_id'] = $options['user_id'];
        }
        return $this->generateAccessToken($token_options);
    }


    /**
     * Utility function to execute curl and create capture response information.
     *
     * @param $ch
     * @return array
     * @throws ConnectionExceptionAlias
     * @throws ChatkitException
     */
    protected function execCurl($ch)
    {
        $headers = [];
        $response = [];

        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
          function($curl, $header) use (&$headers)
          {
              $len = strlen($header);
              $header = explode(':', $header, 2);
              if (count($header) < 2) {
                  return $len;
              }

              $name = strtolower(trim($header[0]));
              if (!array_key_exists($name, $headers)) {
                  $headers[$name] = [trim($header[1])];
              } else {
                  $headers[$name][] = trim($header[1]);
              }

            return $len;
          }
        );

        $data = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $body = empty($data) ? null : json_decode($data, true);

        $response = [
            'status' => $status,
            'headers' => $headers,
            'body' => $body
        ];

        // inform the user of a connection failure
        if ($status == 0 || $response['body'] === false) {
            throw new ConnectionExceptionAlias(curl_error($ch));
        }

        // or an error response from Chatkit
        if ($status >= 400) {
            $this->log('ERROR: execCurl error: '.print_r($response, true));
            throw (new ChatkitException($response['body']['error_description'], $status))->setBody($response['body']);
        }

        $this->log('INFO: execCurl response: '.print_r($response, true));
        return $response;
    }

};

const OPTIONAL_STRING = [ 'type' => 'string', "optional" => true ];
const OPTIONAL_INT = [ 'type' => 'int', "optional" => true ];

const ROOM_ID = [ 'room_id' =>
                  [ 'type' => 'string',
                    'missing_message' =>
                    'You must provide the ID of the room'
                  ]
];
const SENDER_ID = [ 'sender_id' =>
                    [ 'type' => 'string',
                      'missing_message' =>
                      'You must provide the ID of the user sending the message'
                    ]
];

const MESSAGE_ID = [ 'message_id' =>
                     [ 'type' => 'int',
                       'missing_message' =>
                       'You must provide the ID of the message'
                     ]
];

/**
 * @param $fields
 * @param $options
 * @throws MissingArgumentException
 * @throws TypeMismatchException
 */
function verify($fields, $options) {
    foreach ($fields as $field) {
        $name = key($field);
        $rules = $field[$name];

        if (!isset($options[$name]) && !isset($rules['optional'])) {
            throw new MissingArgumentException($rules['missing_message']);
        } elseif (isset($options[$name])) {
            switch ($rules['type']) {
            case 'string':
                if (!is_string($options[$name])) {
                    throw new TypeMismatchException($options[$name]." must be of type string");
                }
                break;
            case 'int':
                if (!is_int($options[$name]) || $options[$name] < 0) {
                    throw new TypeMismatchException($options[$name]." must be a positive int");
                }
                break;
            case 'non_empty_array':
                if (!is_array($options[$name]) || empty($options[$name])) {
                    throw new TypeMismatchException($options[$name]." must be a non-empty array");
                }
                break;
            default:
                break;

            }
        }
    }
}
