<?php

namespace App\Managers;

use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

class Composer
{
    public $composerApp;
    public $appPath;

    public $json = [];

    function __construct($appPath)
    {
        $this->composerApp = new Application();
        $this->composerApp->setAutoExit(false); // prevent `$application->run` method from exitting the script

        $this->appPath = $appPath;

        // Composer\Factory::getHomeDir() method
        // needs COMPOSER_HOME environment variable set
        putenv('COMPOSER_HOME=' . getcwd() . '/vendor/bin/composer');
        chdir($appPath);
    }

    public function setJson()
    {
        $this->json = json_decode(file_get_contents($this->appPath . DIRECTORY_SEPARATOR . 'composer.json'), true);
    }

    public function createProject()
    {
        // call `composer create project` command programmatically
        $input = new ArrayInput(
            array(
                'command' => 'create-project',
                'package' => 'laravel/laravel',
                'directory' => $this->appPath
            )
        );
        $this->composerApp->run($input);
        $this->setJson();
    }

    public function updateComposerJson($composerJson = null)
    {
        file_put_contents($this->appPath . DIRECTORY_SEPARATOR . 'composer.json', json_encode($composerJson, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT));

        if ($composerJson['require'] !== $this->json['require'] || $composerJson['require-dev'] !== $this->json['require-dev'] ) {

            // call `composer update` command programmatically
            $input = new ArrayInput(
                array(
                    'command' => 'update',
                )
            );

            //composerApp return 2 when there's an error with the packages
            return $this->composerApp->run($input);
        }
    }
}
