<?php

namespace local_telconfig;
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify

class api_client {
    public function post(string $url, array $data): string|false {
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => 5,
            ]
        ];
        $context = stream_context_create($options);
        return @file_get_contents($url, false, $context);
    }

    public function delete(string $url): string|false {
        $options = [
            'http' => [
                'method'  => 'DELETE',
                'timeout' => 5,
            ]
        ];
        $context = stream_context_create($options);
        return @file_get_contents($url, false, $context);
    }
}