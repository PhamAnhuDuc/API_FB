<?php

use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Post;
use App\Models\Friendship;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        // $this->call(FriendshipsTableSeeder::class);
        // $this->call(PostsTableSeeder::class);
        // $this->call(ImagesTableSeeder::class);
        // $this->call(CommentsTableSeeder::class);
        // $this->call(MessagesTableSeeder::class);
    }
}

// insert user
class UsersTableSeeder extends Seeder
{
    
    public function run(Request $request)
    {
        $faker = Faker\Factory::create();

        for ($i = 1; $i <= 1000; $i++) {
            $avatar = '';
            if ($i % 3 == 0) {
                $avatar = 'avatar_1.jpg';
            } elseif ($i % 3 == 1) {
                $avatar = 'avatar_2.jpg';
            } else {
                $avatar = 'avatar_3.jpg';
            }

            $request->merge([
                'full_name'    => $faker->name,
                'email'        => $faker->unique()->email,
                'access_token' => str_random(100),
                'password'     => bcrypt('123456'),
                'phone'        => $faker->phoneNumber,
                'birthday'     => $faker->datetime,
                'address'      => $faker->address,
                'avatar'       => $avatar,
                'active'       => rand(0, 10) ? 1 : 0,
            ]);

            User::create($request->all());
        }
    }
}

// insert friendship
class FriendshipsTableSeeder extends Seeder
{

    public function run(Request $request)
    {
        $count = 0;

        $listIdUser  = User::whereActive(1)->orderBy('id')->pluck('id')->all();
        $countIdUser = count($listIdUser);

        while ($count < 3000) {
            $idUser   = $listIdUser[rand(0, $countIdUser - 1)];
            $idFriend = $listIdUser[rand(0, $countIdUser - 1)];
            
            // không được kết bạn cho chính mình
            if ($idFriend !== $idUser) {
                $checkIsFriend = Friendship::checkExistRelationship($idUser, $idFriend)->count();

                if (!$checkIsFriend) {
                    $count++;
                    $status = rand(0, 5); // 0 - lời mời kết bạn, 1 - bạn bè, 2 - chặn
                    if ($status > 2) {
                        $status = 1;
                    }
                    $best_friend_1 = $best_friend_2 = $blocked = 0;

                    if ($status === 1) {
                        $best_friend_1 = rand(0, 1); // 0 - user1 ko coi user2 là bạn thân, 1 - user2 coi user1 là bạn thân
                        $best_friend_2 = rand(0, 1); // 0 - user2 ko coi user1 là bạn thân, 1 - user1 coi user2 là bạn thân
                    }
                    if ($status === 2) {
                        $blocked = rand(1, 2); // 1 - user1 blocked user2, 2 - user2 blocked user1
                    }

                    $request->merge([
                        'user_id'       => $idUser,
                        'friend_id'     => $idFriend,
                        'status'        => $status,
                        'best_friend_1' => $best_friend_1,
                        'best_friend_2' => $best_friend_2,
                        'blocked'       => $blocked,
                    ]);
                    FriendShip::create($request->all());
                }
            }
        }
    }
}

// insert post
class PostsTableSeeder extends Seeder
{
    
