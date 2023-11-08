<?php

namespace Wpjscc\React\Orm;

use Wpjscc\MySQL\Pool;
use Illuminate\Database\Connectors\ConnectorInterface;

class AsyncMysqlConnector implements ConnectorInterface
{

    protected static $pool;

    public function connect($config)
    {

        if (static::$pool) {
            return static::$pool;
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

        return static::$pool = new Pool(
            $username . ':' . $password . '@' . $host . '/' . $database . '?charset=' . $charset,
            $config['pool'] ?? []
        );
    }
}
