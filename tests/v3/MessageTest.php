<?php

class MessageTest extends \Base {

    public function testSendMultipartMessageShouldReturnAResponsePayloadIfPartsProvided()
    {
        $user_id = $this->makeUser();
        $room_id = $this->makeRoom($user_id);

        $parts = [ ['type' => 'text/plain',
                    'content' => 'testing'],
                   ['type' => 'image/png',
                    'url' => 'https://example.com/image.png']
        ];

        $send_msg_res = $this->chatkit->sendMultipartMessage([
            'sender_id' => $user_id,
            'room_id' => $room_id,
            'parts' => $parts
        ]);
        $this->assertEquals($send_msg_res['status'], 201);
        $this->assertArrayHasKey('message_id', $send_msg_res['body']);
    }

    public function testSendMultipartMessagesRaisesAnExceptionIfWrongTypesAreProvided()
    {
        $good_parts = [ ['type' => 'binary/octet-stream',
                         'content' => 'test' ]
        ];

        $bad_parts = [ ['type' => 'binary/octet-stream',
                        'content' => 42 ]
        ];

        $this->expectException(Chatkit\Exceptions\TypeMismatchException::class);
        $this->chatkit->sendMultipartMessage([ 'sender_id' => 'user_id',
                                               'room_id' => 42,
                                               'parts' => $good_parts
        ]);

        $this->expectException(Chatkit\Exceptions\TypeMismatchException::class);
        $this->chatkit->sendMultipartMessage([ 'sender_id' => 42,
                                               'room_id' => 'room_id',
                                               'parts' => $good_parts
        ]);

        $this->expectException(Chatkit\Exceptions\TypeMismatchException::class);
        $this->chatkit->sendMultipartMessage([ 'sender_id' => 'user_id',
                                               'room_id' => 'room_id',
                                               'parts' => $bad_parts
        ]);
    }

    public function testSendMultipartMessageShouldReturnAResponsePayloadIfAttachmentsProvided()
    {
        $user_id = $this->makeUser();
        $room_id = $this->makeRoom($user_id);

        $file = openssl_random_pseudo_bytes(100);
        $file_name = 'a broken image';

        $parts = [ ['type' => 'image/png',
                    'file' => $file,
                    'name' => $file_name,
                    'customData' => [ 'some' =>
                                      [ 'nested' => 'data',
                                        'number' => 42
                                      ] ],
                    'origin' => 'http://example.com' ]
        ];

        $send_msg_res = $this->chatkit->sendMultipartMessage([
            'sender_id' => $user_id,
            'room_id' => $room_id,
            'parts' => $parts
        ]);
        $this->assertEquals($send_msg_res['status'], 201);
        $this->assertArrayHasKey('message_id', $send_msg_res['body']);
    }

    public function testSendSimpleMessageShouldReturnAResponsePayloadIfARoomIDSenderIDAndTextAreProvided()
    {
        $user_id = $this->makeUser();
        $room_id = $this->makeRoom($user_id);

        $send_msg_res = $this->chatkit->sendSimpleMessage([
            'sender_id' => $user_id,
            'room_id' => $room_id,
            'text' => 'testing'
        ]);
        $this->assertEquals($send_msg_res['status'], 201);
        $this->assertArrayHasKey('message_id', $send_msg_res['body']);
    }

    public function testFetchMultipartMessagesRaisesAnExceptionIfNoRoomIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->fetchMultipartMessages([]);
    }

    public function testFetchMultipartMessagesRaisesAnExceptionIfWrongTypesAreProvided()
    {
        $this->expectException(Chatkit\Exceptions\TypeMismatchException::class);
        $this->chatkit->fetchMultipartMessages([ 'room_id' => 42]);

        $this->expectException(Chatkit\Exceptions\TypeMismatchException::class);
        $this->chatkit->fetchMultipartMessages([ 'room_id' => 'correct',
                                                 'limit' => 'incorrect']);
    }

    public function testFetchMultipartMessagesShouldReturnAResponsePayloadIfARoomIDIsProvided()
    {
        $user_id = $this->makeUser();
        $room_id = $this->makeRoom($user_id);

        $messages = $this->makeMessages($room_id, [ [$user_id => 'hi 1'],
                                                    [$user_id => 'hi 2'] ]);

        $get_msg_res = $this->chatkit->fetchMultipartMessages([ 'room_id' => $room_id ]);
        $this->assertEquals(200, $get_msg_res['status']);
        $this->assertEquals(count($messages), count($get_msg_res['body']));

        $parts = [ $get_msg_res['body'][0]['parts'][0],
                   $get_msg_res['body'][1]['parts'][0] ];

        foreach ( [0, 1] as $idx) {
            $this->assertEquals($room_id, $get_msg_res['body'][$idx]['room_id']);
            $this->assertEquals($user_id, $get_msg_res['body'][$idx]['user_id']);
            $this->assertEquals('text/plain', $parts[$idx]['type']);

            $message_id = $get_msg_res['body'][$idx]['id'];
            $this->assertEquals($messages[$message_id], $parts[$idx]['content']);
        }
    }

    public function testFetchMultipartMessagesShouldReturnAResponsePayloadIfARoomIDLimitInitialIDAndDirectionAreProvided()
    {
        $user_id = $this->makeUser();
        $room_id = $this->makeRoom($user_id);

        $messages = $this->makeMessages($room_id, [ [$user_id => 'hi 1'],
                                                    [$user_id => 'hi 2'],
                                                    [$user_id => 'hi 3'],
                                                    [$user_id => 'hi 4'],
        ]);

        $offset = 1;
        $limit = 2;
        // the messages returned should be this slice, note the
        // offset+1 since the API will return the elements _after_ the
        // marker
        $sliced_messages = array_slice($messages, $offset+1, $limit, true);

        $get_msg_res = $this->chatkit->fetchMultipartMessages([
            'room_id' => $room_id,
            'limit' => $limit,
            'direction' => 'newer',
            'initial_id' => array_keys($messages)[$offset]
        ]);

        $this->assertEquals(200, $get_msg_res['status']);
        $this->assertEquals(count($messages), count($get_msg_res['body']));

        $parts = [ $get_msg_res['body'][0]['parts'][0],
                   $get_msg_res['body'][1]['parts'][0] ];

        foreach ( [0, 1] as $idx) {
            $this->assertEquals($room_id, $get_msg_res['body'][$idx]['room_id']);
            $this->assertEquals($user_id, $get_msg_res['body'][$idx]['user_id']);
            $this->assertEquals('text/plain', $parts[$idx]['type']);

            $message_id = $get_msg_res['body'][$idx]['id'];
            // use the sliced messages to make sure we didn't get one
            // of the other messages
            $this->assertEquals($sliced_messages[$message_id], $parts[$idx]['content']);
        }
    }

    public function testDeleteMessageRaisesAnExceptionIfNoIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->deleteMessage([]);
    }

    public function testDeleteMessageShouldReturnAResponsePayloadIfAnIDIsProvided()
    {
        $user_id = $this->makeUser();
        $room_id = $this->makeRoom($user_id);

        $send_msg_res = $this->chatkit->sendSimpleMessage([
            'sender_id' => $user_id,
            'room_id' => $room_id,
            'text' => 'testing'
        ]);
        $this->assertEquals($send_msg_res['status'], 201);

        $delete_msg_res = $this->chatkit->deleteMessage([ 'id' => $send_msg_res['body']['message_id'] ]);
        $this->assertEquals($delete_msg_res['status'], 204);
        $this->assertEquals($delete_msg_res['body'], null);
    }

}
