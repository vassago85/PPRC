<?php

namespace Database\Seeders;

use App\Models\ClubBadge;
use Illuminate\Database\Seeder;

class ClubBadgesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Range safety officer', 'slug' => 'range-safety-officer', 'description' => 'Trained for cold ranges and match-day safety.', 'accent_color' => 'emerald', 'sort_order' => 10],
            ['name' => 'Match volunteer', 'slug' => 'match-volunteer', 'description' => 'Helped set up, score, or tear down club matches.', 'accent_color' => 'brand', 'sort_order' => 20],
            ['name' => 'Club builder', 'slug' => 'club-builder', 'description' => 'Major contribution to facilities, IT, or equipment.', 'accent_color' => 'amber', 'sort_order' => 30],
        ];

        foreach ($rows as $row) {
            ClubBadge::updateOrCreate(
                ['slug' => $row['slug']],
                $row,
            );
        }
    }
}
