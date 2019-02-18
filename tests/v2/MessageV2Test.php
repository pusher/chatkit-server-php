<?php

class MessageV2Test extends \Base {

    public function testSendMessageRaisesAnExceptionIfNoRoomIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->sendMessage([ 'sender_id' => 'ham', 'text' => 'hi' ]);
    }

    public function testSendMessageRaisesAnExceptionIfNoSenderIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->sendMessage([ 'room_id' => '123', 'text' => 'hi' ]);
    }

    public function testSendMessageRaisesAnExceptionIfNoTextIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->sendMessage([ 'sender_id' => 'ham', 'room_id' => '123' ]);
    }

    public function testSendMessageRaisesAnExceptionIfNoResourceLinkIsProvidedForAMessageWithAnAttachment()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->sendMessage([
            'room_id' => '123',
            'sender_id' => 'ham',
            'text' => 'hi',
            'attachment' => [
                'type' => 'audio'
            ]
        ]);
    }

    public function testSendMessageRaisesAnExceptionIfNoTypeIsProvidedForAMessageWithAnAttachment()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->sendMessage([
            'room_id' => '123',
            'sender_id' => 'ham',
            'text' => 'hi',
            'attachment' => [
                'resource_link' => 'https://placekitten.com/200/300'
            ]
        ]);
    }

    public function testSendMessageRaisesAnExceptionIfAnInvalidTypeIsProvidedForAMessageWithAnAttachment()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->sendMessage([
            'room_id' => '123',
            'sender_id' => 'ham',
            'text' => 'hi',
            'attachment' => [
                'resource_link' => 'https://placekitten.com/200/300',
                'type' => 'somethingstupid'
            ]
        ]);
    }

    public function testSendMessageShouldReturnAResponsePayloadIfARoomIDSenderIDAndTextAreProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $room_res = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my room'
        ]);
        $this->assertEquals($room_res['status'], 201);

        $send_msg_res = $this->chatkit->sendMessage([
            'sender_id' => $user_id,
            'room_id' => $room_res['body']['id'],
            'text' => 'testing'
        ]);
        $this->assertEquals($send_msg_res['status'], 201);
        $this->assertArrayHasKey('message_id', $send_msg_res['body']);
    }

    public function testSendMessageShouldReturnAResponsePayloadIfARoomIDSenderIDTextAndALinkAttachmentAreProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $room_res = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my room'
        ]);
        $this->assertEquals($room_res['status'], 201);

        $send_msg_res = $this->chatkit->sendMessage([
            'sender_id' => $user_id,
            'room_id' => $room_res['body']['id'],
            'text' => 'testing',
            'attachment' => [
                'resource_link' => 'https://placekitten.com/200/300',
                'type' => 'image'
            ]
        ]);
        $this->assertEquals($send_msg_res['status'], 201);
        $this->assertArrayHasKey('message_id', $send_msg_res['body']);
    }

    public function testGetRoomMessagesRaisesAnExceptionIfNoRoomIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->getRoomMessages([]);
    }

    public function testGetRoomMessagesShouldReturnAResponsePayloadIfARoomIDIsProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $room_res = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my room'
        ]);
        $this->assertEquals($room_res['status'], 201);

        $send_msg_res1 = $this->chatkit->sendMessage([
            'sender_id' => $user_id,
            'room_id' => $room_res['body']['id'],
            'text' => 'hi 1'
        ]);
        $this->assertEquals($send_msg_res1['status'], 201);

        $send_msg_res2 = $this->chatkit->sendMessage([
            'sender_id' => $user_id,
            'room_id' => $room_res['body']['id'],
            'text' => 'hi 2'
        ]);
        $this->assertEquals($send_msg_res2['status'], 201);

        $get_msg_res = $this->chatkit->getRoomMessages([ 'room_id' => $room_res['body']['id'] ]);
        $this->assertEquals($get_msg_res['status'], 200);
        $this->assertEquals(count($get_msg_res['body']), 2);
        $this->assertEquals($get_msg_res['body'][0]['id'], $send_msg_res2['body']['message_id']);
        $this->assertEquals($get_msg_res['body'][0]['text'], 'hi 2');
        $this->assertEquals($get_msg_res['body'][0]['user_id'], $user_id);
        $this->assertEquals($get_msg_res['body'][0]['room_id'], $room_res['body']['id']);
        $this->assertEquals($get_msg_res['body'][1]['id'], $send_msg_res1['body']['message_id']);
        $this->assertEquals($get_msg_res['body'][1]['text'], 'hi 1');
        $this->assertEquals($get_msg_res['body'][1]['user_id'], $user_id);
        $this->assertEquals($get_msg_res['body'][1]['room_id'], $room_res['body']['id']);
    }

    public function testGetRoomMessagesShouldReturnAResponsePayloadIfARoomIDLimitInitialIDAndDirectionAreProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $room_res = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my room'
        ]);
        $this->assertEquals($room_res['status'], 201);

        $send_msg_opts = [ 'sender_id' => $user_id, 'room_id' => $room_res['body']['id'] ];

        $send_msg_res1 = $this->chatkit->sendMessage(array_merge($send_msg_opts, [ 'text' => 'hi 1' ]));
        $this->assertEquals($send_msg_res1['status'], 201);
        $send_msg_res2 = $this->chatkit->sendMessage(array_merge($send_msg_opts, [ 'text' => 'hi 2' ]));
        $this->assertEquals($send_msg_res2['status'], 201);
        $send_msg_res3 = $this->chatkit->sendMessage(array_merge($send_msg_opts, [ 'text' => 'hi 3' ]));
        $this->assertEquals($send_msg_res3['status'], 201);
        $send_msg_res4 = $this->chatkit->sendMessage(array_merge($send_msg_opts, [ 'text' => 'hi 4' ]));
        $this->assertEquals($send_msg_res4['status'], 201);

        $get_msg_res = $this->chatkit->getRoomMessages([
            'room_id' => $room_res['body']['id'],
            'limit' => 2,
            'direction' => 'newer',
            'initial_id' => $send_msg_res2['body']['message_id']
        ]);
        $this->assertEquals($get_msg_res['status'], 200);
        $this->assertEquals(count($get_msg_res['body']), 2);
        $this->assertEquals($get_msg_res['body'][0]['id'], $send_msg_res3['body']['message_id']);
        $this->assertEquals($get_msg_res['body'][0]['text'], 'hi 3');
        $this->assertEquals($get_msg_res['body'][0]['user_id'], $user_id);
        $this->assertEquals($get_msg_res['body'][0]['room_id'], $room_res['body']['id']);
        $this->assertEquals($get_msg_res['body'][1]['id'], $send_msg_res4['body']['message_id']);
        $this->assertEquals($get_msg_res['body'][1]['text'], 'hi 4');
        $this->assertEquals($get_msg_res['body'][1]['user_id'], $user_id);
        $this->assertEquals($get_msg_res['body'][1]['room_id'], $room_res['body']['id']);
    }

}
