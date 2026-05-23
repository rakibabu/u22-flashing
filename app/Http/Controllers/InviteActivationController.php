<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivateInviteRequest;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InviteActivationController extends Controller
{
    public function show(string $token): View
    {
        $invite = $this->findUsableInvite($token);

        abort_unless($invite, 404);

        return view('invites.show', [
            'invite' => $invite->load('player'),
            'token' => $token,
        ]);
    }

    public function store(ActivateInviteRequest $request, string $token): RedirectResponse
    {
        $invite = $this->findUsableInvite($token);

        abort_unless($invite, 404);
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $invite): User {
            $player = $invite->player;

            $user = User::create([
                'name' => $player->name,
                'email' => $data['email'] ?? null,
                'username' => ($data['username'] ?? null) ?: ($data['email'] ?? null),
                'password' => $data['password'],
                'role' => 'player',
                'email_verified_at' => ($data['email'] ?? null) ? now() : null,
            ]);

            $player->update(['user_id' => $user->id, 'active' => true]);
            $invite->update(['used_at' => now()]);

            return $user;
        });

        Auth::login($user);

        return redirect()->route('player.home');
    }

    private function findUsableInvite(string $token): ?Invite
    {
        $invite = Invite::query()
            ->with('player')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        return $invite?->usable() ? $invite : null;
    }
}
