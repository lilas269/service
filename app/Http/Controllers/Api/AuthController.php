<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;
use App\Models\FreelancerProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * وظيفة لإنشاء حساب مستخدم جديد.
     */
    public function register(Request $request)
    {
        // 1. التحقق من صحة البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'email' => 'required|string|email|max:191|unique:users',
            'password' => 'required|string|min:8|confirmed', // confirmed يتأكد من وجود حقل password_confirmation مطابق
            'role' => 'required|in:client,freelancer',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); // إرجاع أخطاء التحقق
        }

        // 2. استخدام Transaction لضمان تنفيذ كل العمليات معاً أو لا شيء
        try {
            $user = DB::transaction(function () use ($request) {
                // إنشاء المستخدم
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password), // تشفير كلمة المرور
                    'role' => $request->role,
                ]);

                // إنشاء محفظة تلقائياً للمستخدم الجديد
                Wallet::create(['user_id' => $user->id]);
                    
                // إذا كان المستخدم مستقلاً، أنشئ له ملف تعريف فارغ
                if ($request->role === 'freelancer') {
                    FreelancerProfile::create([
                        'user_id' => $user->id,
                        'display_name' => $user->name, // اسم مبدئي
                        'title' => 'New Freelancer'
                    ]);
                }

                return $user;
            });

            // 3. إنشاء توكن للمستخدم الجديد
            $token = $user->createToken('auth_token')->plainTextToken;

            // 4. إرجاع رد ناجح مع بيانات المستخدم والتوكن
            return response()->json([
                'message' => 'User registered successfully!',
                'user' => $user,
                'access_token' => $token,
            ], 201);

        } catch (\Exception $e) {
            // في حال حدوث أي خطأ أثناء العملية
            return response()->json(['message' => 'Registration failed!', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * وظيفة لتسجيل دخول المستخدم.
     */
    public function login(Request $request)
    {
        // 1. التحقق من صحة البيانات
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2. محاولة تسجيل الدخول
        if (!auth()->attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Invalid login details'], 401); // خطأ في البريد أو كلمة المرور
        }

        // 3. جلب بيانات المستخدم وإنشاء توكن جديد
        $user = User::where('email', $request['email'])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        // 4. إرجاع رد ناجح مع التوكن
        return response()->json([
            'message' => 'Welcome back, ' . $user->name,
            'access_token' => $token,
            'user' => $user,
        ]);
    }
        
    /**
     * وظيفة لتسجيل الخروج.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Successfully logged out']);
    }
}