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


Route::get('/onHand/{id}', 'ShowOnHandTask@showonHand');
Route::get('/subTask/{task_id}', 'ShowOnHandTask@subTaskIndex');
Route::post('/timeInsert', 'ShowOnHandTask@storeTask');

//Route::get('/onHand/{id}', 'SubTaskController@show');
//Route::get('/subTask/{task_id}', 'SubTaskController@subTaskIndex');
//Route::post('/timeInsert', 'SubTaskController@storeTask');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
