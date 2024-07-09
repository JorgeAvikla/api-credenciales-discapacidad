<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;




function getKey()
{
    return config('JWT')->secretKey;
}
function getAuthorizationToken($request)
{
    $authHeader = $request->header("Authorization");
    if ($authHeader === NULL) {
        return null;
    }
    $authHeader = $authHeader->getValue();
    return $authHeader;
}
function validateJWT($token)
{
    $key = getKey();
    try {
        return JWT::decode($token, new Key($key, 'HS256'));
    } catch (Exception $ex) {
        return null;
    }
}
