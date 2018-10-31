<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Composer\Console\Application as ComposerApplication;
use Symfony\Component\Console\Input\ArrayInput;

class Launcher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'launch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Launch a brand new Laravel App';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('======================================================');
        $this->line('| Houston, ready to launch a brand new Laravel App ! |');
        $this->line('======================================================');
        $this->line('');
        $this->line('');

        $name = $this->ask('What is the app name ?');

        $directory = $this->ask('Where do you want to create the "' . $name . '" app ?');

        $appPath = $directory . DIRECTORY_SEPARATOR . $name;

        if (!is_dir($appPath)) {
            $this->info('Creating ' . $appPath);
            mkdir($appPath, 0755, true);
        } else {
            $this->error('The directory ' . $appPath . ' already exist');
            die;
        }

        $this->info('Creating Simple Laravel App in ' . $appPath);

        if (!$this->createComposerJson($name, $appPath)) {
            $this->error('Could not create composer.json file in ' . $appPath);
            die;
        }

        // Composer\Factory::getHomeDir() method
        // needs COMPOSER_HOME environment variable set
        putenv('COMPOSER_HOME=' . getcwd() . '/vendor/bin/composer');

        chdir($appPath);

        $composerApp = new ComposerApplication();
        $composerApp->setAutoExit(false); // prevent `$application->run` method from exitting the script

        // call `composer create project` command programmatically
        $input = new ArrayInput(array('command' => 'install'));
        $composerApp->run($input);

        $this->line("Done.");
    }

    protected function createComposerJson($name, $appPath)
    {
        $composerJson = [
            "name" => $name,
            "description" => "Project created with Apollo.",
            "keywords" => ["laravel", "apollo"],
            "type" => "project",
            "require" => [
                "php" => "^7.1.3",
                "laravel/framework" => "5.6.*",
                "laravel/tinker" => "1.0.*",
                "laravelcollective/html" => "5.6.*",
            ],
            "require-dev" => [
                "barryvdh/laravel-debugbar" => "3.2.*",
                "barryvdh/laravel-ide-helper" => "2.5.*",
                "fzaninotto/faker" => "1.8.*",
                "laravel/dusk" => "3.0.*",
                "mockery/mockery" => "1.1.*",
                "phpunit/phpunit" => "7.3.*"
            ],
            "autoload" => [
                "psr-4" => [
                    "App\\" => "app/"
                ]
            ],
            "autoload-dev" => [
                "psr-4" => [
                    "Tests\\" => "tests/"
                ]
            ],
            "minimum-stability" => "dev",
            "prefer-stable" => true
        ];

        $composerJson['description'] = $this->ask('Description of the project : ["Project created with Apollo"]') ?? "Project created with Apollo";

        $package = "";
        while (null !== $package) {
            $package = $this->ask('Add a specific package to the project (require) : [empty to skip]');
            $composerJson['require'][] = $package;
        }

        $devPackage = "";
        while (null !== $devPackage) {
            $devPackage = $this->ask('Add a specific dev package to the project (require-dev) : [empty to skip]');
            $composerJson['require-dev'][] = $devPackage;
        }

        return file_put_contents($appPath . DIRECTORY_SEPARATOR . 'composer.json', json_encode($composerJson, true));
    }
}
