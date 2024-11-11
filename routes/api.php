<?php

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\AuthController;
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
    Route::get('/login', [AuthController::class,'login'] );
    Route::get('/register', [AuthController::class,'register'] );
    Route::get('/forget_password', [AuthController::class,'forget_password'] );
    Route::get('/confirm_code', [AuthController::class,'confirm_code'] );
    Route::get('/reset_password', [AuthController::class,'reset_password'] );

    // ********* Tarahum *********
    Route::get('/home', [ApiController::class,'home'] );

    // ********* Sponser Orphan *********
    Route::get('/get_sponser', [ApiController::class,'get_sponser'] );
    Route::get('/get_sponsers', [ApiController::class,'get_sponsers'] );
    Route::get('/get_orphans', [ApiController::class,'get_orphans'] );
    Route::get('/add_orphan', [ApiController::class,'add_orphan'] );
    Route::get('/get_orphan', [ApiController::class,'get_orphan'] );
    Route::get('/sponser_assign_orphan', [ApiController::class,'sponser_assign_orphan'] );
    
    // ********* Settings *********
    Route::get('/update_image_profile', [ApiController::class,'update_image_profile'] );
    Route::get('/update_my_account', [ApiController::class,'update_my_account'] );
    Route::get('/manage_users', [ApiController::class,'manage_users'] );
    Route::get('/add_user', [ApiController::class,'add_user'] );
    Route::get('/get_roles', [ApiController::class,'get_roles'] );
    Route::get('/get_notifications', [ApiController::class,'get_notifications'] );
    Route::get('/update_user_notifications', [ApiController::class,'update_user_notifications'] );

    // ********* Sponser Payment *********
    Route::get('/add_plan_payment', [ApiController::class,'add_plan_payment'] );
    Route::get('/cancel_plan', [ApiController::class,'cancel_plan'] );
    Route::get('/update_payment_status', [ApiController::class,'update_payment_status'] );
    Route::get('/get_plan_with_details', [ApiController::class,'get_plan_with_details'] );

    // ********* Guardian *********
    // Route::get('/get_guardian_sponser', [ApiController::class,'get_guardian_sponser'] );  

    // ********* Plans *********
    Route::get('/get_plans', [ApiController::class,'get_plans'] );
    Route::get('/add_plan', [ApiController::class,'add_plan'] );  
    Route::get('/update_plan', [ApiController::class,'update_plan'] );  
    Route::get('/delete_plan', [ApiController::class,'delete_plan'] );
    
    // ********* Posts *********
    Route::get('/get_posts', [ApiController::class,'get_posts'] );
    Route::get('/add_post', [ApiController::class,'add_post'] );
    Route::get('/update_post', [ApiController::class,'update_post'] );
    Route::get('/get_post', [ApiController::class,'get_post'] );
    Route::get('/delete_post', [ApiController::class,'delete_post'] );
    
    
    // Route::get('/suggestion_sponsers', [ApiController::class,'suggestion_sponsers'] );
    // Route::get('/get_sponser_orphan', [ApiController::class,'get_sponser_orphan'] );
    // Route::get('/user_info', [ApiController::class,'user_info'] );
});