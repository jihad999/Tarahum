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
    'middleware' => 'CheckApiToken',
],function () {

    Route::get('/home', [ApiController::class,'home'] );

    // ********* Auth *********
    // login => google , apple , facebook
    Route::post('/login', [AuthController::class,'login'] );
    Route::post('/register', [AuthController::class,'register'] );
    Route::post('/forget_password', [AuthController::class,'forget_password'] );
    Route::post('/confirm_code', [AuthController::class,'confirm_code'] );
    Route::post('/reset_password', [AuthController::class,'reset_password'] );

    // ********* Sponser Orphan *********
    Route::get('/get_sponser', [ApiController::class,'get_sponser'] );
    Route::get('/get_sponsers', [ApiController::class,'get_sponsers'] );
    Route::get('/get_orphans', [ApiController::class,'get_orphans'] );
    Route::post('/add_orphan', [ApiController::class,'add_orphan'] );
    Route::post('/update_orphan', [ApiController::class,'update_orphan'] );
    Route::get('/get_orphan', [ApiController::class,'get_orphan'] );
    Route::post('/sponser_assign_orphan', [ApiController::class,'sponser_assign_orphan'] );
    
    // ********* Settings *********
    Route::post('/update_image_profile', [ApiController::class,'update_image_profile'] );
    Route::post('/update_my_account', [ApiController::class,'update_my_account'] );
    Route::get('/manage_users', [ApiController::class,'manage_users'] );
    Route::post('/add_user', [ApiController::class,'add_user'] );
    Route::get('/get_roles', [ApiController::class,'get_roles'] );
    // Route::get('/get_notifications', [ApiController::class,'get_notifications'] );
    Route::post('/update_user_notifications', [ApiController::class,'update_user_notifications'] );

    // ********* Sponser Payment *********
    Route::post('/add_plan_payment', [ApiController::class,'add_plan_payment'] );
    Route::post('/cancel_plan', [ApiController::class,'cancel_plan'] );
    Route::post('/update_payment_status', [ApiController::class,'update_payment_status'] );
    Route::get('/get_plan_with_details', [ApiController::class,'get_plan_with_details'] );

    // ********* Plans *********
    Route::get('/get_plans', [ApiController::class,'get_plans'] );
    Route::post('/add_plan', [ApiController::class,'add_plan'] );  
    Route::post('/update_plan', [ApiController::class,'update_plan'] );  
    Route::post('/delete_plan', [ApiController::class,'delete_plan'] );
    
    // ********* Posts *********
    Route::get('/get_posts', [ApiController::class,'get_posts'] );
    Route::post('/add_post', [ApiController::class,'add_post'] );
    Route::post('/update_post', [ApiController::class,'update_post'] );
    Route::get('/get_post', [ApiController::class,'get_post'] );
    Route::post('/delete_post', [ApiController::class,'delete_post'] );
    
    // ********* Guardians *********
    Route::get('/get_guardians', [ApiController::class,'get_guardians'] );
    Route::get('/get_guardian', [ApiController::class,'get_guardian'] );
    Route::get('/request_payment', [ApiController::class,'request_payment'] );
    // INSERT INTO `notification_settings` (`id`, `title`, `role_id`, `created_at`, `updated_at`, `deleted_at`) VALUES (NULL, 'Sponser Request Payment', NULL, '2024-11-06 20:27:43', '2024-11-06 20:27:43', '2024-11-06 20:27:43');
    
});