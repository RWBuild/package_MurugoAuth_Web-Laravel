<?php

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
Route::get('/api', function(){
    return "Test API routes";
});

Route::post('/murugo-auth', 'AuthenticationController@getMurugoResponse')->name('murugo-auth');

