<?php

class RoleTest extends \Base {

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

}
