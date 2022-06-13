<?php
namespace Firebase\JWT;

use \Firebase\JWT\JWT;

if (!function_exists("generateToken")) {
function generateToken( $email )
{
    $key = getenv('JWT_SECRET');
    $iat = time(); // current timestamp value
    $exp = $iat + 3600;

    $payload = array(
        "iat" => $iat, //Time the JWT issued at
        "exp" => $exp, // Expiration time of token
        "email" => $email,
    );
    return $token = JWT::encode($payload, $key, 'HS256');
}
}