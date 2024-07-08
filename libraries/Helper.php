<?php

use Firebase\JWT\JWT;
use Firebase\JWT\KEY;

class Helper
{
    public static function sanitize($data)
    {
        $data = trim($data);
        $data = htmlspecialchars($data);
        $data = stripslashes($data);
        return $data;
    }
    public static function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function encryptPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    public static function decryptPassword($password, $hash)
    {

        return password_verify($password, $hash);
    }
    public static function generateJWT($payload)
    {
        $setAt = time();
        $expiresAt = $setAt + (3600 * getenv('JWTEXP'));
        $payload['iat'] = $setAt;
        $payload['exp'] = $expiresAt;

        $jwt = JWT::encode($payload, getenv('JWTKEY'), 'HS256');

        return $jwt;
    }

    public static function validateJWT($token)
    {
        try {
            // Retrieve JWT key from environment variables
            $key = getenv("JWTKEY");

            // Decode JWT token
            $decoded = JWT::decode($token, new Key($key, 'HS256'));

            // Return success response with decoded data
            return [
                'state' => true,
                'data' => $decoded
            ];
        } catch (\Exception $e) {
            // Return failure response with error message
            return [
                'state' => false,
                'data' => $e->getMessage()
            ];
        }
    }


    public static function createResponse($statusCode, $message, $status)
    {
        // Prepare response array
        return [
            "statusCode" => $statusCode,
            "message" => $message,
            "status" => $status
        ];
    }

    /**
     * Retrieve the JWT token from the Authorization header.
     *
     * @return string|null JWT token if found, null otherwise.
     */
    public static function getBearerToken()
    {
        // Retrieve all HTTP request headers
        $headers = apache_request_headers();

        // Check if the Authorization header exists
        if (!empty($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];

            // Extract the token part of the Authorization header
            if (preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
                return $matches[1]; // Return the token if found
            }
        }

        return null; // Return null if no token is found
    }


  


}