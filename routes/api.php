<?php

use \App\Http\Controllers\UserController;
use \App\Http\Controllers\SmsNotificationController;
use \App\Http\Controllers\CatalogController;
use \App\Http\Controllers\OrderController;
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
    Route::post('/check-user-details', [UserController::class, 'userExists'])->name('userExists');
    Route::post('/login', [UserController::class, 'login'])->name('login');
    Route::post('/loginLegalUser', [UserController::class, 'loginLegalUser'])->name('loginLegalUser');
    Route::post('/phone-verify-code', [UserController::class, 'phoneVerify']);
    Route::post('/change-password',  [UserController::class, 'changePassword']);
    Route::post('/reset-password',  [UserController::class, 'resetPassword']);
    Route::post('/confirm-reset-code',  [UserController::class, 'confirmResetCode']);
    Route::post('/set-new-password',  [UserController::class, 'setNewPassword']);
    Route::post('/me',  [UserController::class, 'me']);
    
    Route::post('/add-bank-account', [UserController::class, 'addBankAccount']);
    Route::post('/update-address', [UserController::class, 'updateAddress']);

    Route::post('/add-legal-type', [UserController::class, 'addLegalType']);
    Route::post('/legal-types', [UserController::class, 'legalTypes']);

    Route::post('/send-verification-code', [SmsNotificationController::class, 'sendVerificationCode']);

    Route::post('/send-sms', [SmsNotificationController::class, 'sendSMS']);

    Route::get('/remove-bank-account/{id}', [UserController::class, 'removeBankAccount']);

    Route::get('/refresh-token', [UserController::class, 'refreshToken']);

    // CATALOG
    Route::post('/brands', [CatalogController::class, 'getBrands']);
    Route::post('/models', [CatalogController::class, 'getModels']);
    Route::post('/model-params', [CatalogController::class, 'getModelParams']);
    Route::post('/car-list-by-params', [CatalogController::class, 'getCarList']);
    Route::post('/model-categories', [CatalogController::class, 'getModelCategories']);
    Route::post('/model-parts', [CatalogController::class, 'getModelParts']);
    Route::post('/user-cars', [CatalogController::class, 'getUserCars']);
    Route::post('/add-car', [CatalogController::class, 'addCar']);
    Route::post('/delete-car', [CatalogController::class, 'deleteCar']);
    Route::post('/find-car-vin', [CatalogController::class, 'findByVIN']);
    Route::post('/search-by-part-number', [CatalogController::class, 'searchByPartNumber']);
    Route::post('/search-by-part-name', [CatalogController::class, 'searchByPartName']);
    Route::post('/delete-seller-car', [CatalogController::class, 'deleteSellerCar']);

    // ORDERS
    Route::post('/make-order', [OrderController::class, 'makeOrder']);
    Route::post('/get-orders', [OrderController::class, 'getOrders']);
    Route::post('/get-order', [OrderController::class, 'getOrder']);
    Route::post('/get-offers', [OrderController::class, 'getOffers']);

    //SELERS
    Route::post('/add-seller-car', [CatalogController::class, 'addSellerCar']);
    //SELERS ORDER
    Route::post('/get-seller-orders', [OrderController::class, 'getSellerOrders']);
    Route::post('/make-offer', [OrderController::class, 'makeOffer']);
    
    //Auto FETCH
    Route::post('/auto-fetch', [CatalogController::class, 'autoFetch']);


});

