<?php

namespace App\Managers;

class Env
{
    public $envFile = [];
    public $flatEnvFile = [];
    public $appPath;
    public $protectedKeys = [
        'APP_KEY',
    ];

    function __construct($appPath)
    {
        $this->appPath = $appPath;
        $this->setEnvFile();
    }

    public function writeEnvFile()
    {
        $writeEnvFile = fopen($this->appPath . DIRECTORY_SEPARATOR . '.env', 'w');
        foreach ($this->envFile as $index => $block) {
            foreach ($block as $key => $value) {
                fwrite($writeEnvFile, $key . '=' . $value);
                fwrite($writeEnvFile, PHP_EOL);
            }
            //create a new jumpline for each subarray
            fwrite($writeEnvFile, PHP_EOL);
        }
        fclose($writeEnvFile);
        $this->setEnvFile();
    }

    public function getValue($value)
    {
        return $this->flatEnvFile[$value];
    }

    public function setEnvFile()
    {
        $envFile = explode(PHP_EOL, file_get_contents($this->appPath . DIRECTORY_SEPARATOR . '.env'));

        //move around the envFile
        $part = 0;
        foreach ($envFile as $index => $value) {
            $parsed = explode('=', $value);
            if (count($parsed) > 1) {
                $this->envFile[$part][$parsed[0]] = $parsed[1];
                $this->flatEnvFile[$parsed[0]] = $parsed[1];
            } else {
                //create a new subarray/line if there's a jumpline
                $part++;
            }
        }
    }
}
