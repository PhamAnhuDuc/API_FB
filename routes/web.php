<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['middleware' => 'locale'], function() {
    Route::get('get-user/{id}', 'UserController@getUser');
    Route::group(['prefix' => 'user'], function(){
        Route::post('change-avatar', 'UserController@changeAvatar');
        Route::post('change-password', 'UserController@changePassword');
        Route::post('forgot-password', 'UserController@getAccessToken');
        Route::post('reset-password', 'UserController@resetPassword');
    });

    Route::post('add-friend', 'FriendshipController@addFriend');
    Route::post('add-blocked', 'FriendshipController@addBlocked');
    Route::get('list-friend', 'FriendshipController@getListFriend');
    Route::get('list-blocked', 'FriendshipController@getListBlocked');
    Route::delete('delete-friend/{id}', 'FriendshipController@deleteFriend');
    Route::post('accept-friend/{id}', 'FriendshipController@accept');
    Route::post('reject-friend/{id}', 'FriendshipController@reject');

    Route::post('post', 'PostController@store');
    Route::post('share/{id}', 'PostController@share');
    Route::get('post/{id}', 'PostController@show');
    Route::delete('delete-post', 'PostController@destroy');

    Route::get('list-comment/{postId}', 'CommentController@getListComment');
    Route::post('comment-post', 'CommentController@store');
    Route::post('edit-comment/{id}', 'CommentController@update');
    Route::delete('delete-comment/{id}', 'CommentController@destroy');

    Route::get('change-language', 'PageController@changeLanguage');
    Route::post('register', 'PageController@register');
    Route::get('verify/{access_token}', 'PageController@verify')->name('verify_email');
    Route::post('login', 'PageController@login');
    Route::get('search', 'PageController@search');
});

Route::get('test', 'TestController@run');
