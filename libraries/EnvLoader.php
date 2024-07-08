<?php

class EnvLoader {
    

    /**
     * Constructor to initialize the file path
     * 
     * @param string $filePath The path to the .env file
     */

    /**
     * Load and parse the .env file
     * 
     * @throws Exception if the .env file is not found
     */
    public static function load($filePath) {
        if (!file_exists($filePath)) {
            
            throw new Exception('.env file not found.');
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse the line
            list($key, $value) = explode('=', $line, 2);

            // Remove any whitespace
            $key = trim($key);
            $value = trim($value);

            // Set environment variable
            
            putenv(sprintf('%s=%s', $key, $value));
            
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}