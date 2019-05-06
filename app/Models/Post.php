<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'author_id', 'target_id', 'content', 'parent_id', 'status',
    ];

    public function author()
    {
        return $this->belongsTo('App\Models\User', 'author_id', 'id');
    }

    public function target()
    {
        return $this->belongsTo('App\Models\User', 'target_id', 'id');
    }

    public function image()
    {
        return $this->hasMany('App\Models\Image', 'post_id', 'id');
    }
    
    public function comment()
    {
        return $this->hasMany('App\Models\Comment', 'post_id', 'id');
    }

    public function scopeFindById($query, $id)
    {
        return $query->with('author', 'target')->whereId($id)->first();
    }
}
