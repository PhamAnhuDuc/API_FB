<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'full_name', 'email', 'password', 'phone', 'address', 'avatar', 'active', 'access_token',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'access_token',
    ];

    public function friendship()
    {
        return $this->hasMany('App\Models\Friendship', 'user_id', 'id');
    }

    public function comment()
    {
        return $this->hasMany('App\Models\Comment', 'user_id', 'id');
    }

    public function postTarget()
    {
        return $this->hasMany('App\Models\Post', 'target_id', 'id');
    }

    public function postUser()
    {
        return $this->hasMany('App\Models\Post', 'user_id', 'id');
    }

    public function scopeSearch($query, $value)
    {
        return $query->where('email', 'LIKE' , "%{$value}%")
                     ->orWhere('full_name', 'LIKE', "%{$value}%")
                     ->orWhere('phone', $value)
                     ->whereActive(1)->paginate(10);
    }

    public function scopeFindUserActive($query, $userId)
    {
        if (is_array($userId)) {
            return $query->whereActive(1)->whereIn('id', $userId)->get();
        }

        $result = $query->where([['id', $userId], ['active', 1]])->first();
        if ($result === null) {
            return false;
        }

        return $result;
    }
}
