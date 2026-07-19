<?php

namespace App\Core;

class Translator
{
    private static array $messages = [];

    public static function load(string $lang): void
    {
        $file = dirname(__DIR__, 2) . "/lang/{$lang}.php";
        if (file_exists($file)) {
            self::$messages = require $file;
        }
    }

    public static function get(string $key, array $params = []): string
    {
        $text = self::$messages[$key] ?? $key;
        foreach ($params as $k => $v) {
            $text = str_replace(":$k", $v ?: '', $text);
        }
        return $text;
    }
}