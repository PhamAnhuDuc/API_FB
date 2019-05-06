<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use App\Models\Image;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\Comment;
use Faker;

class TestController {

	public function run(Request $request)
    {
        $count    = 0;
        $parentId = 0;
        $faker    = Faker\Factory::create();

        $listIdPost  = Post::orderBy('id')->pluck('id')->all();
        $countIdPost = count($listIdPost);

        $listIdUserActive  = User::whereActive(1)->orderBy('id')->pluck('id')->all();
        $countIdUserActive = count($listIdUserActive);

        $listIdComment  = Comment::pluck('id')->all();
        $countIdComment = count($listIdComment);

        $postId = $listIdPost[rand(0, $countIdPost - 1)];
        $userId = $listIdUserActive[rand(0, $countIdUserActive - 1)];

        if ($countIdComment === 0) {
            $request->merge([
                'user_id'   => $userId,
                'content'   => $faker->text,
                'post_id'   => $postId,
                'parent_id' => $parentId,
            ]);
            $comment = Comment::create($request->all());
            $listIdComment[] = $comment->id;
            $countIdComment++;
        }

        while ($count < 1000) {
        	$postId = $listIdPost[rand(0, $countIdPost - 1)];
            $userId = $listIdUserActive[rand(0, $countIdUserActive - 1)];

            $parentId = $listIdComment[rand(0, $countIdComment + $count - 1)];

			$request->merge([
                'user_id'   => $userId,
                'content'   => $faker->text,
                'post_id'   => $postId,
                'parent_id' => $parentId,
            ]);
			$comment = Comment::create($request->all());
            $listIdComment[] = $comment->id;
            $count++;
        }
    }
}