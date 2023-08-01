All code is here-> ../app/Http/Controllers

Routes for check:<br>
Route::get('/1', 'App\Http\Controllers\NeoBet@parse');<br>
Route::get('/2', 'App\Http\Controllers\NeoBetLive@parse');<br>
Route::get('/3', 'App\Http\Controllers\NeoBetNotLive@parse');<br>
Route::get('/4', 'App\Http\Controllers\OddsFeedLive@main');<br>
Route::get('/5', 'App\Http\Controllers\OddsFeedPreLive@main');<br>
