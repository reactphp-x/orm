<?php

namespace ReactphpX\Orm;

use ReactphpX\MySQL\Pool;
use Illuminate\Database\Connectors\ConnectorInterface;

class AsyncMysqlConnector implements ConnectorInterface
{

    protected static $connectToPoll;

    public function connect($config)
    {

        if (isset(static::$connectToPoll[$config['name']])) {
            return static::$connectToPoll[$config['name']];
        }

        $username = $config['username'];
        $password = $config['password'];
        $charset = $config['charset'] ?? 'utf8mb4';
        $host = $config['host'];
        $port = $config['port'] ?? '';
        if ($port) {
            $host .= ':' . $port;
        }
        $database = $config['database'];

        return static::$connectToPoll[$config['name']] = new Pool(
            $username . ':' . $password . '@' . $host . '/' . $database . '?charset=' . $charset.'&idle='.($config['idle'] ?? 30),
            $config['pool']['min_connections'] ?? 1,
            $config['pool']['max_connections'] ?? 10,
            $config['pool']['max_wait_queue'] ?? 500,
            $config['pool']['wait_timeout'] ?? 0,
        );
    }
}
