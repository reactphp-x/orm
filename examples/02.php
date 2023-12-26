<?php

require __DIR__.'/base.php';

use Illuminate\Database\Capsule\Manager as DB;

// async

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
    DB::disconnect();
});

