<?php

namespace AppBundle;

use Composer\Script\Event;

class ScriptHandler
{
    public static function createCacheDirs(Event $evt)
    {
        $base = 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
        $envs = ['dev', 'test', 'prod'];

        foreach ($envs as $env) {
            $path = realpath($base . $env);

            if (file_exists($path) && is_dir($path)) {
                mkdir($path . DIRECTORY_SEPARATOR . 'app', 0760, true);
            }
        }
    }
}
