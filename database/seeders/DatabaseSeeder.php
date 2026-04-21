<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );
            $user->syncRoles([$role]);
        }

        $this->call(PaulCharsleyTestMemberSeeder::class);
    }
}
