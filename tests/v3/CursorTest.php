<?php

class CursorTest extends \Base {

    public function testSetReadCursorRaisesAnExceptionIfNoRoomIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->setReadCursor([ 'user_id' => 'ham', 'position' => 123 ]);
    }

    public function testSetReadCursorRaisesAnExceptionIfNoUserIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->setReadCursor([ 'room_id' => '123', 'position' => 123 ]);
    }

    public function testSetReadCursorRaisesAnExceptionIfNoPositionIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->setReadCursor([ 'user_id' => 'ham', 'room_id' => '123' ]);
    }

    public function testSetReadCursorShouldReturnAResponsePayloadIfARoomIDUserIDAndPositionAreProvided()
    {
        $user_id = $this->makeUser();
        $room_id = $this->makeRoom($user_id);

        $set_cursor_res = $this->chatkit->setReadCursor([
            'user_id' => $user_id,
            'room_id' => $room_id,
            'position' => 123
        ]);
        $this->assertEquals($set_cursor_res['status'], 201);
        $this->assertEquals($set_cursor_res['body'], []);
    }

    public function testGetReadCursorRaisesAnExceptionIfNoUserIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->GetReadCursor([ 'room_id' => '123' ]);
    }

    public function testGetReadCursorRaisesAnExceptionIfNoRoomIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->GetReadCursor([ 'user_id' => 'ham' ]);
    }

    public function testGetReadCursorShouldReturnAResponsePayloadIfARoomIDAndUserIDAreProvided()
    {
        $user_id = $this->makeUser();
        $room_id = $this->makeRoom($user_id);

        $set_cursor_res = $this->chatkit->setReadCursor([
            'user_id' => $user_id,
            'room_id' => $room_id,
            'position' => 123
        ]);
        $this->assertEquals($set_cursor_res['status'], 201);

        $get_cursor_res = $this->chatkit->getReadCursor([
            'user_id' => $user_id,
            'room_id' => $room_id
        ]);
        $this->assertEquals($get_cursor_res['status'], 200);
        $this->assertArrayHasKey('updated_at', $get_cursor_res['body']);
        $this->assertEquals($get_cursor_res['body']['cursor_type'], 0);
        $this->assertEquals($get_cursor_res['body']['position'], 123);
        $this->assertEquals($get_cursor_res['body']['room_id'], $room_id);
        $this->assertEquals($get_cursor_res['body']['user_id'], $user_id);
    }

    public function testGetReadCursorsRaisesAnExceptionIfNoUserIDIsProvidedForUser()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->GetReadCursorsForUser([]);
    }

    public function testGetReadCursorsShouldReturnAResponsePayloadIfAUserIDIsProvidedForUser()
    {
        $user_id = $this->makeUser();
        $room_id = $this->makeRoom($user_id);

        $set_cursor_res = $this->chatkit->setReadCursor([
            'user_id' => $user_id,
            'room_id' => $room_id,
            'position' => 123
        ]);
        $this->assertEquals($set_cursor_res['status'], 201);

        $get_cursors_res = $this->chatkit->getReadCursorsForUser([
            'user_id' => $user_id
        ]);
        $this->assertEquals($get_cursors_res['status'], 200);
        $this->assertEquals(count($get_cursors_res['body']), 1);
        $this->assertArrayHasKey('updated_at', $get_cursors_res['body'][0]);
        $this->assertEquals($get_cursors_res['body'][0]['cursor_type'], 0);
        $this->assertEquals($get_cursors_res['body'][0]['position'], 123);
        $this->assertEquals($get_cursors_res['body'][0]['room_id'], $room_id);
        $this->assertEquals($get_cursors_res['body'][0]['user_id'], $user_id);
    }

    public function testGetReadCursorsRaisesAnExceptionIfNoRoomIDIsProvidedForRoom()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->GetReadCursorsForRoom([]);
    }

    public function testGetReadCursorsShouldReturnAResponsePayloadIfARoomIDIsProvidedForRoom()
    {
        $user_id = $this->makeUser();
        $room_id = $this->makeRoom($user_id);

        $set_cursor_res = $this->chatkit->setReadCursor([
            'user_id' => $user_id,
            'room_id' => $room_id,
            'position' => 123
        ]);
        $this->assertEquals($set_cursor_res['status'], 201);

        $get_cursors_res = $this->chatkit->getReadCursorsForRoom([
            'room_id' => $room_id
        ]);
        $this->assertEquals($get_cursors_res['status'], 200);
        $this->assertEquals(count($get_cursors_res['body']), 1);
        $this->assertArrayHasKey('updated_at', $get_cursors_res['body'][0]);
        $this->assertEquals($get_cursors_res['body'][0]['cursor_type'], 0);
        $this->assertEquals($get_cursors_res['body'][0]['position'], 123);
        $this->assertEquals($get_cursors_res['body'][0]['room_id'], $room_id);
        $this->assertEquals($get_cursors_res['body'][0]['user_id'], $user_id);
    }
}
