<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CoachUserSeeder extends Seeder
{
    public const EMAIL = 'rakhalimming@gmail.com';

    public const USERNAME = 'rakhalimming';

    private const PASSWORD = 'ovafj$^Dqn5VOhD9';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Rakha Limming',
                'username' => self::USERNAME,
                'password' => Hash::make(self::PASSWORD),
                'role' => 'coach',
                'email_verified_at' => now(),
            ],
        );
    }
}
