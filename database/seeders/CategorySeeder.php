<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'البرمجة والتقنية',
            'التصميم الجرافيكي',
            'تحليل البيانات',
            'التسويق الرقمي',
            'الدعم الفني',
            'الفيديو والأنيميشن',
        ];

        foreach ($items as $name) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name, '-')],
                ['name' => $name, 'description' => $name]
            );
        }
    }
}

