<?php

class UserV3Test extends \Base {

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
}
