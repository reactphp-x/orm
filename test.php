<?php

require './vendor/autoload.php';

use Wpjscc\React\Orm\DB;
use Wpjscc\MySQL\Pool;
use React\MySQL\QueryResult;
use React\EventLoop\Loop;

$loop = Loop::get();


$pool = new Pool('username:password@host/databasename', [
    'max_connections' => 10, // 10 connection --default 10
    'max_wait_queue' => 110, // how many sql in queue --default 50
    'wait_timeout' => 5,// wait time include response time --default 0
]);

DB::init($pool);

$table = 'blog_test';


$queryResult = function (QueryResult $command) {
    if (isset($command->resultRows)) {
        // this is a response to a SELECT etc. with some rows (0+)
        // print_r($command->resultFields);
        print_r($command->resultRows);
        echo count($command->resultRows) . ' row(s) in set' . PHP_EOL;
    } else {
        // this is an OK message in response to an UPDATE etc.
        if ($command->insertId !== 0) {
            echo 'last insert ID:'.$command->insertId.PHP_EOL;
        }
        echo 'Query OK, ' . $command->affectedRows . ' row(s) affected' . PHP_EOL;
    }
};


# create
DB::execute(
    DB::table($table)->insert([
        [
            'content' => 'hello world'
        ],
        [
            'content' => 'hello world'
        ]
    ])
)->then($queryResult);

# select
DB::execute(
    DB::table($table)->first()
)->then($queryResult);

# update
DB::execute(
    DB::table($table)->first()
)->then($queryResult);

# delete
DB::execute(
    DB::table($table)
    ->where('id', 1)->delete()
)->then($queryResult);

# upsert
DB::execute(
    DB::table($table)
    ->upsert([
        'id' => 2,
        'content' => 'hello world2'
    ], [
        'id'
    ], [
        'content'
    ])
)->then($queryResult);


# translation
DB::translation(function ($connection) use ($queryResult, $table) {

    $insert1 = \React\Async\await(DB::executeTL(DB::table($table)->insert([
        'content' => 'hi1'
    ]), $connection));
    $queryResult($insert1);

    $insert2 = \React\Async\await(DB::executeTL(DB::table($table)->insert([
        'content' => 'hi2'
    ]), $connection));

    $queryResult($insert2);
    
    return [
        (array) $insert1,
        (array) $insert2,
    ];
   
})->then(function ($res) {
    var_dump($res,'suuccess');
}, function ($error) {
    var_dump($error->getMessage(), 'i');
});


# translation-fail
DB::translation(function ($connection) use ($table) {

    \React\Async\await(DB::executeTL(DB::table($table)->insert([
        'content' => 'hi3'
    ]), $connection));

    throw new \Exception("Error Processing Request");
    
})->then(function ($res) {
    var_dump($res,'fail');
}, function ($error) {
    var_dump($error->getMessage(), 'fail');
});



$loop->addPeriodicTimer(2, function () {
    echo "hello world\n";
});
