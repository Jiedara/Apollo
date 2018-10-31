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

    public $composerApp;

    public $name;
    public $directory;
    public $appPath;

    public $envFile;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->composerApp = new ComposerApplication();
        $this->composerApp->setAutoExit(false); // prevent `$application->run` method from exitting the script

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

        // $this->name = "plopiplop";
        // $this->directory = "/home/jiedara/Code";
        // $this->appPath = "/home/jiedara/Code/plopiplop";

        // // Composer\Factory::getHomeDir() method
        // // needs COMPOSER_HOME environment variable set
        // putenv('COMPOSER_HOME=' . getcwd() . '/vendor/bin/composer');

        // chdir($this->appPath);
        // $this->laravelScaffold();
        // die;

        $this->name = $this->ask('What is the app name ?');

        $this->directory = $this->ask('Where do you want to create the "' . $this->name . '" app ?');

        $this->appPath = $this->directory . DIRECTORY_SEPARATOR . $this->name;

        if (!is_dir($this->appPath)) {
            $this->info('Creating ' . $this->appPath);
            mkdir($this->appPath, 0755, true);
        } else {
            $this->error('The directory ' . $this->appPath . ' already exist');
            die;
        }

        $this->info('Creating Simple Laravel App in ' . $this->appPath);

        // Composer\Factory::getHomeDir() method
        // needs COMPOSER_HOME environment variable set
        putenv('COMPOSER_HOME=' . getcwd() . '/vendor/bin/composer');

        chdir($this->appPath);

        $this->createLaravelApp();

        $this->line("");
        $this->info("Basic Laravel application created.");

        $this->line("");
        $this->line("Now, let's update the composer.json file !");

        $this->updateComposerJson();

        $this->line("");
        $this->line("Come and fill the .env file with your values !");

        $this->fillEnvFile();

        $this->line("");

        $this->createDatabase();

        $this->line("");
        $this->line("Let's take a look at Laravel scaffoldings");

        $this->laravelScaffold();
    }

    public function fillEnvFile()
    {
        $protectedKeys = [
            'APP_KEY',
        ];

        $envFile = explode(PHP_EOL, file_get_contents($this->appPath . DIRECTORY_SEPARATOR . '.env'));
        $envFileArray = [];

        //move around the envFile and create a key/value array
        $part = 0;
        foreach ($envFile as $index => $value) {
            if (count($parsed = explode('=', $value)) > 1) {
                $default = substr($value, strlen($parsed[0])+1);
                //Ask to change value if possible
                if (!in_array($parsed[0], $protectedKeys)) {
                    $default = $this->ask('Value for ' . $parsed[0], $default);
                }
                $envFileArray[$part][$parsed[0]] = $default;
            } else {
                //create a new subarray if there's a jumpline
                $part++;
            }
        }

        $this->info('Default .env file filled.');

        //new part for custom key/values
        $part++;

        $newKey = '';
        while (null !== $newKey) {
            $newKey = $this->ask('Add a new key [empty to skip]');
            if (null !== $newKey) {
                $newValue = $this->ask('What value go with the ' . $newKey . ' key ?', '');
                $envFileArray[$part][$newKey] = $newValue;
            }
        }

        $this->envFile = array_collapse($envFileArray);

        $writeEnvFile = fopen($this->appPath . DIRECTORY_SEPARATOR . '.env', 'w');
        foreach ($envFileArray as $part => $values) {
            foreach ($values as $key => $value) {
                fwrite($writeEnvFile, $key . '=' . $value);
                fwrite($writeEnvFile, PHP_EOL);
            }
            fwrite($writeEnvFile, PHP_EOL);
        }
        fclose($writeEnvFile);
    }

    public function createLaravelApp()
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
    }

    public function createDatabase()
    {
        $connection = $this->envFile['DB_CONNECTION'];
        $host = $this->envFile['DB_HOST'];
        $port = $this->envFile['DB_PORT'];
        $database = $this->envFile['DB_DATABASE'];
        $username = $this->envFile['DB_USERNAME'];
        $password = $this->envFile['DB_PASSWORD'];

        try{
            $conn = new \PDO("$connection:host=$host:$port", $username, $password);
            // set the PDO error mode to exception
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $sql = "CREATE DATABASE $database";
            // use exec() because no results are returned
            $conn->exec($sql);
            $this->info('Database ' . $database . ' created on the fly !');
        }
        catch(\PDOException $e)
        {
            $this->info('Database ' . $database . ' could not be created on the fly : ' . $e->getMessage());
            $this->info('You may want to create it yourself to prevent Apollo from failing the other steps');
        }

        $conn = null;
    }

    public function updateComposerJson($composerJson = null)
    {
        if (null === $composerJson) {
            $composerJson = json_decode(file_get_contents($this->appPath . DIRECTORY_SEPARATOR . 'composer.json'), true);
        } else {
            file_put_contents($this->appPath . DIRECTORY_SEPARATOR . 'composer.json', json_encode($composerJson, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT));
        }

        $backupComposerJson = $composerJson;

        $composerJson['description'] = $this->ask('Description of the project', "Project created with Apollo");

        $package = "";
        while (null !== $package) {
            $package = $this->ask('Add a specific package to the project (require) [empty to skip]');
            if (null !== $package) {
                $packageVersion = $this->ask('What version to use for the package ' . $package . ' ?', '*');
                $composerJson['require'][$package] = $packageVersion;
            }
        }

        $devPackage = "";
        while (null !== $devPackage) {
            $devPackage = $this->ask('Add a specific dev package to the project (require-dev) [empty to skip]');
            if (null !== $devPackage) {
                $packageVersion = $this->ask('What version to use for the package ' . $package . ' ?', '*');
                $composerJson['require-dev'][$devPackage] = $packageVersion;
            }
        }

        file_put_contents($this->appPath . DIRECTORY_SEPARATOR . 'composer.json', json_encode($composerJson, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT));

        if ($composerJson['require'] !== $backupComposerJson['require'] || $composerJson['require-dev'] !== $backupComposerJson['require-dev'] ) {
            // call `composer update` command programmatically
            $input = new ArrayInput(
                array(
                    'command' => 'update',
                )
            );
            //composerApp return 2 when there's an error with the packages
            if($this->composerApp->run($input) == 2) {
                $this->error("Some package are not valid. Let's try again !");
                $this->updateComposerJson($backupComposerJson);
            }
        }
    }

    public function laravelScaffold()
    {
        $frontend = $this->choice('What frontend environment do you want ?', ['none', 'bootstrap', 'vue', 'react'], 1);
        exec("php artisan preset $frontend");

        $this->line('Laravel frontend preset set to ' . ucfirst($frontend));

        $authSystem = $this->choice('Do you want the classic Laravel Auth system in the app ?', ['no', 'yes'], 1);
        if ($authSystem === 'yes') {
            exec("php artisan make:auth");
            $this->line('Laravel Auth system deployed');
        }
    }
}
