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
}
