<?php

use App\Http\Controllers\ArticleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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
Route::get('ejercicio',[ArticleController::class,'ejercicio']);
Route::get('ejercicio/calculo/{id}',[ArticleController::class,'ejercicioDos']);
Route::post('ejercicio/venta',[ArticleController::class,'venta']);

Route::group([

    'middleware' => 'auth:api',
    'prefix' => 'auth'

], function () {

    Route::post('logout', [AuthController::class,'logout']);
    Route::get('me', [AuthController::class,'me']);

});
Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('login', [AuthController::class,'login']);
    Route::post('register', [AuthController::class,'register']);
});



Route::group([
    'prefix' => 'articles',

], function () {
    Route::group([
        'middleware' => ['auth:api','role:admin']
    ],function () {
        Route::get('/list', [ArticleController::class,'list']);
        Route::post('/create', [ArticleController::class,'create']);
        Route::put('/edit/{id}', [ArticleController::class,'edit']);
        Route::delete('/delete/{id}', [ArticleController::class,'delete']);
    });
    Route::group([
        'middleware' => ['auth:api','permission:list articles']
    ],function () {
        Route::get('/show/{id}', [ArticleController::class,'show'])
        ->middleware('permission:list articles');
    });


});

