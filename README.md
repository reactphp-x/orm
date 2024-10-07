# reactphp-orm


## install

```
composer require reactphp-x/orm -vvv
```


## init

```
require __DIR__.'/vendor/autoload.php';

use ReactphpX\Orm\AsyncMysqlConnector;
use ReactphpX\Orm\AsyncMysqlConnection;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Connection;

Connection::resolverFor('async-mysql', function ($connection, $database, $prefix, $config) {
    return new AsyncMysqlConnection($connection, $database, $prefix, $config);
});
$container = new Container;
$container->bind(AsyncMysqlConnector::class, fn () => new AsyncMysqlConnector);
$container->alias(AsyncMysqlConnector::class, 'db.connector.async-mysql'); 

$db = new DB($container);

$db->addConnection([
    'driver' => 'async-mysql',
    'host' => getenv('MYSQL_HOST') ?: '',
    'port' => getenv('MYSQL_PORT') ?: '',
    'database' => getenv('MYSQL_DATABASE') ?: '',
    'username' => getenv('MYSQL_USER') ?: '',
    'password' => getenv('MYSQL_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
    'pool' => [
        'min_connections' => 2, // min 2 connection
        'max_connections' => 10, // max 10 connection
        'max_wait_queue' => 110, // how many sql in queue
        'wait_timeout' => 5,// wait time include response time
        'idle' => 60, // 
    ]
]);

$db->setAsGlobal();
$db->bootEloquent();
```

## how to use 

sync 使用 和 laravel 一样，可以参考 laravel 文档


async 使用

```php

DB::enableQueryLog();

$promises = [];

for ($i=0; $i < 10; $i++) { 
    $a = \React\Async\async(fn() => User::with('posts.comments', 'posts.tags')->get()->each(function ($user) {
        echo $user->name . PHP_EOL;
        $user->posts->each(function ($post) {
            echo $post->title . PHP_EOL;
            $post->comments->each(function ($comment) {
                echo $comment->content . PHP_EOL;
            });
        });
    }))();

    $promises[] = $a;
    
    
    $b = \React\Async\async(fn() => Post::with('user', 'comments', 'tags')->get()->each(function ($post) {
        echo $post->title . PHP_EOL;
        echo $post->user->name . PHP_EOL;
        $post->comments->each(function ($comment) {
            echo $comment->content . PHP_EOL;
        });
        $post->tags->each(function ($tag) {
            echo $tag->name . PHP_EOL;
        });
    }))();

    $promises[] = $b;
    
}

\React\Promise\all($promises)->then(function () {
    print_r(DB::getQueryLog());
});

```


## notice

transaction only support DB not support Model

```
DB::transaction(function ($db) {
    // importrant use $db not use DB
    $db->table('test_users')->insert([
        'name' => 'test-success',
    ]);
});


// or 

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

```

Recommended use `DB::transaction`


again notice

> 1. transaction only support DB not support Model



## License

MIT