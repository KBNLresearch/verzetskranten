<?php

namespace AppBundle;

use Composer\Script\Event;

class ScriptHandler
{
    public static function createCacheDirs(Event $evt)
    {
        $base = 'var/cache/';
        $envs = ['dev', 'test', 'prod'];

        foreach ($envs as $env) {
            $path = realpath($base . $env);

            if (file_exists($path) && is_dir($path)) {
                mkdir($path . '/app', 0760, true);
            }
        }
    }
}
