@php
    $label = auth()->check() ? __('filament-developer-logins::auth.switch-to') : __('filament-developer-logins::auth.login-as');
@endphp

<div>
    @if (filled($users))
        @if ($inUserMenu ?? false)
            <x-filament::dropdown.list>
                <x-filament::dropdown.header icon="heroicon-o-user">
                    {{ $label }}
                </x-filament::dropdown.header>

                @foreach ($users as $userLabel => $credentials)
                    <x-filament::dropdown.list.item
                        wire:click="loginAs('{{ $credentials }}')"
                        color="{{ $credentials === $current ? 'primary' : 'gray' }}"
                        icon="heroicon-o-user"
                    >
                        {{ "$userLabel ($credentials)" }}
                    </x-filament::dropdown.list.item>
                @endforeach
            </x-filament::dropdown.list>
        @else
            <x-filament::dropdown placement="bottom-end" teleport>
                <x-slot name="trigger">
                    <x-filament::button icon="heroicon-o-user" color="gray" outlined="false">
                        {{ $label }}
                    </x-filament::button>
                </x-slot>

                <x-filament::dropdown.list>
                    @foreach ($users as $userLabel => $credentials)
                        <x-filament::dropdown.list.item
                            wire:click="loginAs('{{ $credentials }}')"
                            color="{{ $credentials === $current ? 'primary' : 'gray' }}"
                        >
                            {{ "$userLabel ($credentials)" }}
                        </x-filament::dropdown.list.item>
                    @endforeach
                </x-filament::dropdown.list>
            </x-filament::dropdown>
        @endif
    @endif
</div>
