<?php

namespace App\Managers;

class Scaffolding
{
    public function __construct($appPath)
    {
        chdir($appPath);
    }

    public function generateFrontScaffolding($frontend)
    {
        exec("php artisan preset $frontend");
    }

    public function generateAuthSystem()
    {
        exec("php artisan make:auth");
    }
}
