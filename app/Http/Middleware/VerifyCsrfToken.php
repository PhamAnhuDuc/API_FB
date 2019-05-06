<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'user/search',
        'user/register',
        'user/login',
        'user/add-friend',
        'user/delete-friend',
        'post',
        'post/comment',
        'user/delete-friend/id',
    ];
}
