<?php

namespace sparsely;

class Output
{
    public static function success(array $data)
    {
        $data = [
            'status' => 'success',
            'payload' => $data,
        ];
        self::output($data);
    }

    public static function error(array $data, string $message = "Error")
    {
        $data = [
            'status' => 'error',
            'message' => $message,
            'payload' => $data,
        ];
        self::output($data);
    }

    private static function output($data)
    {
        header('Content-Type: application/json');
        die(json_encode($data));
    }
}