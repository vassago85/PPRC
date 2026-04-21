<?php

namespace Database\Seeders;

use App\Models\MatchFormat;
use Illuminate\Database\Seeder;

class MatchFormatsSeeder extends Seeder
{
    public function run(): void
    {
        // PPRC currently only runs PRS (centerfire) and PR22 matches. Any other
        // historical format in the DB (e.g. "training") is kept for referential
        // integrity on old records but deactivated so it doesn't appear in the
        // match-director format picker.
        $formats = [
            [
                'slug' => 'prs-centerfire',
                'name' => 'PRS',
                'short_name' => 'PRS',
                'description' => 'Precision Rifle Series — full-bore centerfire match.',
                'sort_order' => 10,
            ],
            [
                'slug' => 'pr22',
                'name' => 'PR22 Match',
                'short_name' => 'PR22',
                'description' => 'Precision Rifle 22 — rimfire match run to PRS-style stages.',
                'sort_order' => 20,
            ],
        ];

        foreach ($formats as $attrs) {
            MatchFormat::updateOrCreate(
                ['slug' => $attrs['slug']],
                array_merge(['is_active' => true], $attrs),
            );
        }

        $activeSlugs = array_column($formats, 'slug');
        MatchFormat::query()
            ->whereNotIn('slug', $activeSlugs)
            ->update(['is_active' => false]);
    }
}
