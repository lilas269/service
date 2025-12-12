<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController; // تأكد من إضافة هذا السطر

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

// هذا الكود المثال الذي يأتي مع لارافل، يمكنك حذفه أو تركه
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});