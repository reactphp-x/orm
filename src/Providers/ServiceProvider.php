<?php

namespace ReactphpX\Orm\Providers;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\Database\Connection;
use ReactphpX\Orm\AsyncMysqlConnection;
use ReactphpX\Orm\AsyncMysqlConnector;

class ServiceProvider extends BaseServiceProvider
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
