<?php

namespace Wpjscc\React\Orm;

use Wpjscc\MySQL\Pool;
use Illuminate\Database\Connectors\ConnectorInterface;

class AsyncMysqlConnector implements ConnectorInterface
{

    public function connect($config)
    {
        $username = $config['username'];
        $password = $config['password'];
        $charset = $config['charset'] ?? 'utf8mb4';
        $host = $config['host'];
        $port = $config['port'] ?? '';
        if ($port) {
            $host .= ':' . $port;
        }
        $database = $config['database'];

        return new Pool(
            $username . ':' . $password . '@' . $host . '/' . $database . '?charset=' . $charset,
            $config['pool'] ?? []
        );
    }
}
