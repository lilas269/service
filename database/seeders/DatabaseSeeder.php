<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        // إنشاء مستخدمين افتراضيين للتجربة (عميل ومستقل)
        if (! User::where('email', 'client@example.com')->exists()) {
            $client = User::factory()->create([
                'name' => 'Demo Client',
                'email' => 'client@example.com',
                'role' => 'client',
            ]);
            $client->wallet()->create(['balance' => 200]);
        }

        if (! User::where('email', 'freelancer@example.com')->exists()) {
            $freelancer = User::factory()->create([
                'name' => 'Demo Freelancer',
                'email' => 'freelancer@example.com',
                'role' => 'freelancer',
            ]);
            $freelancer->wallet()->create();
            $freelancer->freelancerProfile()->create([
                'display_name' => 'Demo Freelancer',
                'title' => 'Full Stack Developer',
            ]);
        }
    }
}
