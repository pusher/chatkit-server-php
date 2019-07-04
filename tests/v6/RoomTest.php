<?php

class RoomTestV6 extends \Base {
    public function testCreateRoomShouldReturnResponsePayloadIfIDIsProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $room_res = $this->chatkit->createRoom([
            'id' => 'mycoolroom',
            'creator_id' => $user_id,
            'name' => 'my room'
        ]);
        $this->assertEquals($room_res['status'], 201);
        $this->assertArrayHasKey('id', $room_res['body']);
        $this->assertEquals($room_res['body']['id'], 'mycoolroom');
        $this->assertEquals($room_res['body']['name'], 'my room');
        $this->assertFalse($room_res['body']['private']);
        $this->assertEquals($room_res['body']['member_user_ids'], [$user_id]);
    }
}