<?php

// namespace libraries;
class Response {
    public static function set($array) {
        
        http_response_code($array['statusCode'] ?? 500);

        
        echo json_encode($array);
    }
}


