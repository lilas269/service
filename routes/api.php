<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\ReviewController;

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

// --- مسارات المصادقة (التسجيل والدخول) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// هذا المسار يتطلب أن يكون المستخدم مسجلاً دخوله
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// --- مسارات المستقل والعميل داخل المنصة ---
Route::middleware('auth:sanctum')->group(function () {
    // تصفح المشاريع المفتوحة والتفاصيل
    Route::get('/projects/open', [ProjectController::class, 'openProjects']);
    Route::get('/projects/{project}', [ProjectController::class, 'show']);

    // عروض المستقلين
    Route::middleware('role:freelancer')->group(function () {
        Route::post('/projects/{project}/offers', [OfferController::class, 'store']);
        Route::get('/freelancer/offers', [OfferController::class, 'myOffers']);
    });

    // عمليات العميل على المشروع
    Route::middleware('role:client')->group(function () {
        Route::post('/projects/{project}/offers/{offer}/accept', [ProjectController::class, 'acceptOffer']);
        Route::post('/projects/{project}/complete', [ProjectController::class, 'complete']);
        Route::post('/projects/{project}/cancel', [ProjectController::class, 'cancel']);
        Route::post('/projects/{project}/reviews', [ReviewController::class, 'store']);
    });

    // المحادثة بين العميل والمستقل بعد قبول العرض
    Route::get('/projects/{project}/messages', [MessageController::class, 'index']);
    Route::post('/projects/{project}/messages', [MessageController::class, 'store']);

    // نظام المحفظة
    Route::get('/wallet', [WalletController::class, 'show']);
    Route::post('/wallet/deposit', [WalletController::class, 'deposit']);
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);
});

// هذا الكود المثال الذي يأتي مع لارافل، يمكنك حذفه أو تركه
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});