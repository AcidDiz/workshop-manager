<?php

namespace Database\Seeders;

use App\Models\WorkshopCategory;
use Illuminate\Database\Seeder;

class WorkshopCategorySeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array{slug: string, name: string}> */
        $categories = [
            ['slug' => 'async-integrations', 'name' => 'Async, mail & storage'],
            ['slug' => 'auth-and-apis', 'name' => 'Auth, security & APIs'],
            ['slug' => 'data-persistence', 'name' => 'Data & persistence'],
            ['slug' => 'frontend', 'name' => 'Frontend & UX'],
            ['slug' => 'laravel-backend', 'name' => 'Laravel & backend'],
            ['slug' => 'platform-ops', 'name' => 'Platform & reliability'],
            ['slug' => 'product-domain', 'name' => 'Product & workshop domain'],
            ['slug' => 'team-practices', 'name' => 'Team practices'],
            ['slug' => 'testing-ci', 'name' => 'Testing & CI'],
        ];

        foreach ($categories as $row) {
            WorkshopCategory::query()->firstOrCreate(
                ['slug' => $row['slug']],
                ['name' => $row['name']],
            );
        }
    }
}
