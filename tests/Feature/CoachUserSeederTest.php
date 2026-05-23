<?php

use App\Models\User;
use Database\Seeders\CoachUserSeeder;
use Illuminate\Support\Facades\Hash;

test('coach user seeder creates the requested coach account', function () {
    $this->seed(CoachUserSeeder::class);

    $coach = User::query()->where('email', CoachUserSeeder::EMAIL)->firstOrFail();

    expect($coach->name)->toBe('Rakha Limming')
        ->and($coach->username)->toBe(CoachUserSeeder::USERNAME)
        ->and($coach->role)->toBe('coach')
        ->and($coach->email_verified_at)->not->toBeNull()
        ->and(Hash::check('ovafj$^Dqn5VOhD9', $coach->password))->toBeTrue();
});

test('coach user seeder is idempotent', function () {
    $this->seed(CoachUserSeeder::class);
    $firstCoachId = User::query()->where('email', CoachUserSeeder::EMAIL)->value('id');

    $this->seed(CoachUserSeeder::class);

    expect(User::query()->where('email', CoachUserSeeder::EMAIL)->count())->toBe(1)
        ->and(User::query()->where('email', CoachUserSeeder::EMAIL)->value('id'))->toBe($firstCoachId);
});
