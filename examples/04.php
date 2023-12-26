<?php

require __DIR__.'/base.php';

use Illuminate\Database\Capsule\Manager as DB;

// failed
$db = DB::beginTransaction();
try {
    $db->table('test_users')->insert([
        'name' => 'test-failed',
    ]);
    $db->table('test_users')->insert([
        'name44' => 'test',
    ]);
    $db->commit();
} catch (\Throwable $e) {
    $db->rollBack();
    var_dump($e->getMessage());
}

// true
var_dump(DB::table('test_users')->where('name', 'test-failed')->count() === 0);

echo 'pool_count:'. DB::getPdo()->getPoolCount() . PHP_EOL;
echo 'idleConnectionCount:'. DB::getPdo()->idleConnectionCount() . PHP_EOL;

// success
DB::table('test_users')->where('name', 'test-success')->delete();
$db = DB::beginTransaction();
try {
    $db->table('test_users')->insert([
        'name' => 'test-success',
    ]);
    $db->commit();
} catch (\Throwable $e) {
    $db->rollBack();
    var_dump($e->getMessage());
}
// true
var_dump(DB::table('test_users')->where('name', 'test-success')->count() === 1);

echo 'pool_count:'. DB::getPdo()->getPoolCount() . PHP_EOL;
echo 'idleConnectionCount:'. DB::getPdo()->idleConnectionCount() . PHP_EOL;


DB::disconnect();