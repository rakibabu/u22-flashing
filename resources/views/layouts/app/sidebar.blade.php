<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-primary-900 dark:bg-primary-900 dark:text-white">
        <flux:sidebar sticky collapsible="mobile" class="u22-sidebar border-e border-flash-orange/30 bg-primary-800 text-white dark:border-flash-orange/30 dark:bg-primary-800">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('U22 Monitoring')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    @if (auth()->user()->isCoach())
                        <flux:sidebar.item icon="users" :href="route('coach.players.index')" :current="request()->routeIs('coach.players.*')" wire:navigate>Spelers</flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document-check" :href="route('coach.checkins.index')" :current="request()->routeIs('coach.checkins.*')" wire:navigate>Check-ins</flux:sidebar.item>
                        <flux:sidebar.item icon="chart-bar" :href="route('coach.tests.index')" :current="request()->routeIs('coach.tests.*')" wire:navigate>Tests</flux:sidebar.item>
                        <flux:sidebar.item icon="chat-bubble-left-right" :href="route('coach.advice.index')" :current="request()->routeIs('coach.advice.*')" wire:navigate>Advies</flux:sidebar.item>
                        <flux:sidebar.item icon="document-text" :href="route('coach.analysis-export')" :current="request()->routeIs('coach.analysis-export')" wire:navigate>Analyse export</flux:sidebar.item>
                    @else
                        <flux:sidebar.item icon="book-open" :href="route('player.program')" :current="request()->routeIs('player.program')" wire:navigate>Programma</flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document-check" :href="route('player.checkin')" :current="request()->routeIs('player.checkin')" wire:navigate>Weekcheck</flux:sidebar.item>
                        <flux:sidebar.item icon="chart-bar" :href="route('player.progress')" :current="request()->routeIs('player.progress')" wire:navigate>Voortgang</flux:sidebar.item>
                        <flux:sidebar.item icon="chat-bubble-left-right" :href="route('player.advice')" :current="request()->routeIs('player.advice')" wire:navigate>Advies</flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="u22-mobile-header border-b border-flash-orange/20 bg-primary-800 text-white lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @include('partials.flux-scripts')
    </body>
</html>
