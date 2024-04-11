<?php

class DotEnv
{
    /**
     * Loads .env file and set the $_ENV variable with the values
     * of the file.
     */
    public static function loadEnv()
    {
        $env = file_get_contents(dirname(__DIR__) . "/.env");
        $lines = explode("\n", $env);

        foreach ($lines as $line) {
            preg_match("/([^#]+)\=(.*)/", $line, $matches);

            if (!isset($_ENV[$matches[1]])) {
                $_ENV[$matches[1]] = trim($matches[2]);
            }
        }
    }
}
