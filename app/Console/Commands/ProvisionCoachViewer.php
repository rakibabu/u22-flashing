<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

#[Signature('app:provision-coach-viewer {--email=demo@u22-basketball.nl} {--username=demo-coach} {--name=} {--password=}')]
#[Description('Create or refresh a read-only coach demo account')]
class ProvisionCoachViewer extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->option('email');
        $username = (string) $this->option('username');
        $nameOption = $this->option('name');
        $passwordOption = $this->option('password');
        $name = is_string($nameOption) && $nameOption !== '' ? $nameOption : 'Demo Coach';
        $password = is_string($passwordOption) && $passwordOption !== '' ? $passwordOption : 'Aa1!'.Str::password(20);
        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser && ! $existingUser->isCoachViewer()) {
            $this->components->error('Dit e-mailadres hoort al bij een ander account.');

            return self::FAILURE;
        }

        $validator = Validator::make(
            compact('email', 'username', 'name', 'password'),
            [
                'email' => ['required', 'email'],
                'username' => [
                    'required',
                    'alpha_dash',
                    'max:255',
                    Rule::unique(User::class, 'username')->ignore($existingUser),
                ],
                'name' => ['required', 'string', 'max:255'],
                'password' => [
                    'required',
                    Password::min(16)->letters()->mixedCase()->numbers()->symbols(),
                ],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        $viewer = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'username' => $username,
                'password' => Hash::make($password),
                'role' => 'coach_viewer',
            ],
        );
        $viewer->forceFill([
            'email_verified_at' => $viewer->email_verified_at ?? now(),
            'remember_token' => Str::random(60),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
        $viewer->passkeys()->delete();

        $sessionTable = (string) config('session.table', 'sessions');

        if (Schema::hasTable($sessionTable)) {
            DB::table($sessionTable)
                ->where('user_id', $viewer->getAuthIdentifier())
                ->delete();
        }

        $this->components->info('Demo-coachaccount is klaar voor gebruik.');
        $this->table(
            ['Login', 'Waarde'],
            [
                ['E-mail', $email],
                ['Gebruikersnaam', $username],
                ['Wachtwoord', $password],
            ],
        );

        return self::SUCCESS;
    }
}
