<?php

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group([
    // 'middleware' => 'CheckApiToken',
],function () {
    // login => google , apple , facebook
    Route::get('/login', [ApiController::class,'login'] );
    Route::get('/register', [ApiController::class,'register'] );
    Route::get('/forget_password', [ApiController::class,'forget_password'] );
    Route::get('/confirm_code', [ApiController::class,'confirm_code'] );
    Route::get('/reset_password', [ApiController::class,'reset_password'] );
    Route::get('/update_image_profile', [ApiController::class,'update_image_profile'] );
    Route::get('/my_account', [ApiController::class,'my_account'] );
    Route::get('/add_user', [ApiController::class,'add_user'] );
    Route::get('/manage_users', [ApiController::class,'manage_users'] );
    Route::get('/assign_sponser', [ApiController::class,'assign_sponser'] );
    Route::get('/orphan_info', [ApiController::class,'orphan_info'] );
    Route::get('/home', [ApiController::class,'home'] );
});