<?php

namespace Chatkit;

use Chatkit\Exceptions\ChatkitException;
use Chatkit\Exceptions\ConfigurationException;
use Chatkit\Exceptions\ConnectionException;
use Chatkit\Exceptions\MissingArgumentException;
use Chatkit\Exceptions\TypeMismatchException;
use \Firebase\JWT\JWT;

class Chatkit
{
    protected $settings = array(
        'scheme'       => 'https',
        'port'         => 80,
        'timeout'      => 30,
        'debug'        => false,
        'curl_options' => array(),
    );
    protected $logger = null;
    protected $ch = null; // Curl handler

    protected $api_settings = array();
    protected $authorizer_settings = array();

    /**
     *
     * Initializes a new Chatkit instance.
     *
     *
     * @param array $options   Options to configure the Chatkit instance.
     *                         instance_locator - your Chatkit instance locator
     *                         key - your Chatkit instance's key
     *                         scheme - e.g. http or https
     *                         host - the host; no trailing forward slash.
     *                         port - the http port
     *                         timeout - the http timeout
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
        $this->api_settings['service_name'] = "chatkit";
        $this->api_settings['service_version'] = "v1";
        $this->authorizer_settings['service_name'] = "chatkit_authorizer";
        $this->authorizer_settings['service_version'] = "v1";
        $this->cursor_settings['service_name'] = "chatkit_cursors";
        $this->cursor_settings['service_version'] = "v1";

        foreach ($options as $key => $value) {
            // only set if valid setting/option
            if (isset($this->settings[$key])) {
                $this->settings[$key] = $value;
            }
        }
    }

    public function authenticate($auth_options)
    {
        if (!isset($auth_options['user_id'])) {
            throw new MissingArgumentException('You must provide a user ID');
        }

        $access_token = $this->generateAccessToken($auth_options);

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

    public function generateAccessToken($auth_options)
    {
        return $this->generateToken($auth_options);
    }

    public function generateToken($auth_options)
    {
        $split_instance_locator = explode(":", $this->settings['instance_locator']);
        $split_key = explode(":", $this->settings['key']);

        $now = time();
        $claims = array(
            "instance" => $split_instance_locator[2],
            "iss" => "api_keys/".$split_key[0],
            "iat" => $now
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

        $claims['exp'] = strtotime('+1 day', $now);

        return JWT::encode($claims, $split_key[1]);
    }

    /**
     * Set a logger to be informed of internal log messages.
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
     * @throws ChatkitException if any required dependencies are missing
     *
     * @return void
     */
    protected function checkCompatibility()
    {
        if (!extension_loaded('curl')) {
            throw new ConfigurationException('The Chatkit library requires the PHP cURL module. Please ensure it is installed');
        }

        if (!extension_loaded('json')) {
            throw new ConfigurationException('The Chatkit library requires the PHP JSON module. Please ensure it is installed');
        }
    }


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
            "id" => $options['id'],
            "name" => $options['name']
        );

        if (isset($options['avatar_url']) && !is_null($options['avatar_url'])) {
            $body['avatar_url'] = $options['avatar_url'];
        }

        if (isset($options['custom_data']) && !is_null($options['custom_data'])) {
            $body['custom_data'] = $options['custom_data'];
        }

        $ch = $this->createCurl(
            $this->api_settings,
            "/users",
            $this->getServerToken(),
            "POST",
            $body
        );

        return $this->execCurl($ch);
    }

    public function updateUser($options)
    {
        if (!isset($options['id'])) {
            throw new MissingArgumentException('You must provide an ID');
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

        $user_id = $options['id'];

        $token = $this->generateToken([
            'user_id' => $user_id,
            'su' => true
        ]);

        $ch = $this->createCurl(
            $this->api_settings,
            "/users/" . $user_id,
            $token,
            "PUT",
            $body
        );

        return $this->execCurl($ch);
    }

    /**
     * Creates a new room.
     *
     * @param array $options  The room options
     *                          [Available Options]
     *                          • creator_id (string|required): Represents the ID of the user that you want to create the room.
     *                          • name (string|optional): Represents the name with which the room is identified.
     *                              A room name must not be longer than 40 characters and can only contain lowercase letters,
     *                              numbers, underscores and hyphens.
     *                          • private (boolean|optional): Indicates if a room should be private or public. Private by default.
     *                          • user_ids (array|optional): If you wish to add users to the room at the point of creation,
     *                              you may provide their user IDs.
     * @return array
     */
    public function createRoom($options)
    {
        if (is_null($options['creator_id'])) {
            throw new MissingArgumentException('You must provide the ID of the user that you wish to create the room');
        }
        $body = [];

        if (isset($options['name'])) {
            $body['name'] = (string) $options['name'];
        }

        if (isset($options['private'])) {
            $body['private'] = (bool) $options['private'];
        }

        if (isset($options['user_ids'])) {
            $body['user_ids'] = (array) $options['user_ids'];
        }

        $ch = $this->createCurl(
            $this->api_settings,
            '/rooms',
            $this->getServerToken([ 'user_id' => $options['creator_id'] ]),
            'POST',
            $body
        );

        return $this->execCurl($ch);
    }


    /**
     * @param $options
     * include include_private= true to return private rooms also
     * @return array
     * @throws ConfigurationException
     * @throws ConnectionException
     */
    public function getRooms($options)
    {
        $queryParams = isset($options['include_private']) ? ['include_private' => $options['include_private']] : [];

        $ch = $this->createCurl(
            $this->api_settings,
            "/rooms",
            $this->getServerToken(),
            'GET',
            null,
            $queryParams
        );

        return $this->execCurl($ch);
    }

    /**
     * Deletes a room given a room_id
     */
    public function deleteRoom($options)
    {
        if (is_null($options['room_id'])) {
            throw new MissingArgumentException('You must provide the ID of the room that you wish to delete');
        }

        $room_id = $options['room_id'];
        $token = $this->getServerToken();

        $ch = $this->createCurl(
            $this->api_settings,
            '/rooms/' . $room_id,
            $token,
            'DELETE'
        );

        return $this->execCurl($ch);
    }

    /**
     * Join a given user to a given room
     *
     * @param array $options
     *                          [Available Options]
     *                          • user_id (string|required): Represents the ID of the user that you want to join to the room.
     *                          • room_id (integer|required): Represents the room_id with which the room is identified.
     *
     * @throws ChatkitException if any required dependencies are missing
     *
     * @return array
     */
    public function joinRoom($options)
    {
        if (!isset($options['user_id'])) {
            throw new MissingArgumentException('You must provide a User ID');
        }

        if (gettype($options['user_id']) != 'string') {
            throw new TypeMismatchException('User ID must be a string');
        }

        if (!isset($options['room_id'])) {
            throw new MissingArgumentException('You must provide a Room ID');
        }

        $userId = $options['user_id'];

        $roomId = $options['room_id'];

        $token = $this->getServerToken();

        $ch = $this->createCurl(
            $this->api_settings,
            '/users/' . $userId . '/rooms/' . $roomId . '/join',
            $token,
            'POST',
            null
        );

        return $this->execCurl($ch);
    }

    /**
     * Add given users to a given room
     *
     * @param array $options
     *                          [Available Options]
     *                          • user_ids (array|required): Represents the IDs of the users that you want to add to the room.
     *                          • room_id (integer|required): Represents the room_id with which the room is identified.
     *
     * @throws ChatkitException if any required dependencies are missing
     *
     * @return array
     */
    public function addUsersToRoom($options)
    {
        if (!isset($options['room_id'])) {
            throw new MissingArgumentException('You must provide a Room ID');
        }

        if (!isset($options['user_ids'])) {
            throw new MissingArgumentException('You must provide a User ID(s) to add them to a room');
        }

        $roomId = $options['room_id'];

        $body = [];
        $body['user_ids'] = $options['user_ids'];

        $token = $this->getServerToken();

        $ch = $this->createCurl(
            $this->api_settings,
            '/rooms/' . $roomId . '/users/add',
            $token,
            'PUT',
            $body
        );

        return $this->execCurl($ch);
    }


    /**
     * Remove given users from a given room
     *
     * @param array $options
     *                          [Available Options]
     *                          • user_ids (array|required): Represents the IDs of the users that you want remove from the room.
     *                          • room_id (integer|required): Represents the room_id with which the room is identified.
     *
     * @throws ChatkitException if any required dependencies are missing
     *
     * @return array
     */
    public function removeUsersFromRoom($options)
    {
        if (!isset($options['room_id'])) {
            throw new MissingArgumentException('You must provide a Room ID');
        }

        if (!isset($options['user_ids'])) {
            throw new MissingArgumentException('You must provide a User ID(s) to remove them from a room');
        }

        $roomId = $options['room_id'];

        $body = [];
        $body['user_ids'] = $options['user_ids'];

        $token = $this->getServerToken();

        $ch = $this->createCurl(
            $this->api_settings,
            '/rooms/' . $roomId . '/users/remove',
            $token,
            'PUT',
            $body
        );

        return $this->execCurl($ch);
    }

    /**
     * Get all read cursors for a user
     *
     * @param array $options
     *              [Available Options]
     *              • user_id (string|required): Represents the ID of the user that you want to get the rooms for.
     * @return array
     * @throws ChatkitException or MissingArgumentException
     */
    public function getUserReadCursors($options)
    {
        if (is_null($options['user_id'])) {
            throw new MissingArgumentException('You must provide the ID of the user that you want to get the cursors for');
        }

        $user_id = $options['user_id'];

        $ch = $this->createCurl(
            $this->cursor_settings,
            "/cursors/0/users/$user_id",
            $this->getServerToken([ 'user_id' => $user_id ]),
            'GET'
        );

        return $this->execCurl($ch);
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
     * @throws ChatkitException or MissingArgumentException
     */
    public function setReadCursor($options)
    {
        if (is_null($options['user_id'])) {
            throw new MissingArgumentException('You must provide the ID of the user that you want to get the cursor for');
        }
        if (is_null($options['room_id'])) {
            throw new MissingArgumentException('You must provide the room ID of the room that you want to set the cursor for');
        }
        if (is_null($options['position'])) {
            throw new MissingArgumentException('You must provide the position of the cursor');
        }

        $user_id = $options['user_id'];
        $room_id = $options['room_id'];
        $body = ['position' => $options['position']];

        $ch = $this->createCurl(
            $this->cursor_settings,
            "/cursors/0/rooms/$room_id/users/$user_id",
            $this->getServerToken([ 'user_id' => $user_id ]),
            'PUT',
            $body
        );

        return $this->execCurl($ch);
    }

    /**
     * Get all rooms a user belongs to
     *
     * @param array $options
     *              [Available Options]
     *              • user_id (string|required): Represents the ID of the user that you want to get the rooms for.
     *              • joinable (bool|optional): Indicates if you only want the joinable rooms returned for the given user.
     * @return array
     * @throws ChatkitException or MissingArgumentException
     */
    public function getUserRooms($options)
    {
        if (is_null($options['user_id'])) {
            throw new MissingArgumentException('You must provide the ID of the user that you want to get the rooms for');
        }

        $user_id = $options['user_id'];

        $queryParams = isset($options['joinable']) ? ['joinable' => $options['joinable']] : [];
        $ch = $this->createCurl(
            $this->api_settings,
            "/users/$user_id/rooms",
            $this->getServerToken([ 'user_id' => $user_id ]),
            'GET',
            null,
            $queryParams
        );

        return $this->execCurl($ch);
    }

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
     * @throws ChatkitException or MissingArgumentException
     */
    public function getRoomMessages($options)
    {
        if (is_null($options['room_id'])) {
            throw new MissingArgumentException('You must provide the ID of the room that you want to get the messages for');
        }

        $queryParams = [];
        if (!empty($options['initial_id'])) {
            $queryParams['initial_id'] = $options['initial_id'];
        }
        if (!empty($options['limit'])) {
            $queryParams['limit'] = $options['limit'];
        }
        if (!empty($options['direction'])) {
            $queryParams['direction'] = $options['direction'];
        }

        $room_id = $options['room_id'];

        $ch = $this->createCurl(
            $this->api_settings,
            "/rooms/$room_id/messages",
            $this->getServerToken(),
            'GET',
            null,
            $queryParams
        );

        return $this->execCurl($ch);
    }

    public function sendMessage($options)
    {
        if (is_null($options['sender_id'])) {
            throw new MissingArgumentException('You must provide the ID of the user that you want to set as the sender of the message');
        }
        if (is_null($options['room_id'])) {
            throw new MissingArgumentException('You must provide the ID of the room that you want to add the message to');
        }
        if (is_null($options['text'])) {
            throw new MissingArgumentException('You must provide some text for the message');
        }
        $user_id = $options['sender_id'];
        $room_id = $options['room_id'];
        $text = $options['text'];

        $body = array(
            'text' => $text
        );

        if (isset($options['attachment'])) {
            if (is_null($options['attachment']['resource_link'])) {
                throw new MissingArgumentException('You must provide the resource_link for the attachment');
            }
            if (is_null($options['attachment']['type']) || !in_array($options['attachment']['type'], array('image', 'video', 'audio', 'file'))) {
                throw new MissingArgumentException('You must provide the type for the attachment. This can be one of image, video, audio or file');
            }

            $body['attachment'] = array(
                'resource_link' => $options['attachment']['resource_link'],
                'type' => $options['attachment']['type']
            );
        }

        $token = $this->generateToken(array(
            'user_id' => $user_id
        ));

        $ch = $this->createCurl(
            $this->api_settings,
            '/rooms/' . $room_id . '/messages',
            $token,
            'POST',
            $body
        );

        return $this->execCurl($ch);
    }

    public function deleteUser($options)
    {
        if (is_null($options['id'])) {
            throw new MissingArgumentException('You must provide the ID of the user that you wish to delete');
        }

        $user_id = $options['id'];

        $token = $this->getServerToken([ 'user_id' => $user_id ]);

        $ch = $this->createCurl(
            $this->api_settings,
            '/users/' . $user_id,
            $token,
            'DELETE'
        );

        return $this->execCurl($ch);
    }

    /**
     * $options['from_ts'] should be in the B8601DZw.d format
     *
     * e.g. 2018-04-17T14:02:00Z
     */
    public function getUsers($options = [])
    {
        $token = $this->getServerToken();
        $path = '/users';
        $queryParams = [];

        if (!empty($options['from_ts'])) {
            $queryParams['from_ts'] = $options['from_ts'];
        }

        $ch = $this->createCurl(
            $this->api_settings,
            $path,
            $token,
            'GET',
            null,
            $queryParams
        );

        return $this->execCurl($ch);
    }

    public function getUsersByIds($options)
    {
        $token = $this->getServerToken();
        $userIDsString = implode(',', $options['user_ids']);

        $ch = $this->createCurl(
            $this->api_settings,
            '/users_by_ids',
            $token,
            'GET',
            null,
            [ 'user_ids' => $userIDsString ]
        );

        return $this->execCurl($ch);
    }

    /**
     * Utility function used to create the curl object with common settings.
     */
    protected function createCurl($service_settings, $path, $jwt, $request_method, $body = null, $query_params = array())
    {
        $split_instance_locator = explode(":", $this->settings['instance_locator']);

        $scheme = "https";
        $host = $split_instance_locator[1].".pusherplatform.io";
        $service_path_fragment = $service_settings['service_name']."/".$service_settings['service_version'];
        $instance_id = $split_instance_locator[2];

        $full_url = $scheme."://".$host."/services/".$service_path_fragment."/".$instance_id.$path;
        $query_string = http_build_query($query_params);
        $final_url = $full_url."?".$query_string;

        $this->log('INFO: createCurl( '.$final_url.' )');

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

        // Set cURL opts and execute request
        curl_setopt($ch, CURLOPT_URL, $final_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            "Authorization: Bearer ".$jwt
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->settings['timeout']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_method);

        if (!is_null($body)) {
            $json_encoded_body = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_encoded_body);
        }

        // Set custom curl options
        if (!empty($this->settings['curl_options'])) {
            foreach ($this->settings['curl_options'] as $option => $value) {
                curl_setopt($ch, $option, $value);
            }
        }

        return $ch;
    }

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
     */
    protected function execCurl($ch)
    {
        $response = array();

        $response['body'] = json_decode(curl_exec($ch), true);

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response['status'] = $status;

        // inform the user of a connection failure
        if ($status == 0 || $response['body'] === false) {
            throw new ConnectionException(curl_error($ch));
        }

        // or an error response from Chatkit
        if ($status >= 400) {
            $this->log('ERROR: execCurl error: '.print_r($response, true));
            throw (new ChatkitException($response['body']['error_description'], $status))->setBody($response['body']);
        }

        $this->log('INFO: execCurl response: '.print_r($response, true));
        return $response;
    }
}
