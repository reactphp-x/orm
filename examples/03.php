<?php

require __DIR__.'/base.php';

use Illuminate\Database\Capsule\Manager as DB;


DB::table('test_users')->whereIn('name', [
    'test-failed',
    'test-success',
])->delete();

// failed
DB::transaction(function ($db) {
    $db->table('test_users')->insert([
        'name' => 'test-failed',
    ]);
    $db->table('test_users')->insert([
        'name44' => 'test',
    ]);
})->then(function ($result) {
    var_dump($result);
}, function ($e) {
    var_dump($e->getMessage(), 312312);
});

// success
DB::transaction(function ($db) {
    $db->table('test_users')->insert([
        'name' => 'test-success',
    ]);
});

\React\EventLoop\Loop::addTimer(3, function () {

    \React\Async\async(function () {
        var_dump(DB::table('test_users')->where('name', 'test-failed')->count() === 0);
        var_dump(DB::table('test_users')->where('name', 'test-success')->count() === 1);
        DB::disconnect();
    })();

});

