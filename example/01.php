<?php

require __DIR__.'/base.php';

use Illuminate\Database\Capsule\Manager as DB;

// sync

DB::enableQueryLog();

User::with('posts.comments', 'posts.tags')->get()->each(function ($user) {
    echo $user->name . PHP_EOL;
    $user->posts->each(function ($post) {
        echo $post->title . PHP_EOL;
        $post->comments->each(function ($comment) {
            echo $comment->content . PHP_EOL;
        });
    });
});


Post::with('user', 'comments', 'tags')->get()->each(function ($post) {
    echo $post->title . PHP_EOL;
    echo $post->user->name . PHP_EOL;
    $post->comments->each(function ($comment) {
        echo $comment->content . PHP_EOL;
    });
    $post->tags->each(function ($tag) {
        echo $tag->name . PHP_EOL;
    });
});

print_r(DB::getQueryLog());
DB::disconnect();



// foreach (DB::table('blog_test')->cursor() as $cursor) {
//     echo $cursor->id . PHP_EOL;
// }

// print_r(DB::table('blog_test')->get()->toJson());
