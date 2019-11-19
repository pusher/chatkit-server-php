<?php

use Firebase\JWT\JWT;

class GenerateTokenTest extends \Base {

    public function testGenerateTokenGeneratesATokenWithoutAnyException()
    {
        $jwt = $this->chatkit->generateToken();
        $this->assertArrayHasKey('token', $jwt);
        $this->assertArrayHasKey('expires_in', $jwt);
    }

    public function testGenerateTokenGeneratesATokenWithTheNeededFields()
    {
        $split_key = explode(':', CHATKIT_INSTANCE_KEY);
        $jwt_key = $split_key[1];

        $token = $this->chatkit->generateToken()['token'];
        $parsed_jwt = JWT::decode($token, $jwt_key, array('HS256'));

        $this->assertObjectHasAttribute('instance', $parsed_jwt);
        $this->assertObjectHasAttribute('iss', $parsed_jwt);
        $this->assertObjectHasAttribute('iat', $parsed_jwt);
        $this->assertObjectHasAttribute('exp', $parsed_jwt);

        $this->assertLessThanOrEqual(time(), $parsed_jwt->iat);

        // check exp is within 5s of the expected 24h window
        $this->assertLessThanOrEqual(5, abs(time() + 24 * 60 * 60 - $parsed_jwt->exp));
    }

}
