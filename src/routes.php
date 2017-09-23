<?php

/*
|--------------------------------------------------------------------------
| GridModel Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "admin" middleware group. Now create something great!
|
*/
Route::post('gridmodel-bulk-action', 'Zoor\Gridmodel\GridModelController@doBulk')->name('gridmodel.bulk-action');