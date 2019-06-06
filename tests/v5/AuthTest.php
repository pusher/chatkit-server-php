<?php

class AuthTest extends \Base {

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

}
