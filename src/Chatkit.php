<?php

namespace Chatkit;

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
     * Initializes a new Chatkit instance with instalce_locator and key.
     * You can optionally turn on debugging for all requests by setting debug to true.
     *
     * @param string $instance_locator
     * @param string $key
     * @param array  $options          [optional]
     *                                 Options to configure the Chatkit instance.
     *                                 scheme - e.g. http or https
     *                                 host - the host; no trailing forward slash.
     *                                 port - the http port
     *                                 timeout - the http timeout
     */
    public function __construct($instance_locator, $key, $options = array())
    {
        $this->checkCompatibility();

        $this->settings['instance_locator'] = $instance_locator;
        $this->settings['key'] = $key;
        $this->api_settings['service_name'] = "chatkit";
        $this->api_settings['service_version'] = "v1";
        $this->authorizer_settings['service_name'] = "chatkit_authorizer";
        $this->authorizer_settings['service_version'] = "v1";

        foreach ($options as $key => $value) {
            // only set if valid setting/option
            if (isset($this->settings[$key])) {
                $this->settings[$key] = $value;
            }
        }
    }

    public function generateTokenPair($auth_options)
    {
        $access_token = $this->generateAccessToken($auth_options);
        $refresh_token = $this->generateRefreshToken($auth_options);

        return array(
          "access_token" => $access_token,
          "token_type" => "bearer",
          "expires_in" => 24 * 60 * 60,
          "refresh_token" => $refresh_token
        );
    }

    public function generateAccessToken($auth_options)
    {
        return $this->generateToken($auth_options);
    }

    public function generateRefreshToken($auth_options)
    {
        $merged_auth_options = array(
            "refresh" => true
        );
        foreach ($auth_options as $key => $value) {
            $merged_auth_options[$key] = $value;
        }
        return $this->generateToken($merged_auth_options);
    }

    public function generateToken($auth_options)
    {
        $split_instance_locator = explode(":", $this->settings['instance_locator']);
        $split_key = explode(":", $this->settings['key']);

        JWT::$leeway = 60;

        $now = time();
        $claims = array(
            "instance" => $split_instance_locator[2],
            "iss" => "api_keys/".$split_key[0],
            "iat" => $now
        );

        if (isset($auth_options['user_id'])) {
            $claims['sub'] = $auth_options['user_id'];
        }
        if (isset($auth_options['refresh']) && $auth_options['refresh'] === true) {
            $claims['refresh'] = true;
        } else {
            if (isset($auth_options['su']) && $auth_options['su'] === true) {
                $claims['su'] = true;
            }
            $claims['exp'] = strtotime('+1 day', $now);
        }

        $jwt = JWT::encode($claims, $split_key[1]);

        $token_payload = array(
            "token" => $jwt,
            "expires_in" => 24 * 60 * 60
        );

        return $jwt;
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
            throw new ChatkitException('The Chatkit library requires the PHP cURL module. Please ensure it is installed');
        }

        if (!extension_loaded('json')) {
            throw new ChatkitException('The Chatkit library requires the PHP JSON module. Please ensure it is installed');
        }
    }


    public function createUser($id, $name, $avatar_url = null, $custom_data = null)
    {
        $body = array(
            "id" => $id,
            "name" => $name
        );

        if (!is_null($avatar_url)) {
            $body['avatar_url'] = $avatar_url;
        }

        if (!is_null($custom_data)) {
            $body['custom_data'] = $custom_data;
        }

        $ch = $this->createCurl(
            $this->api_settings,
            "/users",
            $this->getServerToken(),
            "POST",
            $body
        );

        $response = $this->execCurl($ch);
        return $response;
    }

    public function updateUser($id, $name = null, $avatar_url = null, $custom_data = null)
    {
        $body = array();

        if (!is_null($name)) {
            $body['name'] = $name;
        }
        if (!is_null($avatar_url)) {
            $body['avatar_url'] = $avatar_url;
        }
        if (!is_null($custom_data)) {
            $body['custom_data'] = $custom_data;
        }

        if (empty($body)) {
            throw new ChatkitException('At least one of the following are required: name, avatar_url, or custom_data.');
        }

        $token = $this->generateToken(array(
            'user_id' => $id,
            'su' => true
        ));

        $ch = $this->createCurl(
            $this->api_settings,
            "/users/" . $id,
            $token,
            "PUT",
            $body
        );

        $response = $this->execCurl($ch);
        return $response;
    }

    /**
     * Creates a new room.
     *
     * @param array $options  The room options
     *                          [Available Options]
     *                          • name (string|optional): Represents the name with which the room is identified.
     *                              A room name must not be longer than 40 characters and can only contain lowercase letters,
     *                              numbers, underscores and hyphens.
     *                          • private (boolean|optional): Indicates if a room should be private or public. Private by default.
     *                          • user_ids (array|optional): If you wish to add users to the room at the point of creation,
     *                              you may provide their user IDs.
     * @return array
     */
    public function createRoom($user_id, array $options = [])
    {
        if (is_null($user_id)) {
            throw new ChatkitException('You must provide the ID of the user that you wish to create the room');
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
            $this->getServerToken($user_id),
            'POST',
            $body
        );

        return $this->execCurl($ch);
    }

    public function sendMessage($id, $room_id, $text)
    {
        $body = array(
            'text' => $text
        );

        if (empty($body['text'])) {
            throw new ChatkitException('A message text is required.');
        }

        $token = $this->generateToken(array(
            'user_id' => $id
        ));

        $ch = $this->createCurl(
            $this->api_settings,
            '/rooms/' . $room_id . '/messages',
            $token,
            'POST',
            $body
        );

        $response = $this->execCurl($ch);
        return $response;
    }

    public function deleteUser($user_id)
    {
        $token = $this->getServerToken($user_id);

        $ch = $this->createCurl(
            $this->api_settings,
            '/users/' . $user_id,
            $token,
            'DELETE'
        );

        $response = $this->execCurl($ch);
        return $response;
    }

    public function getUsersByIds($user_ids)
    {
        $token = $this->getServerToken();
        $user_ids_string = implode(',', $user_ids);

        $ch = $this->createCurl(
            $this->api_settings,
            '/users_by_ids' . '?user_ids=' . $user_ids_string,
            $token,
            'GET'
        );

        $response = $this->execCurl($ch);
        return $response;
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
            throw new ChatkitException('Could not initialise cURL!');
        }

        $ch = $this->ch;

        // curl handle is not reusable unless reset
        if (function_exists('curl_reset')) {
            curl_reset($ch);
        }

        // Set cURL opts and execute request
        curl_setopt($ch, CURLOPT_URL, $full_url);
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

    protected function getServerToken($user_id = null)
    {
        $token_options = array("su" => true);
        if (!is_null($user_id)) {
            $token_options['user_id'] = $user_id;
        }
        return $this->generateAccessToken($token_options);
    }

    /**
     * Utility function to execute curl and create capture response information.
     */
    protected function execCurl($ch)
    {
        $response = array();

        $response['body'] = curl_exec($ch);
        $response['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response['body'] === false || $response['status'] < 200 || 400 <= $response['status']) {
            $this->log('ERROR: execCurl error: '.curl_error($ch));
        }

        $this->log('INFO: execCurl response: '.print_r($response, true));

        return $response;
    }
}
