<?php

namespace App\Managers;

class Database
{
    public $env;
    public $connection;

    public function __construct(Env $env = null)
    {
        if (null !== $env) {
            $this->env = $env;
        }
    }

    public function connect()
    {
        try{
            $conn = new \PDO($this->env->getValue('DB_CONNECTION') . ":host=" . $this->env->getValue('DB_HOST') . ":" . $this->env->getValue('DB_PORT'), $this->env->getValue('DB_USERNAME'), $this->env->getValue('DB_PASSWORD'));
            // set the PDO error mode to exception
            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            return "Database is not accessible with the provided env information : " . $e->getMessage();
        }

        $this->connection = $conn;
        return $conn;
    }

    public function create()
    {
        $sql = "CREATE DATABASE " . $this->env->getValue('DB_DATABASE');

        try{
            $this->connect()->exec($sql);
            return 'Database ' . $this->env->getValue('DB_DATABASE') . ' created on the fly !';
        }
        catch(\PDOException $e)
        {
            return 'Database ' . $this->env->getValue('DB_DATABASE') . ' could not be created on the fly : ' . $e->getMessage();
            return 'You may want to create it yourself to prevent Apollo from failing the other steps';
        }

        $conn = null;
    }
}
