<?php

namespace Database\Seeders;

use App\Models\MatchFormat;
use Illuminate\Database\Seeder;

class MatchFormatsSeeder extends Seeder
{
    public function run(): void
    {
        $formats = [
            [
                'slug' => 'prs-centerfire',
                'name' => 'PRS (Centerfire)',
                'short_name' => 'PRS',
                'description' => 'Precision Rifle Series — full-bore centerfire match.',
                'sort_order' => 10,
            ],
            [
                'slug' => 'pr22',
                'name' => 'PR22',
                'short_name' => 'PR22',
                'description' => 'Precision Rifle 22 — rimfire match run to PRS-style stages.',
                'sort_order' => 20,
            ],
            [
                'slug' => 'training',
                'name' => 'Training / Practice',
                'short_name' => 'Training',
                'description' => 'Club training day or practice session, not a ranked match.',
                'sort_order' => 90,
            ],
        ];

        foreach ($formats as $attrs) {
            MatchFormat::updateOrCreate(
                ['slug' => $attrs['slug']],
                array_merge(['is_active' => true], $attrs),
            );
        }
    }
}
