<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * عرض قائمة جميع التصنيفات المتاحة.
     */
    public function index(Request $request)
    {
        $categories = Category::orderBy('name')->get();
        
        return response()->json($categories);
    }

    /**
     * عرض تفاصيل تصنيف واحد.
     */
    public function show(Category $category)
    {
        return response()->json($category);
    }
}

