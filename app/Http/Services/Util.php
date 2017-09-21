<?php

namespace App\Http\Services;

class Util
{
    public static function callTaskProcessor()
    {
        exec(env('PYTHON_COMMAND') . ' ' . env('DBMAN_SCRIPTS_PATH') . DIRECTORY_SEPARATOR . 'taskprocessor.py 2>&1');
    }
}