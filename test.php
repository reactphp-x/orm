<?php

require './vendor/autoload.php';

use Wpjscc\React\Orm\DB;
use React\MySQL\QueryResult;

$connection = (new \React\MySQL\Factory())->createLazyConnection('username:password@host/databasename');

DB::init($connection);

query();

function query() {
    for ($i=0; $i < 90; $i++) { 
        DB::executeQuery(DB::table('blog')->where('id', $i)->first())->then(function (QueryResult $command) use ($i) {
            echo "query:$i\n";
            if (isset($command->resultRows)) {
                // this is a response to a SELECT etc. with some rows (0+)
                // print_r($command->resultFields);
                // print_r($command->resultRows[0]['id'] ?? '');
                echo count($command->resultRows) . ' row(s) in set' . PHP_EOL;
            } else {
                // this is an OK message in response to an UPDATE etc.
                if ($command->insertId !== 0) {
                    var_dump('last insert ID', $command->insertId);
                }
                echo 'Query OK, ' . $command->affectedRows . ' row(s) affected' . PHP_EOL;
            }
        }, function (\Exception $error) {
            // the query was not executed successfully
            echo 'Error: ' . $error->getMessage() . PHP_EOL;
        });
        
    }
}