<?php

use Illuminate\Support\Facades\Route;

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

//Route::get('/1', 'App\Http\Controllers\TestC@test');

Route::get('/1', 'App\Http\Controllers\NeoBet@parse');
Route::get('/2', 'App\Http\Controllers\NeoBetLive@parse');
Route::get('/3', 'App\Http\Controllers\NeoBetNotLive@parse');
Route::get('/4', 'App\Http\Controllers\OddsFeedLive@main');
Route::get('/5', 'App\Http\Controllers\OddsFeedPreLive@main');
