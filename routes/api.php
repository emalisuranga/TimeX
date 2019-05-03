<?php

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::GET('/onHand/{id}', 'ShowOnHandTask@showonHand');
Route::POST('/subTask', 'ShowOnHandTask@subTaskIndex');
Route::POST('/timeInsert', 'ShowOnHandTask@storeTask');
Route::POST('/completeTask', 'ShowOnHandTask@completeTask');

