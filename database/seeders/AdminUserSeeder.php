<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'tarikulislamnahid15@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
            ],
        );
    }
}
