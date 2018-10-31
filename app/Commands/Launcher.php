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

        $this->name = "plopiplop";
        $this->directory = "/home/jiedara/Code";
        $this->appPath = "/home/jiedara/Code/plopiplop";

        // Composer\Factory::getHomeDir() method
        // needs COMPOSER_HOME environment variable set
        putenv('COMPOSER_HOME=' . getcwd() . '/vendor/bin/composer');

        chdir($this->appPath);

        $this->updateComposerJson();
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

        // Composer\Factory::getHomeDir() method
        // needs COMPOSER_HOME environment variable set
        putenv('COMPOSER_HOME=' . getcwd() . '/vendor/bin/composer');

        chdir($this->appPath);

        $this->createLaravelApp();

        $this->line("");
        $this->info("Basic Laravel application created.");

        $this->line("");
        $this->line("Now, let's personalize it !");

        $this->updateComposerJson();

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

    protected function updateComposerJson($composerJson = null)
    {
        if (null === $composerJson) {
            $composerJson = json_decode(file_get_contents($this->appPath . DIRECTORY_SEPARATOR . 'composer.json'), true);
        } else {
            file_put_contents($this->appPath . DIRECTORY_SEPARATOR . 'composer.json', json_encode($composerJson, JSON_UNESCAPED_SLASHES + JSON_PRETTY_PRINT));
        }

        $backupComposerJson = $composerJson;

        $composerJson['description'] = $this->ask('Description of the project ["Project created with Apollo"]') ?? "Project created with Apollo";

        $package = "";
        while (null !== $package) {
            $package = $this->ask('Add a specific package to the project (require) [empty to skip]');
            if (null !== $package) {
                $packageVersion = $this->ask('What version to use for the package ' . $package . ' ? [*]') ?? "*";
                $composerJson['require'][$package] = $packageVersion;
            }
        }

        $devPackage = "";
        while (null !== $devPackage) {
            $devPackage = $this->ask('Add a specific dev package to the project (require-dev) [empty to skip]');
            if (null !== $devPackage) {
                $packageVersion = $this->ask('What version to use for the package ' . $package . ' ? [*]') ?? "*";
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
}
