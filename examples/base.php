<?php

require __DIR__.'/../vendor/autoload.php';

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
    'port' => getenv('MYSQL_PORT') ?: '3306',
    'database' => getenv('MYSQL_DATABASE') ?: '',
    'username' => getenv('MYSQL_USER') ?: '',
    'password' => getenv('MYSQL_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
    'pool' => [
        'min_connections' => 2, // min 2 connection
        'max_connections' => 10, // max 10 connection
        'max_wait_queue' => 110, // how many sql in queue
        'wait_timeout' => 5,// wait time include response time
        'keep_alive' => 10, // 
    ]
]);

$db->setAsGlobal();
$db->bootEloquent();


if (!DB::schema()->hasTable('test_users')){
    DB::schema()->create('test_users', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->timestamps();
    });
}

if (!DB::schema()->hasTable('test_posts')){
    DB::schema()->create('test_posts', function ($table) {
        $table->increments('id');
        $table->integer('user_id');
        $table->string('title');
        $table->timestamps();
    });
}

if (!DB::schema()->hasTable('test_comments')){
    DB::schema()->create('test_comments', function ($table) {
        $table->increments('id');
        $table->integer('post_id');
        $table->string('content');
        $table->timestamps();
    });
}

if (!DB::schema()->hasTable('test_tags')){
    DB::schema()->create('test_tags', function ($table) {
        $table->increments('id');
        $table->string('name');
        $table->timestamps();
    });
}

if (!DB::schema()->hasTable('test_post_tag')){
    DB::schema()->create('test_post_tag', function ($table) {
        $table->increments('id');
        $table->integer('post_id');
        $table->integer('tag_id');
        $table->timestamps();
    });
}



// fake data test_users

if (DB::table('test_users')->count() < 100) {
    for ($i=0; $i < 100; $i++) {
        DB::table('test_users')->insert([
            'name' => 'user' . $i,
        ]);
    }
}

// fake data test_posts

if (DB::table('test_posts')->count() < 100) {
    for ($i=0; $i < 100; $i++) {
        DB::table('test_posts')->insert([
            'user_id' => rand(1, 100),
            'title' => 'post' . $i,
        ]);
    }
}


// fake data test_comments

if (DB::table('test_comments')->count() < 100) {
    for ($i=0; $i < 100; $i++) {
        DB::table('test_comments')->insert([
            'post_id' => rand(1, 100),
            'content' => 'comment' . $i,
        ]);
    }
}

// fake data test_tags

if (DB::table('test_tags')->count() < 100) {
    for ($i=0; $i < 100; $i++) {
        DB::table('test_tags')->insert([
            'name' => 'tag' . $i,
        ]);
    }
}

// fake data test_post_tag

if (DB::table('test_post_tag')->count() < 100) {
    for ($i=0; $i < 100; $i++) {
        DB::table('test_post_tag')->insert([
            'post_id' => rand(1, 100),
            'tag_id' => rand(1, 100),
        ]);
    }
}


class User extends \Illuminate\Database\Eloquent\Model {
    protected $table = 'test_users';

    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}

class Post extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'test_posts';

    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'test_post_tag', 'post_id', 'tag_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

class Comment extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'test_comments';
}

class Tag extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'test_tags';

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'test_post_tag', 'tag_id', 'post_id');
    }
}



\React\EventLoop\Loop::addPeriodicTimer(1, function () {
    if (DB::getPdo()) {
        echo 'pool_count:'. DB::getPdo()->getPoolCount() . PHP_EOL;
        echo 'idleConnectionCount:'. DB::getPdo()->idleConnectionCount() . PHP_EOL;
    } else {
        var_dump('pool closed');
        \React\EventLoop\Loop::stop();
    }
   
});