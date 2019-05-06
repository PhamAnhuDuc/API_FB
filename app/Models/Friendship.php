<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Friendship extends Model
{
    protected $fillable = ['user_id', 'friend_id', 'status', 'best_friend_1', 'best_friend_2', 'blocked'];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }

    public function scopeCheckIsFriend($query, $user1, $user2)
    {
        /* 
         *   - giả sử user1 là userLogin, user2 là user cần check bạn
         *   - có 2 trường hợp để là bạn bè của nhau: 1 là userLogin gửi lời kết bạn đến user2 (TH1), 2 là user2 gửi lời mời kết bạn đến *      userLogin(TH2)
         *   - TH1: userLogin là user_id(db), user2 là friend_id(db), TH2: userLogin là friend_id(db), user2 là user_id(db)
        */
        return $query->where([['user_id', $user1], ['friend_id', $user2], ['status', 1]])
                     ->orWhere([['user_id', $user2], ['friend_id', $user1], ['status', 1]]);
    }

    public function scopeCheckExistRelationship($query, $user1, $user2)
    {
        return $query->where([['user_id', $user1], ['friend_id', $user2]])
                     ->orWhere([['user_id', $user2], ['friend_id', $user1]]);
    }

    public function scopeCheckIsBlocked($query, $user1, $user2)
    {
        return $query->where([['user_id', $user1], ['friend_id', $user2], ['status', 2]])
                     ->orWhere([['user_id', $user2], ['friend_id', $user1], ['status', 2]]);
    }

    //lấy ra tất cả các id là bạn bè
    public function scopeGetIdFriend($query, $user_id)
    {
        $listFriend1 = Friendship::where([['user_id', $user_id], ['status', 1]])->pluck('friend_id')->all();
        $listFriend2 = Friendship::where([['friend_id', $user_id], ['status', 1]])->pluck('user_id')->all();
        
        return array_merge($listFriend1, $listFriend2);
    }

    public function scopeGetIdUserBlock($query, $user_id)
    {
        $listBlocked1 = Friendship::where([['user_id', $user_id], ['status', 2], ['blocked', 1]])->pluck('friend_id')->all();
        $listBlocked2 = Friendship::where([['friend_id', $user_id], ['status', 2], ['blocked', 2]])->pluck('user_id')->all();
        
        return array_merge($listBlocked1, $listBlocked2);
    }
}
