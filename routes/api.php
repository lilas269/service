<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\FreelancerProfileController;
use App\Http\Controllers\Api\FreelancerPortfolioController;
use App\Http\Controllers\Api\CategoryController;

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

// --- التصنيفات (متاحة للجميع) ---
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);

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
        
        // ملف تعريف المستقل
        Route::get('/freelancer/profile', [FreelancerProfileController::class, 'show']);
        Route::put('/freelancer/profile', [FreelancerProfileController::class, 'update']);
        
        // معرض أعمال المستقل (Portfolio)
        Route::get('/freelancer/portfolio', [FreelancerPortfolioController::class, 'index']);
        Route::post('/freelancer/portfolio', [FreelancerPortfolioController::class, 'store']);
        Route::put('/freelancer/portfolio/{freelancerPortfolio}', [FreelancerPortfolioController::class, 'update']);
        Route::delete('/freelancer/portfolio/{freelancerPortfolio}', [FreelancerPortfolioController::class, 'destroy']);
        
        // المشاريع المكتملة للمستقل
        Route::get('/freelancer/completed-projects', [ProjectController::class, 'completedProjects']);
    });

    // عمليات العميل على المشروع
    Route::middleware('role:client')->group(function () {
        // إدارة المشاريع
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::put('/projects/{project}', [ProjectController::class, 'update']);
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
        Route::get('/client/projects', [ProjectController::class, 'myProjects']);
        
        // إدارة العروض
        Route::get('/projects/{project}/offers', [OfferController::class, 'projectOffers']);
        Route::post('/projects/{project}/offers/{offer}/accept', [OfferController::class, 'acceptOffer']);
        
        // إدارة إنهاء المشروع والدفع
        Route::post('/projects/{project}/complete', [ProjectController::class, 'completeProject']);
        
        // إدارة التقييمات
        Route::post('/projects/{project}/reviews', [ReviewController::class, 'createReview']);
        
        // إدارة المشاريع
        Route::post('/projects/{project}/cancel', [ProjectController::class, 'cancel']);
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