<?php

namespace Reactphp\Framework\Orm\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Connection;
use Reactphp\Framework\Orm\AsyncMysqlConnection;
use Reactphp\Framework\Orm\AsyncMysqlConnector;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        Connection::resolverFor('async-mysql', function ($connection, $database, $prefix, $config) {
            return new AsyncMysqlConnection($connection, $database, $prefix, $config);
        });
        $this->app->bind(AsyncMysqlConnector::class, fn() => new AsyncMysqlConnector);
        $this->app->alias(AsyncMysqlConnector::class, 'db.connector.async-mysql'); 
    }
}
