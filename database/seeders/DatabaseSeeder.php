<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            MembershipTypesSeeder::class,
            MatchFormatsSeeder::class,
            ClubBadgesSeeder::class,
            SiteContentSeeder::class,
        ]);

        $isProduction = app()->environment('production');

        // Display names mirror the 2026 AGM elected ExCo where applicable; emails
        // remain stable dev inboxes for local/staging login.
        $committee = [
            ['paul@charsley.co.za',           'Paul Charsley (Developer)',   'developer'],
            ['chair@pretoriaprc.co.za',       'Warren Britnell (Chair)',     'chairperson'],
            ['vicechair@pretoriaprc.co.za',   'Paul Charsley (Vice Chair)',  'vice_chair'],
            ['treasurer@pretoriaprc.co.za',   'Natasha Britnell (Treasurer)', 'treasurer'],
            ['secretary@pretoriaprc.co.za',   'Coenie van Tonder (Secretary)', 'secretary'],
            ['marketing@pretoriaprc.co.za',   'Sean Swarts (Marketing)',    'marketing'],
            ['captain@pretoriaprc.co.za',     'Eddie Kinnear (Club Captain)', 'club_captain'],
            ['membership@pretoriaprc.co.za',  'PPRC Membership Secretary',  'membership_secretary'],
            ['matches@pretoriaprc.co.za',     'PPRC Match Director',        'match_director'],
            ['admin@pretoriaprc.co.za',       'PPRC Admin',                 'admin'],
        ];

        foreach ($committee as [$email, $name, $role]) {
            $existing = User::where('email', $email)->first();

            if ($existing) {
                $existing->update(['name' => $name, 'email_verified_at' => $existing->email_verified_at ?? now()]);
                $existing->syncRoles([$role]);

                continue;
            }

            $user = User::create([
                'email' => $email,
                'name' => $name,
                'password' => Hash::make($isProduction ? Str::random(48) : 'password'),
                'email_verified_at' => now(),
            ]);
            $user->syncRoles([$role]);
        }

        if (! $isProduction) {
            $this->call(PaulCharsleyTestMemberSeeder::class);
        }
    }
}
