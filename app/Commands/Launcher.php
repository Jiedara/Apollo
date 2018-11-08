<?php

namespace App\Commands;

use App\Managers\Composer;
use App\Managers\Database;
use App\Managers\Env;
use App\Managers\Scaffolding;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

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

    public $name;
    public $directory;
    public $appPath;

    public $envFile;

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

        // $this->name = "plop";
        // $this->directory = "/home/jiedara/Code";
        $this->appPath = "/home/jiedara/Code/plop";

        // $env = new Env($this->appPath);
        $scaffold = new Scaffolding($this->appPath);
        $this->laravelScaffold($scaffold);
        die;

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

        $composer = new Composer($this->appPath);

        $composer->createProject();

        $this->line("");
        $this->info("Basic Laravel application created.");

        $this->line("");
        $this->line("Now, let's update the composer.json file !");

        $this->addComposerPackages($composer);

        $this->line("");
        $this->line("Come and fill the .env file with your values !");

        $env = new Env($this->appPath);
        $this->fillEnvFile($env);

        $database = new Database($env);
        $this->createDatabase($database);

        $this->line("");
        $this->line("Let's take a look at Laravel scaffoldings");

        $scaffold = new Scaffolding($this->appPath);
        $this->laravelScaffold($scaffold);

        $this->line('~~~~');
        $this->line('~~~~~~~');
        $this->line('~~~~~~~~~~~~~');
        $this->line('Everything is set for now. Take a look at ' . this->appPath . ' !');
        $this->line('~~~~~~~~~~~~~~');
        $this->line('~~~~~~~');
        $this->line('~~~~');
    }

    public function addComposerPackages(Composer $composer)
    {
        $composerJson = $composer->json;
        $composerJson['description'] = $this->ask('Description of the project', "Project created with Apollo");

        $package = "";
        while (null !== $package) {
            $package = $this->ask('Add a production package to the project (require) [empty to skip]');
            if (null !== $package) {
                $packageVersion = $this->ask('What version to use for the package ' . $package . ' ?', '*');
                $composerJson['require'][$package] = $packageVersion;
            }
        }

        $devPackage = "";
        while (null !== $devPackage) {
            $devPackage = $this->ask('Add a dev package to the project (require-dev) [empty to skip]');
            if (null !== $devPackage) {
                $packageVersion = $this->ask('What version to use for the package ' . $package . ' ?', '*');
                $composerJson['require-dev'][$devPackage] = $packageVersion;
            }
        }

        if ($composer->updateComposerJson($composerJson) == 2) {
            $this->error("Some package are not valid. Let's try again !");
            $this->updateComposerJson($composer);
        } else {
            $composer->setJson();
        }
    }

    public function fillEnvFile(Env $env)
    {
        foreach ($env->envFile as $partKey => $partBlock) {
            $env->envFile[$partKey] = $this->fillEnvFilePart($env, $partKey);
        }

        $newKey = '';
        $newPart = count($env->envFile);
        while (null !== $newKey) {
            $newKey = $this->ask('Add a new key [empty to skip]');
            if (null !== $newKey) {
                $newValue = $this->ask('What value go with the ' . $newKey . ' key ?', '');
                $env->envFile[$newPart][$newKey] = $newValue;
            }
        }

        $env->writeEnvFile();
    }

    public function fillEnvFilePart(Env $env, $partKey)
    {
        $partBlock = [];
        foreach ($env->envFile[$partKey] as $key => $value) {
            if (!in_array($key, $env->protectedKeys)) {
                $partBlock[$key] = $this->ask('Value for ' . $key, $value);
            } else {
                $partBlock[$key] = $value;
            }
        }
        return $partBlock;
    }

    public function createDatabase(Database $database)
    {
        $this->info($database->create());
    }

    public function laravelScaffold(Scaffolding $scaffold)
    {
        $frontend = $this->choice('What frontend environment do you want ?', ['none', 'bootstrap', 'vue', 'react'], 1);
        $scaffold->generateFrontScaffolding($frontend);

        $this->line('Laravel frontend preset set to ' . ucfirst($frontend));

        $authSystem = $this->choice('Do you want the classic Laravel Auth system in the app ?', ['no', 'yes'], 1);
        if ($authSystem === 'yes') {
            $scaffold->generateAuthSystem();
            $this->line('Laravel Auth system deployed');
        }
    }
}
