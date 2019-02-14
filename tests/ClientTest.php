<?php

class ClientTest extends \PHPUnit_Framework_TestCase {

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

    public function testAuthenticateShouldRaiseAnArgumentErrorIfEmptyOptionsAreProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->authenticate([]);
    }

    public function testAuthenticateShouldReturnATokenPayloadIfAUserIDIsProvided()
    {
        $auth = $this->chatkit->authenticate([ 'user_id' => 'ham' ]);
        $this->assertEquals($auth['status'], 200);
        $this->assertEmpty($auth['headers']);
        $this->assertEquals($auth['body']['token_type'], 'bearer');
        $this->assertEquals($auth['body']['expires_in'], 24 * 60 * 60);
        $this->assertArrayHasKey('access_token', $auth['body']);
    }

    public function testGenerateAccessTokenShouldRaiseAnExceptionIfEmptyOptionsAreProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->generateAccessToken([]);
    }

    public function testGenerateAccessTokenShouldReturnATokenPayloadIfAUserIDIsProvided()
    {
        $token_payload = $this->chatkit->generateAccessToken([ 'user_id' => 'ham' ]);
        $this->assertEquals($token_payload['expires_in'], 24 * 60 * 60);
        $this->assertArrayHasKey('token', $token_payload);
    }

    public function testGenerateAccessTokenShouldReturnATokenPayloadIfSuTrueIsProvided()
    {
        $token_payload = $this->chatkit->generateAccessToken([ 'su' => true ]);
        $this->assertEquals($token_payload['expires_in'], 24 * 60 * 60);
        $this->assertArrayHasKey('token', $token_payload);
    }

    public function testGenerateSuTokenShouldReturnATokenPayloadIfNoOptionsAreProvided()
    {
        $token_payload = $this->chatkit->generateSuToken([]);
        $this->assertEquals($token_payload['expires_in'], 24 * 60 * 60);
        $this->assertArrayHasKey('token', $token_payload);
    }

    public function testGenerateSuTokenShouldReturnATokenPayloadIfAUserIDIsProvided()
    {
        $token_payload = $this->chatkit->generateSuToken([ 'user_id' => 'ham' ]);
        $this->assertEquals($token_payload['expires_in'], 24 * 60 * 60);
        $this->assertArrayHasKey('token', $token_payload);
    }

    public function testCreateUserShouldRaiseAnExceptionIfNoIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->createUser([ 'name' => 'Ham' ]);
    }

    public function testCreateUserShouldRaiseAnExceptionIfNoNameIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->createUser([ 'id' => 'ham' ]);
    }

    public function testCreateUserShouldReturnAResponsePayloadIfAnIDAndNameAreProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($res['status'], 201);
        $this->assertEquals($res['body']['id'], $user_id);
        $this->assertEquals($res['body']['name'], 'Ham');
    }

    public function testCreateUserShouldReturnAResponsePayloadIfAnIDNameAvatarURLAndCustomDataAreProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham',
            'avatar_url' => 'https://placekitten.com/200/300',
            'custom_data' => [ 'something' => 'CUSTOM' ]
        ]);
        $this->assertEquals($res['status'], 201);
        $this->assertEquals($res['body']['id'], $user_id);
        $this->assertEquals($res['body']['name'], 'Ham');
        $this->assertEquals($res['body']['avatar_url'], 'https://placekitten.com/200/300');
        $this->assertEquals($res['body']['custom_data'], [ 'something' => 'CUSTOM' ]);
    }

    public function testCreateUsersShouldRaiseAnExceptionIfNoUsersAreProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->createUsers([]);
    }

    public function testCreateUsersShouldReturnAResponsePayloadIfAValidSetOfOptionsAreProvided()
    {
        $user_id1 = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_id2 = $this->guidv4(openssl_random_pseudo_bytes(16));
        $res = $this->chatkit->createUsers([
            'users' => [
                [ 'id' => $user_id1, 'name' => 'Ham' ],
                [ 'id' => $user_id2, 'name' => 'Ham2' ]
            ]
        ]);
        $this->assertEquals($res['status'], 201);
        $ids = array_map([$this, 'extractID'], $res['body']);
        $names = array_map([$this, 'extractName'], $res['body']);
        $this->assertEmpty(array_diff($ids, [$user_id1, $user_id2]));
        $this->assertEmpty(array_diff($names, ['Ham', 'Ham2']));
    }

    public function testUpdateUserShouldRaiseAnExceptionIfNoIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->updateUser([ 'name' => 'Ham' ]);
    }

    public function testUpdateUserShouldReturnAResponsePayloadIfAValidSetOfOptionsAreProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham',
            'avatar_url' => 'https://placekitten.com/200/300',
            'custom_data' => [ 'something' => 'CUSTOM' ]
        ]);
        $this->assertEquals($res['status'], 201);

        $res = $this->chatkit->updateUser([
            'id' => $user_id,
            'name' => 'No longer Ham',
            'avatar_url' => 'https://test.update.com',
            'custom_data' => [ 'something' => 'NEW', 'and' => 777 ]
        ]);
        $this->assertEquals($res['status'], 204);
        $this->assertEquals($res['body'], null);
    }

    public function testDeleteUserShouldRaiseAnExceptionIfNoIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->deleteUser([]);
    }

    public function testDeleteUserShouldReturnAResponsePayloadIfAnIDIsProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($res['status'], 201);

        $delete_res = $this->chatkit->deleteUser([ 'id' => $user_id ]);
        $this->assertEquals($delete_res['status'], 204);
        $this->assertEquals($delete_res['body'], null);
    }

    public function testGetUserShouldRaiseAnExceptionIfNoIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->getUser([]);
    }

    public function testGetUserShouldReturnAResponsePayloadIfAnIDIsProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($res['status'], 201);

        $get_res = $this->chatkit->getUser([ 'id' => $user_id ]);
        $this->assertEquals($get_res['status'], 200);
        $this->assertEquals($get_res['body']['id'], $user_id);
        $this->assertEquals($get_res['body']['name'], 'Ham');
    }

    public function testGetUsersShouldReturnAResponsePayloadIfNoOptionsAreProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_id2 = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_res = $this->chatkit->createUsers([
            'users' => [
                [ 'id' => $user_id, 'name' => 'Ham' ],
                [ 'id' => $user_id2, 'name' => 'Ham2' ]
            ]
        ]);
        $this->assertEquals($create_res['status'], 201);

        $get_res = $this->chatkit->getUsers();
        $this->assertEquals($get_res['status'], 200);
        $this->assertEquals($get_res['body'][0]['id'], $user_id);
        $this->assertEquals($get_res['body'][0]['name'], 'Ham');
        $this->assertEquals($get_res['body'][1]['id'], $user_id2);
        $this->assertEquals($get_res['body'][1]['name'], 'Ham2');
    }

    public function testGetUsersShouldReturnAResponsePayloadIfALimitIsProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_id2 = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_res = $this->chatkit->createUsers([
            'users' => [
                [ 'id' => $user_id, 'name' => 'Ham' ],
                [ 'id' => $user_id2, 'name' => 'Ham2' ]
            ]
        ]);
        $this->assertEquals($create_res['status'], 201);

        $get_res = $this->chatkit->getUsers([ 'limit' => 1 ]);
        $this->assertEquals($get_res['status'], 200);
        $this->assertEquals(count($get_res['body']), 1);
        $this->assertEquals($get_res['body'][0]['id'], $user_id);
        $this->assertEquals($get_res['body'][0]['name'], 'Ham');
    }

    public function testGetUsersShouldReturnAResponsePayloadIfAFromTimestampValueIsProvided()
    {
        $user_id1 = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_id2 = $this->guidv4(openssl_random_pseudo_bytes(16));

        $create_res1 = $this->chatkit->createUser([
            'id' => $user_id1,
            'name' => 'Ham'
        ]);
        $this->assertEquals($create_res1['status'], 201);

        sleep(2);

        $create_res2 = $this->chatkit->createUser([
            'id' => $user_id2,
            'name' => 'Ham2'
        ]);
        $this->assertEquals($create_res2['status'], 201);

        $get_users_res1 = $this->chatkit->getUsers([ 'from_timestamp' => $create_res1['body']['created_at'] ]);
        $this->assertEquals($get_users_res1['status'], 200);
        $this->assertEquals(count($get_users_res1['body']), 2);
        $this->assertEquals($get_users_res1['body'][0]['id'], $user_id1);
        $this->assertEquals($get_users_res1['body'][0]['name'], 'Ham');
        $this->assertEquals($get_users_res1['body'][1]['id'], $user_id2);
        $this->assertEquals($get_users_res1['body'][1]['name'], 'Ham2');

        $get_users_res2 = $this->chatkit->getUsers([ 'from_timestamp' => $create_res2['body']['created_at'] ]);
        $this->assertEquals($get_users_res2['status'], 200);
        $this->assertEquals(count($get_users_res2['body']), 1);
        $this->assertEquals($get_users_res2['body'][0]['id'], $user_id2);
        $this->assertEquals($get_users_res2['body'][0]['name'], 'Ham2');
    }

    public function testGetUsersByIDShouldRaiseAnExceptionIfNoUserIDsAreProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->getUsersByID([]);
    }

    public function testGetUsersByIDShouldReturnAResponsePayloadIfUserIDsAreProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_id2 = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_res = $this->chatkit->createUsers([
            'users' => [
                [ 'id' => $user_id, 'name' => 'Ham' ],
                [ 'id' => $user_id2, 'name' => 'Ham2' ]
            ]
        ]);
        $this->assertEquals($create_res['status'], 201);

        $get_users_res = $this->chatkit->getUsersByID([
            'user_ids' => [$user_id, $user_id2]
        ]);
        $this->assertEquals($get_users_res['status'], 200);
        $this->assertEquals(count($get_users_res['body']), 2);
        $ids = array_map([$this, 'extractID'], $get_users_res['body']);
        $names = array_map([$this, 'extractName'], $get_users_res['body']);
        $this->assertEmpty(array_diff($ids, [$user_id, $user_id2]));
        $this->assertEmpty(array_diff($names, ['Ham', 'Ham2']));
    }

    public function testCreateRoomShouldRaiseAnExcepctionIfNoNameIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->createRoom([ 'creator_id' => 'ham' ]);
    }

    public function testCreateRoomShouldRaiseAnExcepctionIfNoCreatorIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->createRoom([ 'name' => 'my room' ]);
    }

    public function testCreateRoomShouldReturnAResponsePayloadIfACreatorIDAndNameAreProvided()
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
        $this->assertArrayHasKey('id', $room_res['body']);
        $this->assertEquals($room_res['body']['name'], 'my room');
        $this->assertFalse($room_res['body']['private']);
        $this->assertEquals($room_res['body']['member_user_ids'], [$user_id]);
    }

    public function testCreateRoomShouldReturnAResponsePayloadIfACreatorIDNameAndUserIDsAreProvidedAndTheRoomIsPrivate()
    {
        $user_id1 = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_id2 = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUsers([
            'users' => [
                [ 'id' => $user_id1, 'name' => 'Ham' ],
                [ 'id' => $user_id2, 'name' => 'Ham2' ]
            ]
        ]);
        $this->assertEquals($user_res['status'], 201);

        $room_res = $this->chatkit->createRoom([
            'creator_id' => $user_id1,
            'name' => 'my room',
            'user_ids' => [$user_id2],
            'private' => true
        ]);
        $this->assertEquals($room_res['status'], 201);
        $this->assertArrayHasKey('id', $room_res['body']);
        $this->assertEquals($room_res['body']['name'], 'my room');
        $this->assertTrue($room_res['body']['private']);
        $this->assertEmpty(array_diff($room_res['body']['member_user_ids'], [$user_id1, $user_id2]));
    }

    public function testCreateRoomShouldReturnAResponsePayloadIfACreatorIDNameAndCustomDataAreProvided() {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $room_res = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my room',
            'custom_data' => array('foo' => 'bar')
        ]);
        $this->assertEquals($room_res['status'], 201);
        $this->assertArrayHasKey('id', $room_res['body']);
        $this->assertEquals($room_res['body']['name'], 'my room');
        $this->assertEquals($room_res['body']['custom_data'], array('foo' => 'bar'));
    }

    public function testUpdateRoomShouldRaiseAnExceptionIfNoIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->updateRoom([ 'name' => 'new name' ]);
    }

    public function testUpdateRoomShouldReturnAResponsePayloadIfAValidSetOfOptionsAreProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $room_res = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my room',
            'custom_data' => array('foo' => 'bar')
        ]);
        $this->assertEquals($room_res['status'], 201);

        $update_res = $this->chatkit->updateRoom([
            'id' => $room_res['body']['id'],
            'name' => 'new name',
            'private' => true,
            'custom_data' => array('foo' => 'baz')
        ]);
        $this->assertEquals($update_res['status'], 204);
        $this->assertEquals($update_res['body'], null);

        $get_res = $this->chatkit->getRoom([
            'id' => $room_res['body']['id']
        ]);
        $this->assertEquals($get_res['status'], 200);
        $this->assertEquals($get_res['body']['name'], 'new name');
        $this->assertEquals($get_res['body']['private'], true);
        $this->assertEquals($get_res['body']['custom_data'], array('foo' => 'baz'));
    }

    public function testDeleteRoomShouldRaiseAnExceptionIfNoIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->deleteRoom([]);
    }

    public function testDeleteRoomShouldReturnAResponsePayloadIfAnIDIsProvided()
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

        $delete_res = $this->chatkit->deleteRoom([
            'id' => $room_res['body']['id']
        ]);
        $this->assertEquals($delete_res['status'], 204);
        $this->assertEquals($delete_res['body'], null);
    }

    public function testGetRoomShouldRaiseAnExceptionIfNoIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->getRoom([]);
    }

    public function testGetRoomShouldReturnAResponsePayloadIfAnIDIsProvided()
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

        $get_res = $this->chatkit->getRoom([
            'id' => $room_res['body']['id']
        ]);
        $this->assertEquals($get_res['status'], 200);
        $this->assertEquals($get_res['body']['name'], 'my room');
        $this->assertFalse($get_res['body']['private']);
        $this->assertEmpty(array_diff($get_res['body']['member_user_ids'], [$user_id]));
    }

    public function testGetRoomsShouldReturnAResponsePayloadIfNoOptionsAreProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $room_res1 = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my room'
        ]);
        $this->assertEquals($room_res1['status'], 201);

        $room_res2 = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my second room'
        ]);
        $this->assertEquals($room_res2['status'], 201);

        $get_rooms_res = $this->chatkit->getRooms();
        $this->assertEquals($get_rooms_res['status'], 200);
        $this->assertEquals(count($get_rooms_res['body']), 2);
        $this->assertEquals($get_rooms_res['body'][0]['id'], $room_res1['body']['id']);
        $this->assertEquals($get_rooms_res['body'][0]['name'], 'my room');
        $this->assertFalse($get_rooms_res['body'][0]['private']);
        $this->assertEquals($get_rooms_res['body'][1]['id'], $room_res2['body']['id']);
        $this->assertEquals($get_rooms_res['body'][1]['name'], 'my second room');
        $this->assertFalse($get_rooms_res['body'][1]['private']);
    }

    public function testGetRoomsShouldReturnAResponsePayloadIfIncludePrivateIsSpecified()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $room_res1 = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my room',
            'private' => true
        ]);
        $this->assertEquals($room_res1['status'], 201);

        $room_res2 = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my second room'
        ]);
        $this->assertEquals($room_res2['status'], 201);

        $get_rooms_res1 = $this->chatkit->getRooms();
        $this->assertEquals($get_rooms_res1['status'], 200);
        $this->assertEquals(count($get_rooms_res1['body']), 1);
        $this->assertEquals($get_rooms_res1['body'][0]['id'], $room_res2['body']['id']);
        $this->assertEquals($get_rooms_res1['body'][0]['name'], 'my second room');
        $this->assertFalse($get_rooms_res1['body'][0]['private']);

        $get_rooms_res2 = $this->chatkit->getRooms([ 'include_private' => true ]);
        $this->assertEquals($get_rooms_res2['status'], 200);
        $this->assertEquals(count($get_rooms_res2['body']), 2);
        $this->assertEquals($get_rooms_res2['body'][0]['id'], $room_res1['body']['id']);
        $this->assertEquals($get_rooms_res2['body'][0]['name'], 'my room');
        $this->assertTrue($get_rooms_res2['body'][0]['private']);
        $this->assertEquals($get_rooms_res2['body'][1]['id'], $room_res2['body']['id']);
        $this->assertEquals($get_rooms_res2['body'][1]['name'], 'my second room');
        $this->assertFalse($get_rooms_res2['body'][1]['private']);
    }

    public function testGetRoomsShouldReturnAResponsePayloadIfIncludedPrivateAndFromIDAreSpecified()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $room_res1 = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my room',
            'private' => true
        ]);
        $this->assertEquals($room_res1['status'], 201);

        $room_res2 = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my second room'
        ]);
        $this->assertEquals($room_res2['status'], 201);

        $get_rooms_res1 = $this->chatkit->getRooms();
        $this->assertEquals($get_rooms_res1['status'], 200);
        $this->assertEquals(count($get_rooms_res1['body']), 1);
        $this->assertEquals($get_rooms_res1['body'][0]['id'], $room_res2['body']['id']);
        $this->assertEquals($get_rooms_res1['body'][0]['name'], 'my second room');
        $this->assertFalse($get_rooms_res1['body'][0]['private']);

        $get_rooms_res2 = $this->chatkit->getRooms([
            'include_private' => true,
            'from_id' => $room_res1['body']['id']
        ]);
        $this->assertEquals($get_rooms_res2['status'], 200);
        $this->assertEquals(count($get_rooms_res2['body']), 1);
        $this->assertEquals($get_rooms_res2['body'][0]['id'], $room_res2['body']['id']);
        $this->assertEquals($get_rooms_res2['body'][0]['name'], 'my second room');
        $this->assertFalse($get_rooms_res2['body'][0]['private']);

        $get_rooms_res3 = $this->chatkit->getRooms([
            'include_private' => true,
            'from_id' => $room_res2['body']['id']
        ]);
        $this->assertEquals($get_rooms_res3['status'], 200);
        $this->assertEquals(count($get_rooms_res3['body']), 0);
    }

    public function testGetUserRoomsShouldRaiseAnExceptionIfNoIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->getUserRooms([]);
    }

    public function testGetUserRoomsShouldReturnAResponsePayloadIfAnIDIsProvided()
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

        $get_user_rooms_res = $this->chatkit->getRooms([ 'id' => $user_id ]);
        $this->assertEquals($get_user_rooms_res['status'], 200);
        $this->assertEquals(count($get_user_rooms_res['body']), 1);
        $this->assertEquals($get_user_rooms_res['body'][0]['id'], $room_res['body']['id']);
        $this->assertEquals($get_user_rooms_res['body'][0]['name'], 'my room');
        $this->assertFalse($get_user_rooms_res['body'][0]['private']);
    }

    public function testGetUserRoomsShouldReturnAResponsePayloadIfAnIDIsProvidedAndOnlyReturnTheCorrectRooms()
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

        $user_id2 = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res2 = $this->chatkit->createUser([
            'id' => $user_id2,
            'name' => 'Ham2'
        ]);
        $this->assertEquals($user_res2['status'], 201);

        $room_res2 = $this->chatkit->createRoom([
            'creator_id' => $user_id2,
            'name' => 'my second room'
        ]);
        $this->assertEquals($room_res2['status'], 201);

        $get_user_rooms_res = $this->chatkit->getUserRooms([ 'id' => $user_id ]);
        $this->assertEquals($get_user_rooms_res['status'], 200);
        $this->assertEquals(count($get_user_rooms_res['body']), 1);
        $this->assertEquals($get_user_rooms_res['body'][0]['id'], $room_res['body']['id']);
        $this->assertEquals($get_user_rooms_res['body'][0]['name'], 'my room');
        $this->assertFalse($get_user_rooms_res['body'][0]['private']);
    }

    public function testGetUserJoinableRoomsShouldRaiseAnExceptionIfNoIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->getUserJoinableRooms([]);
    }

    public function testGetUserJoinableRoomsShouldReturnAResponsePayloadIfAnIDIsProvided()
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

        $user_id2 = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res2 = $this->chatkit->createUser([
            'id' => $user_id2,
            'name' => 'Ham2'
        ]);
        $this->assertEquals($user_res2['status'], 201);

        $get_user_rooms_res = $this->chatkit->getUserJoinableRooms([ 'id' => $user_id ]);
        $this->assertEquals($get_user_rooms_res['status'], 200);
        $this->assertEquals(count($get_user_rooms_res['body']), 0);

        $room_res2 = $this->chatkit->createRoom([
            'creator_id' => $user_id2,
            'name' => 'my second room'
        ]);
        $this->assertEquals($room_res2['status'], 201);

        $get_user_rooms_res = $this->chatkit->getUserJoinableRooms([ 'id' => $user_id ]);
        $this->assertEquals($get_user_rooms_res['status'], 200);
        $this->assertEquals(count($get_user_rooms_res['body']), 1);
        $this->assertEquals($get_user_rooms_res['body'][0]['id'], $room_res2['body']['id']);
        $this->assertEquals($get_user_rooms_res['body'][0]['name'], 'my second room');
        $this->assertFalse($get_user_rooms_res['body'][0]['private']);
    }

    public function testAddUsersToRoomShouldRaiseAnExceptionIfNoRoomIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->addUsersToRoom([ 'user_ids' => ['ham'] ]);
    }

    public function testAddUsersToRoomShouldRaiseAnExceptionIfNoUserIDsAreProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->addUsersToRoom([ 'room_id' => '123' ]);
    }

    public function testAddUsersToRoomShouldReturnAResponsePayloadIfARoomIDAndUserIDsAreProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $user_id2 = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res2 = $this->chatkit->createUser([
            'id' => $user_id2,
            'name' => 'Ham2'
        ]);
        $this->assertEquals($user_res2['status'], 201);

        $user_id3 = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res3 = $this->chatkit->createUser([
            'id' => $user_id3,
            'name' => 'Ham3'
        ]);
        $this->assertEquals($user_res3['status'], 201);

        $room_res = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my room'
        ]);
        $this->assertEquals($room_res['status'], 201);

        $add_users_res = $this->chatkit->addUsersToRoom([
            'room_id' => $room_res['body']['id'],
            'user_ids' => [$user_id2, $user_id3]
        ]);
        $this->assertEquals($add_users_res['status'], 204);
        $this->assertEquals($add_users_res['body'], null);

        $get_res = $this->chatkit->getRoom([
            'id' => $room_res['body']['id']
        ]);
        $this->assertEquals($get_res['status'], 200);
        $this->assertEquals($get_res['body']['name'], 'my room');
        $this->assertFalse($get_res['body']['private']);
        $this->assertEmpty(array_diff($get_res['body']['member_user_ids'], [$user_id, $user_id2, $user_id3]));
    }

    public function testRemoveUsersFromRoomShouldRaiseAnExceptionIfNoRoomIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->removeUsersFromRoom([ 'user_ids' => ['ham'] ]);
    }

    public function testRemoveUsersFromRoomShouldRaiseAnExceptionIfNoUserIDsAreProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->removeUsersFromRoom([ 'room_id' => '123' ]);
    }

    public function testRemoveUsersFromRoomShouldReturnAResponsePayloadIfARoomIDAndUserIDsAreProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $user_id2 = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res2 = $this->chatkit->createUser([
            'id' => $user_id2,
            'name' => 'Ham2'
        ]);
        $this->assertEquals($user_res2['status'], 201);

        $user_id3 = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res3 = $this->chatkit->createUser([
            'id' => $user_id3,
            'name' => 'Ham3'
        ]);
        $this->assertEquals($user_res3['status'], 201);

        $room_res = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my room',
            'user_ids' => [$user_id2, $user_id3]
        ]);
        $this->assertEquals($room_res['status'], 201);

        $remove_users_res = $this->chatkit->removeUsersFromRoom([
            'room_id' => $room_res['body']['id'],
            'user_ids' => [$user_id3]
        ]);
        $this->assertEquals($remove_users_res['status'], 204);
        $this->assertEquals($remove_users_res['body'], null);

        $get_res = $this->chatkit->getRoom([
            'id' => $room_res['body']['id']
        ]);
        $this->assertEquals($get_res['status'], 200);
        $this->assertEquals($get_res['body']['name'], 'my room');
        $this->assertFalse($get_res['body']['private']);
        $this->assertEmpty(array_diff($get_res['body']['member_user_ids'], [$user_id, $user_id2]));
    }

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

    public function testSendMultipartMessageShouldReturnAResponsePayloadIfPartsProvided()
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

        $parts = [ ['type' => 'text/plain',
                    'content' => 'testing'],
                   ['type' => 'image/png',
                    'url' => 'https://example.com/image.png']
        ];

        $send_msg_res = $this->chatkit->sendMultipartMessage([
            'sender_id' => $user_id,
            'room_id' => $room_res['body']['id'],
            'parts' => $parts
        ]);
        $this->assertEquals($send_msg_res['status'], 201);
        $this->assertArrayHasKey('message_id', $send_msg_res['body']);
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

        $send_msg_res = $this->chatkit->sendSimpleMessage([
            'sender_id' => $user_id,
            'room_id' => $room_res['body']['id'],
            'text' => 'testing'
        ]);
        $this->assertEquals($send_msg_res['status'], 201);
        $this->assertArrayHasKey('message_id', $send_msg_res['body']);
    }

    public function testGetRoomMessagesRaisesAnExceptionIfNoRoomIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->getRoomMessages([]);
    }

    public function testGetRoomMessagesShouldReturnAResponsePayloadIfARoomIDIsProvide()
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

    public function testDeleteMessageRaisesAnExceptionIfNoIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->deleteMessage([]);
    }

    public function testDeleteMessageShouldReturnAResponsePayloadIfAnIDIsProvided()
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

        $delete_msg_res = $this->chatkit->deleteMessage([ 'id' => $send_msg_res['body']['message_id'] ]);
        $this->assertEquals($delete_msg_res['status'], 204);
        $this->assertEquals($delete_msg_res['body'], null);
    }

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

        $set_cursor_res = $this->chatkit->setReadCursor([
            'user_id' => 'ham',
            'room_id' => $room_res['body']['id'],
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

        $set_cursor_res = $this->chatkit->setReadCursor([
            'user_id' => $user_id,
            'room_id' => $room_res['body']['id'],
            'position' => 123
        ]);
        $this->assertEquals($set_cursor_res['status'], 201);

        $get_cursor_res = $this->chatkit->getReadCursor([
            'user_id' => $user_id,
            'room_id' => $room_res['body']['id']
        ]);
        $this->assertEquals($get_cursor_res['status'], 200);
        $this->assertArrayHasKey('updated_at', $get_cursor_res['body']);
        $this->assertEquals($get_cursor_res['body']['cursor_type'], 0);
        $this->assertEquals($get_cursor_res['body']['position'], 123);
        $this->assertEquals($get_cursor_res['body']['room_id'], $room_res['body']['id']);
        $this->assertEquals($get_cursor_res['body']['user_id'], $user_id);
    }

    public function testGetReadCursorsRaisesAnExceptionIfNoUserIDIsProvidedForUser()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->GetReadCursorsForUser([]);
    }

    public function testGetReadCursorsShouldReturnAResponsePayloadIfAUserIDIsProvidedForUser()
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

        $set_cursor_res = $this->chatkit->setReadCursor([
            'user_id' => $user_id,
            'room_id' => $room_res['body']['id'],
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
        $this->assertEquals($get_cursors_res['body'][0]['room_id'], $room_res['body']['id']);
        $this->assertEquals($get_cursors_res['body'][0]['user_id'], $user_id);
    }

    public function testGetReadCursorsRaisesAnExceptionIfNoRoomIDIsProvidedForRoom()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->GetReadCursorsForRoom([]);
    }

    public function testGetReadCursorsShouldReturnAResponsePayloadIfARoomIDIsProvidedForRoom()
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

        $set_cursor_res = $this->chatkit->setReadCursor([
            'user_id' => $user_id,
            'room_id' => $room_res['body']['id'],
            'position' => 123
        ]);
        $this->assertEquals($set_cursor_res['status'], 201);

        $get_cursors_res = $this->chatkit->getReadCursorsForRoom([
            'room_id' => $room_res['body']['id']
        ]);
        $this->assertEquals($get_cursors_res['status'], 200);
        $this->assertEquals(count($get_cursors_res['body']), 1);
        $this->assertArrayHasKey('updated_at', $get_cursors_res['body'][0]);
        $this->assertEquals($get_cursors_res['body'][0]['cursor_type'], 0);
        $this->assertEquals($get_cursors_res['body'][0]['position'], 123);
        $this->assertEquals($get_cursors_res['body'][0]['room_id'], $room_res['body']['id']);
        $this->assertEquals($get_cursors_res['body'][0]['user_id'], $user_id);
    }

    public function testCreateGlobalRoleRaisesAnExceptionIfNoNameIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->createGlobalRole([ 'permissions' => [] ]);
    }

    public function testCreateGlobalRoleRaisesAnExceptionIfNoPermissionsKeyProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->createGlobalRole([ 'name' => 'test' ]);
    }

    public function testCreateGlobalRoleShouldReturnAResponsePayloadIfANameAndPermissionsKeyAreProvided()
    {
        $role_name = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_role_res = $this->chatkit->createGlobalRole([
            'name' => $role_name,
            'permissions' => ['room:create']
        ]);
        $this->assertEquals($create_role_res['status'], 201);
        $this->assertEquals($create_role_res['body'], null);
    }

    public function testCreateRoomRoleRaisesAnExceptionIfNoNameIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->createRoomRole([ 'permissions' => [] ]);
    }

    public function testCreateRoomRoleRaisesAnExceptionIfNoPermissionsKeyProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->createRoomRole([ 'name' => 'test' ]);
    }

    public function testCreateRoomRoleShouldReturnAResponsePayloadIfANameAndPermissionsKeyAreProvided()
    {
        $role_name = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_role_res = $this->chatkit->createRoomRole([
            'name' => $role_name,
            'permissions' => ['room:update']
        ]);
        $this->assertEquals($create_role_res['status'], 201);
        $this->assertEquals($create_role_res['body'], null);
    }

    public function testDeleteGlobalRoleRaisesAnExceptionIfNoOptionsAreProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->deleteGlobalRole([]);
    }

    public function testDeleteGlobalRoleShouldReturnAResponsePayloadIfANameIsProvided()
    {
        $role_name = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_role_res = $this->chatkit->createGlobalRole([
            'name' => $role_name,
            'permissions' => ['room:create']
        ]);
        $this->assertEquals($create_role_res['status'], 201);

        $delete_role_res = $this->chatkit->deleteGlobalRole([
            'name' => $role_name
        ]);
        $this->assertEquals($delete_role_res['status'], 204);
        $this->assertEquals($delete_role_res['body'], null);
    }

    public function testDeleteRoomRoleRaisesAnExceptionIfNoOptionsAreProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->deleteRoomRole([]);
    }

    public function testDeleteRoomRoleShouldReturnAResponsePayloadIfANameIsProvided()
    {
        $role_name = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_role_res = $this->chatkit->createRoomRole([
            'name' => $role_name,
            'permissions' => ['room:update']
        ]);
        $this->assertEquals($create_role_res['status'], 201);

        $delete_role_res = $this->chatkit->deleteRoomRole([
            'name' => $role_name
        ]);
        $this->assertEquals($delete_role_res['status'], 204);
        $this->assertEquals($delete_role_res['body'], null);
    }

    public function testAssignGlobalRoleToUserRaisesAnExceptionIfNoUserIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->assignGlobalRoleToUser([ 'name' => 'test' ]);
    }

    public function testAssignGlobalRoleToUserRaisesAnExceptionIfNoNameIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->assignGlobalRoleToUser([ 'user_id' => 'ham' ]);
    }

    public function testAssignGlobalRoleToUserShouldReturnAResponsePayloadIfANameAndUserIDAreProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $role_name = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_role_res = $this->chatkit->createGlobalRole([
            'name' => $role_name,
            'permissions' => ['room:create']
        ]);
        $this->assertEquals($create_role_res['status'], 201);

        $assign_role_res = $this->chatkit->assignGlobalRoleToUser([
            'name' => $role_name,
            'user_id' => $user_id
        ]);
        $this->assertEquals($assign_role_res['status'], 201);
        $this->assertEquals($assign_role_res['body'], null);
    }

    public function testAssignRoomRoleToUserRaisesAnExceptionIfNoRoomIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->assignRoomRoleToUser([ 'name' => 'test', 'user_id' => 'ham' ]);
    }

    public function testAssignRoomRoleToUserRaisesAnExceptionIfNoUserIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->assignRoomRoleToUser([ 'room_id' => 'ham', 'name' => 'test' ]);
    }

    public function testAssignRoomRoleToUserRaisesAnExceptionIfNoNameIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->assignRoomRoleToUser([ 'user_id' => 'ham', 'room_id' => '123' ]);
    }

    public function testAssignRoomRoleToUserShouldReturnAResponsePayloadIfANameUserIDAndRoomIDAreProvided()
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

        $role_name = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_role_res = $this->chatkit->createRoomRole([
            'name' => $role_name,
            'permissions' => ['room:update']
        ]);
        $this->assertEquals($create_role_res['status'], 201);

        $assign_role_res = $this->chatkit->assignRoomRoleToUser([
            'name' => $role_name,
            'user_id' => $user_id,
            'room_id' => $room_res['body']['id']
        ]);
        $this->assertEquals($assign_role_res['status'], 201);
        $this->assertEquals($assign_role_res['body'], null);
    }

    public function testGetRolesShouldReturnAResponsePayloadIfNoOptionsAreProvided()
    {
        $role_name = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_role_res = $this->chatkit->createRoomRole([
            'name' => $role_name,
            'permissions' => ['room:update']
        ]);
        $this->assertEquals($create_role_res['status'], 201);

        $get_roles_res = $this->chatkit->getRoles();
        $this->assertEquals($get_roles_res['status'], 200);
        $this->assertEquals(count($get_roles_res['body']), 1);
        $this->assertEquals($get_roles_res['body'][0]['scope'], 'room');
        $this->assertEquals($get_roles_res['body'][0]['name'], $role_name);
        $this->assertEquals($get_roles_res['body'][0]['permissions'], ['room:update']);
    }

    public function testGetUserRolesRaisesAnExceptionIfNoUserIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->getUserRoles([]);
    }

    public function testGetUserRolesShouldReturnAResponsePayloadIfAUserIDIsProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $role_name = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_role_res = $this->chatkit->createGlobalRole([
            'name' => $role_name,
            'permissions' => ['room:update']
        ]);
        $this->assertEquals($create_role_res['status'], 201);

        $assign_role_res = $this->chatkit->assignGlobalRoleToUser([
            'name' => $role_name,
            'user_id' => $user_id
        ]);
        $this->assertEquals($assign_role_res['status'], 201);

        $get_roles_res = $this->chatkit->getUserRoles([ 'user_id' => $user_id ]);
        $this->assertEquals($get_roles_res['status'], 200);
        $this->assertEquals(count($get_roles_res['body']), 1);
        $this->assertEquals($get_roles_res['body'][0]['scope'], 'global');
        $this->assertEquals($get_roles_res['body'][0]['role_name'], $role_name);
        $this->assertEquals($get_roles_res['body'][0]['permissions'], ['room:update']);
    }

    public function testRemoveGlobalRoleForUserRaisesAnExceptionIfNoUserIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->removeGlobalRoleForUser([]);
    }

    public function testRemoveGlobalRoleForUserShouldReturnAResponsePayloadIfAUserIDIsProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $role_name = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_role_res = $this->chatkit->createGlobalRole([
            'name' => $role_name,
            'permissions' => ['room:update']
        ]);
        $this->assertEquals($create_role_res['status'], 201);

        $assign_role_res = $this->chatkit->assignGlobalRoleToUser([
            'name' => $role_name,
            'user_id' => $user_id
        ]);
        $this->assertEquals($assign_role_res['status'], 201);

        $remove_role_res = $this->chatkit->removeGlobalRoleForUser([ 'user_id' => $user_id ]);
        $this->assertEquals($remove_role_res['status'], 204);
        $this->assertEquals($remove_role_res['body'], null);
    }

    public function testRemoveRoomRoleForUserRaisesAnExceptionIfNoUserIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->removeRoomRoleForUser([ 'room_id' => '123' ]);
    }

    public function testRemoveRoomRoleForUserRaisesAnExceptionIfNoRoomIDIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->removeRoomRoleForUser([ 'user_id' => 'ham' ]);
    }

    public function testRemoveRoomRoleForUserShouldReturnAResponsePayloadIfAUserIDAndRoomIDAreProvided()
    {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        $role_name = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_role_res = $this->chatkit->createRoomRole([
            'name' => $role_name,
            'permissions' => ['room:update']
        ]);
        $this->assertEquals($create_role_res['status'], 201);

        $room_res = $this->chatkit->createRoom([
            'creator_id' => $user_id,
            'name' => 'my room'
        ]);
        $this->assertEquals($room_res['status'], 201);

        $assign_role_res = $this->chatkit->assignRoomRoleToUser([
            'name' => $role_name,
            'user_id' => $user_id,
            'room_id' => $room_res['body']['id']
        ]);
        $this->assertEquals($assign_role_res['status'], 201);

        $remove_role_res = $this->chatkit->removeRoomRoleForUser([
            'user_id' => $user_id,
            'room_id' => $room_res['body']['id']
        ]);
        $this->assertEquals($remove_role_res['status'], 204);
        $this->assertEquals($remove_role_res['body'], null);
    }

    public function testGetPermissionsForGlobalRoleRaisesAnExceptionIfNoNameIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->getPermissionsForGlobalRole([]);
    }

    public function testGetPermissionsForGlobalRoleShouldReturnAResponsePayloadIfANameIsProvided()
    {
        $role_name = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_role_res = $this->chatkit->createGlobalRole([
            'name' => $role_name,
            'permissions' => ['room:create']
        ]);
        $this->assertEquals($create_role_res['status'], 201);

        $get_perms_res = $this->chatkit->getPermissionsForGlobalRole([ 'name' => $role_name ]);
        $this->assertEquals($get_perms_res['status'], 200);
        $this->assertEquals($get_perms_res['body'], ['room:create']);
    }

    public function testGetPermissionsForRoomRoleRaisesAnExceptionIfNoNameIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->getPermissionsForRoomRole([]);
    }

    public function testGetPermissionsForRoomRoleShouldReturnAResponsePayloadIfANameIsProvided()
    {
        $role_name = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_role_res = $this->chatkit->createRoomRole([
            'name' => $role_name,
            'permissions' => ['room:update']
        ]);
        $this->assertEquals($create_role_res['status'], 201);

        $get_perms_res = $this->chatkit->getPermissionsForRoomRole([ 'name' => $role_name ]);
        $this->assertEquals($get_perms_res['status'], 200);
        $this->assertEquals($get_perms_res['body'], ['room:update']);
    }

    public function testUpdatePermissionsForGlobalRoleRaisesAnExceptionIfNoNameIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->updatePermissionsForGlobalRole([ 'permissions_to_add' => ['message:create'] ]);
    }

    public function testUpdatePermissionsForGlobalRoleRaisesAnExceptionIfNoPermissionsToAddOrRemoveAreProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->updatePermissionsForGlobalRole([ 'name' => 'test' ]);
    }

    public function testUpdatePermissionsForGlobalRoleShouldReturnAResponsePayloadIfANameIsProvided()
    {
        $role_name = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_role_res = $this->chatkit->createGlobalRole([
            'name' => $role_name,
            'permissions' => ['room:create']
        ]);
        $this->assertEquals($create_role_res['status'], 201);

        $update_perms_res = $this->chatkit->updatePermissionsForGlobalRole([
            'name' => $role_name,
            'permissions_to_add' => ['room:delete'],
            'permissions_to_remove' => ['room:create']
        ]);
        $this->assertEquals($update_perms_res['status'], 204);
        $this->assertEquals($update_perms_res['body'], null);

        $get_perms_res = $this->chatkit->getPermissionsForGlobalRole([ 'name' => $role_name ]);
        $this->assertEquals($get_perms_res['status'], 200);
        $this->assertEquals($get_perms_res['body'], ['room:delete']);
    }

    public function testUpdatePermissionsForRoomRoleRaisesAnExceptionIfNoNameIsProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->updatePermissionsForRoomRole([ 'permissions_to_add' => ['message:create'] ]);
    }

    public function testUpdatePermissionsForRoomRoleRaisesAnExceptionIfNoPermissionsToAddOrRemoveAreProvided()
    {
        $this->expectException(Chatkit\Exceptions\MissingArgumentException::class);
        $this->chatkit->updatePermissionsForRoomRole([ 'name' => 'test' ]);
    }

    public function testUpdatePermissionsForRoomRoleShouldReturnAResponsePayloadIfANameIsProvided()
    {
        $role_name = $this->guidv4(openssl_random_pseudo_bytes(16));
        $create_role_res = $this->chatkit->createRoomRole([
            'name' => $role_name,
            'permissions' => ['room:update']
        ]);
        $this->assertEquals($create_role_res['status'], 201);

        $update_perms_res = $this->chatkit->updatePermissionsForRoomRole([
            'name' => $role_name,
            'permissions_to_add' => ['message:create'],
            'permissions_to_remove' => ['room:update']
        ]);
        $this->assertEquals($update_perms_res['status'], 204);
        $this->assertEquals($update_perms_res['body'], null);

        $get_perms_res = $this->chatkit->getPermissionsForRoomRole([ 'name' => $role_name ]);
        $this->assertEquals($get_perms_res['status'], 200);
        $this->assertEquals($get_perms_res['body'], ['message:create']);
    }

    // call like this: guidv4(openssl_random_pseudo_bytes(16));

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

    protected function makeUser() {
        $user_id = $this->guidv4(openssl_random_pseudo_bytes(16));
        $user_res = $this->chatkit->createUser([
            'id' => $user_id,
            'name' => 'Ham'
        ]);
        $this->assertEquals($user_res['status'], 201);

        return $user_id;
    }

    protected function makeRoom($creator) {
        $room_res = $this->chatkit->createRoom([
            'creator_id' => $creator,
            'name' => 'my room'
        ]);
        $this->assertEquals($room_res['status'], 201);
        return $room_res['body']['id'];
    }
}
