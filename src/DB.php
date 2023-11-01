<?php

namespace Wpjscc\React\Orm;

use Illuminate\Database\Capsule\Manager as Capsule;
use Wpjscc\MySQL\Pool;
use React\MySQL\QueryResult;

class DB extends Capsule
{
    protected static $query;
    protected static $pool;

    protected static $callbacks = [];

    public static function init(Pool $pool = null)
    {

        static::$query = $query = new Query();
        static::$pool = $pool;

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


        // 同步执行的。马上返回
        Capsule::beforeExecuting(function ($sql, $bindings) use ($query) {
            Capsule::setPretending(); //让sql不执行
            $query->sql = $sql;
            $query->bindings = $bindings;
        });
    }

    public static function listen($callback) 
    {
        static::$callbacks[] = $callback;
    }



    public static function execute($query)
    {
        $sql = static::$query->sql;
        $bindings = static::$query->bindings;


        static::$query->sql = '';
        static::$query->bindings = [];
        // 时间
        $start = microtime(true);

        return static::$pool->query($sql, $bindings)->then(function (QueryResult $command) use ($sql, $bindings, $start) {
            $time = microtime(true) - $start;
            if (!empty(static::$callbacks)) {
                foreach (static::$callbacks as $callback) {
                    $callback($sql, $bindings, $time);
                }
            }
            return $command;
        });

    }

    public static function executeTL($query, $connection)
    {
        $sql = static::$query->sql;
        $bindings = static::$query->bindings;

        static::$query->sql = '';
        static::$query->bindings = [];

        // 时间
        $start = microtime(true);
        return $connection->query($sql, $bindings)->then(function (QueryResult $command) use ($sql, $bindings, $start) {
            $time = microtime(true) - $start;
            if (!empty(static::$callbacks)) {
                foreach (static::$callbacks as $callback) {
                    $callback($sql, $bindings, $time);
                }
            }
            return $command;
        });

    }


    public static function translation(callable $callable)
    {
       return static::$pool->translation($callable);
    }

}