    public function run(Request $request)
    {
        $count = 0;
        $content = '';
        $faker = Faker\Factory::create();

        $listIdPost = Post::orderBy('id')->pluck('id')->all();
        $stop = $countIdPost = count($listIdPost);

        $listIdUser = User::whereActive(1)->orderBy('id')->pluck('id')->all();
        $countIdUser = count($listIdUser);
     
        while (true) {
            $idAuthor = $listIdUser[rand(0, $countIdUser - 1)];
            $idTarget = $listIdUser[rand(0, $countIdUser - 1)];

            $checkIsFriend = Friendship::checkIsFriend($idAuthor, $idTarget)->count();

            if ($checkIsFriend || ($idAuthor == $idTarget)) {
                $status = rand(0, 2); // 0 - tất cả cùng được xem, 1 - bạn bè được xép xem, 2 - chỉ 1 mình xem

                $parentId = 0; // default = 0 => không chia sẻ
                $flag     = rand(0, 1);

                // chia sẻ, phải tồn tại $post_id mới cho chia sẻ, và không chia sẻ các bài đã được chia sẻ
                while (!$flag) {
                    // nếu chưa có bản ghi nào thì break để tạo bản ghi mới
                    if (!$countIdPost) {
                        break;
                    }
                    
                    $post_id = $listIdPost[rand(0, $countIdPost - 1)];
                    // tồn tại bài post
                    if ($post = Post::find($post_id)) {
                        // bài post đã được chia sẻ thì chắc chắn parent_id > 0
                        if ($post->parent_id) {
                            continue;
                        } else {
                            $parentId = $post_id;
                            $content = $post;
                            $flag = 1;
                        }
                    }
                }

                //chia sẻ
                if ($parentId) {    
                    $request->merge([
                        'author_id' => $idAuthor,
                        'target_id' => $idAuthor,
                        'content'   => $content,
                        'status'    => $status,
                        'parent_id' => $parentId,
                    ]);
                } else {
                    $request->merge([
                        'author_id' => $idAuthor,
                        'target_id' => $idTarget,
                        'content'   => $faker->text,
                        'status'    => $status,
                        'parent_id' => 0,
                    ]);
                }
                $newPost = Post::create($request->all());

                $listIdPost[] = $newPost->id;

                $countIdPost++;
                if ($countIdPost > $stop + 2000) {
                    break;
                } 
            }
        }
    }
}

// insert image
class ImagesTableSeeder extends Seeder
{
    
    public function run(Request $request)
    {
        $count  = 0;
        $faker  = Faker\Factory::create();

        $listIdRootPost = Post::whereParentId(0)->orderBy('id')->pluck('id')->all();
        $counIdRootPost = count($listIdRootPost);

        while ($count < 1000) {
            $postId = $listIdRootPost[rand(0, $counIdRootPost - 1)];

            $path = '';
            if ($count % 3 == 0) {
                $path = 'avatar_1.jpg';
            } elseif ($count % 3 == 1) {
                $path = 'avatar_2.jpg';
            } else {
                $path = 'avatar_3.jpg';
            }

            $request->merge([
                'path'    => $path,
                'post_id' => $postId,
            ]);
            Image::create($request->all());
            $count++;
        }
    }
}

// insert messages
class MessagesTableSeeder extends Seeder
{
    
    public function run(Request $request)
    {
        $count  = 0;
        $roomId = 0;
        $faker  = Faker\Factory::create();

        $listFriendship = Friendship::select('user_id', 'friend_id')->whereStatus(1)->orderBy('id')->get();
        $countIdFriendship = count($listFriendship);

        while ($count < 3000) {
            $rand = rand(0, $countIdFriendship - 1);

            if ($count % 2) {
                $idSend    = $listFriendship[$rand]->user_id;
                $idReceive = $listFriendship[$rand]->friend_id;
            } else {
                $idReceive = $listFriendship[$rand]->user_id;
                $idSend    = $listFriendship[$rand]->friend_id;
            }

            $request->merge([
                'user_send_id'    => $idSend,
                'user_recieve_id' => $idReceive,
                'message'         => $faker->text,
                'room_id'         => $roomId,
            ]);
            Message::create($request->all());
            $count++;
        }
    }
}

// insert comment
class CommentsTableSeeder extends Seeder
{
    
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

        while ($count < 3000) {
            $postId = $listIdPost[rand(0, $countIdPost - 1)];
            $userId = $listIdUserActive[rand(0, $countIdUserActive - 1)];

            $parentId = rand(0, 3) ? $listIdComment[rand(0, $countIdComment + $count - 1)] : 0;

            if ($parentId) {
                $postId = Comment::find($parentId)->post_id;
            }

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