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
            SiteContentSeeder::class,
        ]);

        $committee = [
            ['dev@pretoriaprc.co.za',         'PPRC Developer',            'developer'],
            ['chair@pretoriaprc.co.za',       'PPRC Chairperson',          'chairperson'],
            ['vicechair@pretoriaprc.co.za',   'PPRC Vice Chair',           'vice_chair'],
            ['treasurer@pretoriaprc.co.za',   'PPRC Treasurer',            'treasurer'],
            ['secretary@pretoriaprc.co.za',   'PPRC Secretary',            'secretary'],
            ['marketing@pretoriaprc.co.za',   'PPRC Marketing',            'marketing'],
            ['captain@pretoriaprc.co.za',     'PPRC Club Captain',         'club_captain'],
            ['membership@pretoriaprc.co.za',  'PPRC Membership Secretary', 'membership_secretary'],
            ['matches@pretoriaprc.co.za',     'PPRC Match Director',       'match_director'],
            ['admin@pretoriaprc.co.za',       'PPRC Admin',                'admin'],
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
    }
}
