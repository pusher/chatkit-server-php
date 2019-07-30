<?php

class Base extends \PHPUnit_Framework_TestCase {

    protected function setUp() {
        if (CHATKIT_INSTANCE_LOCATOR === '' || CHATKIT_INSTANCE_KEY === '') {
            $this->markTestSkipped('Please set the CHATKIT_INSTANCE_LOCATOR and CHATKIT_INSTANCE_KEY values.');
        } else {
            $this->chatkit = new Chatkit\Chatkit([
                'instance_locator' => CHATKIT_INSTANCE_LOCATOR,
                'key' => CHATKIT_INSTANCE_KEY
            ]);

            $this->chatkit->apiRequest([
                'method' => 'DELETE',
                'path' => '/resources',
                'jwt' => $this->chatkit->generateSuToken()['token']
            ]);
        }
    }

    protected function guidv4($data) {
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    protected function extractID($value) {
        return $value['id'];
    }

    protected function extractName($value) {
        return $value['name'];
    }

    protected function extractFromArrayByID($ID, $array) {
        foreach($array as $elem) {
            if ($elem['id'] == $ID) {
                return $elem;
            }
        }
        return null;
    }

    protected function makeUser() {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        return $user_id;
    }

    protected function makeRoom($creator, $extra_members = []) {
        $room_res = $this->chatkit->createRoom([
            'creator_id' => $creator,
            'name' => 'my room'
        ]);
        $this->assertEquals($room_res['status'], 201);
        $room_id = $room_res['body']['id'];

        if (sizeof($extra_members) > 0) {
            $add_res = $this->chatkit->addUsersToRoom([
                'room_id' => $room_id,
                'user_ids' => $extra_members
            ]);
            $this->assertEquals(204, $add_res['status']);
        }

        return $room_id;
    }

    protected function makeMessages($room_id, $messages) {
        $ids = [];
        foreach ($messages as $message) {
            $sender_id = key($message);
            $content = $message[$sender_id];
            $send_msg_res = $this->chatkit->sendSimpleMessage([
                'sender_id' => $sender_id,
                'room_id' => $room_id,
                'text' => $content
            ]);
            $this->assertEquals($send_msg_res['status'], 201);

            $ids[$send_msg_res['body']['message_id']] = $content;
        }
        return $ids;
    }

    protected function payloadContainsValueForKey($payload, $key, $value)
    {
        foreach ($payload as $p) {
            foreach ($p as $k => $v) {
                if ($key === $k && $value === $v) {
                    $this->assertEquals($value, $v);
                    return;
                }
            }
        }
    }
};

// useful for debugging tests
function debug($var) {
    fwrite(STDERR, print_r($var, TRUE));
}
