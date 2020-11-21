<?php

use \App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/register', function (Request $request) {
    return $request->user();
});


//API V1
Route::group(['prefix' => 'v1'], function () {
    Route::post('/register', [UserController::class, 'register'])->name('register');
    Route::post('/login', [UserController::class, 'login'])->name('login');
    Route::post('/phone-verify-code', [UserController::class, 'phoneVerify']);
    Route::post('/change-password',  [UserController::class, 'changePassword']);
    Route::post('/add-bank-account', [UserController::class, 'addBankAccount']);

    Route::get('/remove-bank-account/{id}', [UserController::class, 'removeBankAccount']);
});

