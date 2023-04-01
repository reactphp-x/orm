<?php

namespace Wpjscc\React\Orm;

use Illuminate\Database\Capsule\Manager as Capsule;

class DB extends Capsule
{
    public static function init($connection)
    {
        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'database',
            'username' => 'root',
            'password' => 'password',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        Capsule::macro('setPretending', function () {
            $this->pretending = true;
        });

        $query = new Query();


        Capsule::beforeExecuting(function ($sql, $bindings, $connection) use ($query) {
            Capsule::setPretending(); //让sql不执行
            $query->sql = $sql;
            $query->bindings = $bindings;
        });

        Capsule::macro('executeQuery', function () use ($query, $connection) {
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            $query->sql = '';
            $query->bindings = [];
            return $connection->query($sql, $bindings);
        });
        
    }
}
