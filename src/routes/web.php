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

Route::get('/web', function(){
    return "Test web routes";
});

Route::get('/home', function(){
    return view('MurugoAuth::home');
});

Route::get('/login', function(){
    return view('MurugoAuth::login');
});

Route::get('/authenticate-user', 'AuthenticationController@authenticateUser')->name('authenticate-user');
Route::get('/logout', 'AuthenticationController@logoutUser')->name('logout');


