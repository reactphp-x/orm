<?php

namespace ReactphpX\Orm;

use Illuminate\Database\Connection;
use Closure;
use Illuminate\Database\Query\Grammars\MySqlGrammar as QueryGrammar;
use Illuminate\Filesystem\Filesystem;
use PDOStatement;
use Illuminate\Database\Schema\MySqlBuilder;
use Illuminate\Database\Schema\Grammars\MySqlGrammar as SchemaGrammar;
use Illuminate\Database\Schema\MySqlSchemaState;
use Illuminate\Database\Capsule\Manager as DB;

class AsyncMysqlConnection extends Connection
{

    /**
     * Determine if the connected database is a MariaDB database.
     *
     * @return bool
     */
    public function isMaria()
    {
        $command = \React\Async\await($this->getPdo()->query("select version()"));
        $version = $command->resultRows[0]['version()'] ?? '';
        return str_contains($version, 'MariaDB');
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Illuminate\Database\Query\Grammars\MySqlGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\MySqlBuilder
     */
    public function getSchemaBuilder()
    {
        // throw new \Exception("Not implemented");
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new MySqlBuilder($this);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\MySqlGrammar
     */
    protected function getDefaultSchemaGrammar()
    {
        // throw new \Exception("Not implemented");
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the schema state for the connection.
     *
     * @param  \Illuminate\Filesystem\Filesystem|null  $files
     * @param  callable|null  $processFactory
     * @return \Illuminate\Database\Schema\MySqlSchemaState
     */
    public function getSchemaState(Filesystem $files = null, callable $processFactory = null)
    {
        return new MySqlSchemaState($this, $files, $processFactory);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\MySqlProcessor
     */
    protected function getDefaultPostProcessor()
    {
        return new MySqlProcessor;
    }

    /**
     * Get the Doctrine DBAL driver.
     *
     * @return \Illuminate\Database\PDO\MySqlDriver
     */
    protected function getDoctrineDriver()
    {
        throw new \Exception("Not implemented");
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) {
                return [];
            }
            return \React\Async\await($this->getPdoForSelect($useReadPdo)->query($query, $bindings))->resultRows ?? [];
        });
    }

    /**
     * Run a select statement against the database and returns a generator.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return \Generator
     */
    public function cursor($query, $bindings = [], $useReadPdo = true)
    {
        $stream = $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) {
                return [];
            }
            return $this->getPdoForSelect($useReadPdo)->queryStream($query, $bindings);
        });

        $queues = [];
        $deferred = new \React\Promise\Deferred();
        array_push($queues, $deferred);

        $stream->on('data', function ($data) use (&$queues, &$deferred) {
            $nextDeferred = new \React\Promise\Deferred();
            array_push($queues, $nextDeferred);
            $deferred->resolve($data);
            $deferred = $nextDeferred;
        });

        $stream->on('end', function () use (&$deferred) {
            $deferred->resolve(null);
        });

        $stream->on('error', function ($error) use (&$deferred) {
            $deferred->reject($error);
            throw $error;
        });

        while (count($queues) > 0) {
            $currentDeferred = array_shift($queues);
            $data = \React\Async\await($currentDeferred->promise());
            if ($data === null) {
                continue;
            }
            yield $data;
        }
    }


    /**
     * Configure the PDO prepared statement.
     *
     * @param  \PDOStatement  $statement
     * @return \PDOStatement
     */
    protected function prepared(PDOStatement $statement)
    {
        throw new \Exception("Not implemented");
    }

    /**
     * Get the PDO connection to use for a select query.
     *
     * @param  bool  $useReadPdo
     * @return \ReactphpX\MySQL\Pool;
     */
    protected function getPdoForSelect($useReadPdo = true)
    {
        return $useReadPdo ? $this->getReadPdo() : $this->getPdo();
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return bool | int
     */
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }
            $this->recordsHaveBeenModified();
            return \React\Async\await($this->getPdo()->query($query, $bindings))->insertId ?? false;
        });
    }


    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }
            $count =  \React\Async\await($this->getPdo()->query($query, $bindings))->affectedRows ?? 0;
            $this->recordsHaveBeenModified($count > 0);
            return $count;
        });
    }


    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param  string  $query
     * @return bool
     */
    public function unprepared($query)
    {
        return $this->run($query, [], function ($query) {
            if ($this->pretending()) {
                return true;
            }

            $this->recordsHaveBeenModified(
                $change = \React\Async\await($this->getPdo()->query($query)) !== false
            );

            return $change;
        });
    }


    /**
     * Disconnect from the underlying PDO connection.
     *
     * @return void
     */
    public function disconnect()
    {
        $this->getPdo()?->close();
        $this->getReadPdo()?->close();
        $this->setPdo(null)->setReadPdo(null);
        $this->doctrineConnection = null;
    }


    /**
     * Get the current PDO connection.
     *
     * @return \React\MySQL\ConnectionInterface;
     */
    public function getPdo()
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = call_user_func($this->pdo);
        }

        return $this->pdo;
    }

    /**
     * Get the current PDO connection used for reading.
     *
     * @return \React\MySQL\ConnectionInterface;
     */
    public function getReadPdo()
    {
        if ($this->transactions > 0) {
            return $this->getPdo();
        }

        if ($this->readOnWriteConnection ||
            ($this->recordsModified && $this->getConfig('sticky'))) {
            return $this->getPdo();
        }

        if ($this->readPdo instanceof Closure) {
            return $this->readPdo = call_user_func($this->readPdo);
        }

        return $this->readPdo ?: $this->getPdo();
    }

    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure  $callback
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(Closure $callback, $attempts = 1)
    {
        $that = $this;
        return $this->getPdo()->transaction(function ($connection) use ($callback, $that) {
            $db = clone $that;
            $db->setPdo($connection);
            return $callback($db, $connection);
        })->then(function ($data) {
            return $data;
        }, function($e) use ($attempts, $callback, $that) {
            if ($attempts > 1) {
                return $that->transaction($callback, $attempts - 1);
            }
            throw $e;
        });
    }

    /**
     * Start a new database transaction.
     *
     * @return self
     *
     * @throws \Throwable
     */
    public function beginTransaction()
    {

        if (!$this->getPdo()) {
            throw new Exception("no connection");
        }

        if (!($this->getPdo() instanceof \ReactphpX\MySQL\Pool)) {
            throw new Exception("had in transaction");
        }

        $connection = \React\Async\await($this->getPdo()->getConnection());
        \React\Async\await($connection->query('BEGIN'));
        $db = clone $this;
        $db->setPdo($connection);

        $this->fireConnectionEvent('beganTransaction');

        return $db;
    }

    public function commit()
    {
        if ($this->getPdo() && !($this->getPdo() instanceof \ReactphpX\MySQL\Pool)) {
            \React\Async\await($this->getPdo()->query('COMMIT'));
            DB::getPdo()->releaseConnection($this->getPdo());
        } else {
            return;
        }
        $this->fireConnectionEvent('committed');

    }

    /**
     * Rollback the active database transaction.
     *
     * @param  int|null  $toLevel
     * @return void
     *
     * @throws \Throwable
     */
    public function rollBack($toLevel = null)
    {
       if ($this->getPdo() && !($this->getPdo() instanceof \ReactphpX\MySQL\Pool)) {
            \React\Async\await($this->getPdo()->query('ROLLBACK'));
            DB::getPdo()->releaseConnection($this->getPdo());
        } else {
            return;
        }
        $this->fireConnectionEvent('rollingBack');
    }



}
