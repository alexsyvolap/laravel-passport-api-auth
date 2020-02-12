<?php

use App\Enums\UserRoles;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'prefix' => 'v1',
    'as' => 'v1.',
    'namespace' => 'API\v1'
], function () {
    Route::group([
        'prefix' => 'auth',
        'as' => 'auth.',
        'namespace' => 'Auth'
    ], function () {
        Route::post('login', 'AuthController@login')->name('login');
        Route::post('signUp', 'AuthController@signUp')->name('signUp');
        Route::get('signUp/activate/{token}', 'AuthController@signUpActivate')->name('signUp.activate');

        Route::group([
            'middleware' => 'auth:api'
        ], function() {
            Route::get('logout', 'AuthController@logout')->name('logout');
            Route::get('user', 'AuthController@user')->name('user.information');
        });

        Route::group([
            'middleware' => 'api',
            'as' => 'password.',
            'prefix' => 'password',
        ], function() {
            Route::post('create', 'PasswordResetController@create')->name('create');
            Route::get('find/{token}', 'PasswordResetController@find')->name('find');
            Route::post('reset', 'PasswordResetController@reset')->name('reset');
        });
    });

    Route::group([
        'middleware' => 'auth:api'
    ], function () {
        Route::group([
            'prefix' => 'admin',
            'as' => 'admin.',
            'namespace' => 'Admin',
            'middleware' => 'scope:' . UserRoles::getInstance(UserRoles::Administrator)->key
        ], function () {
            Route::get('', function (Request $request) { return $request->user(); })->name('testRoute');
        });

        Route::group([
            'prefix' => 'subscriber',
            'as' => 'subscriber.',
            'namespace' => 'Subscriber',
            'middleware' => 'scope:' . UserRoles::getInstance(UserRoles::Subscriber)->key
        ], function () {
            Route::get('', function (Request $request) { return $request->user(); })->name('testRoute');
        });
    });
});
