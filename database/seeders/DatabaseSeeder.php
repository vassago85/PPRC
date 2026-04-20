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
        ]);

        $developer = User::updateOrCreate(
            ['email' => 'dev@pretoriaprc.co.za'],
            [
                'name' => 'PPRC Developer',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
        $developer->syncRoles(['developer']);

        $admin = User::updateOrCreate(
            ['email' => 'admin@pretoriaprc.co.za'],
            [
                'name' => 'PPRC Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles(['admin']);
    }
}
