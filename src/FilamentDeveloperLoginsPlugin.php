<?php

namespace DutchCodingCompany\FilamentDeveloperLogins;

use App\Models\User;
use Closure;
use DutchCodingCompany\FilamentDeveloperLogins\Exceptions\ImplementationException;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Schemas\Concerns\HasColumns;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;

class FilamentDeveloperLoginsPlugin implements Plugin
{
    use EvaluatesClosures, HasColumns;

    /**
     * @var class-string<\Illuminate\Database\Eloquent\Model&\Illuminate\Contracts\Auth\Authenticatable>
     */
    public string $modelClass = '';

    public Closure | bool $enabled = false;

    public Closure | bool $switchable = true;

    public Closure | string | null $switcherRenderHook = null;

    /**
     * @var array<string, string>
     */
    public Closure | array $users = [];

    public Closure | string | null $redirectTo = null;

    public ?string $panelId = null;

    public string $column = 'email';

    public function __construct()
    {
        $this->modelClass = config('auth.providers.users.model') ?? User::class;
    }

    public function getId(): string
    {
        return 'filament-developer-logins';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        $this->panelId = $panel->getId();
    }

    public function boot(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            $this->getSwitcherRenderHook(),
            function () use ($panel): ?string {
                if (Filament::getCurrentPanel()?->getId() !== $panel->getId()) {
                    return null;
                }

                if (! $this->getEnabled() || ! $this->getSwitchable()) {
                    return null;
                }

                return Blade::render('@livewire(\'menu-logins\')');
            },
        );
    }

    public static function current(): static
    {
        if (Filament::getCurrentPanel()?->hasPlugin('filament-developer-logins')) {
            /** @var static $plugin */
            $plugin = Filament::getCurrentPanel()->getPlugin('filament-developer-logins');

            return $plugin;
        }

        throw new ImplementationException('No current panel found with filament-developer-logins plugin.');
    }

    public static function getById(string $panelId): static
    {
        if (Filament::getPanel($panelId)->hasPlugin('filament-developer-logins')) {
            /** @var static $plugin */
            $plugin = Filament::getPanel($panelId)->getPlugin('filament-developer-logins');

            return $plugin;
        }

        throw new ImplementationException('No panel found with filament-developer-logins plugin.');
    }

    public function enabled(Closure | bool $value = false): static
    {
        $this->enabled = $value;

        return $this;
    }

    public function getEnabled(): bool
    {
        return $this->evaluate($this->enabled);
    }

    public function switchable(Closure | bool $value): static
    {
        $this->switchable = $value;

        return $this;
    }

    public function getSwitchable(): bool
    {
        return $this->evaluate($this->switchable);
    }

    /**
     * Where the "Switch to" menu renders. Without a hook it sits next to the
     * global search in the topbar, or — when the panel has no topbar — inside
     * the user menu dropdown, as a list of users.
     */
    public function switcherRenderHook(Closure | string | null $hook): static
    {
        $this->switcherRenderHook = $hook;

        return $this;
    }

    public function getSwitcherRenderHook(): string
    {
        $hook = $this->evaluate($this->switcherRenderHook);

        if (filled($hook)) {
            return $hook;
        }

        $panel = $this->panelId ? Filament::getPanel($this->panelId) : Filament::getCurrentPanel();

        return $panel?->hasTopbar() === false
            ? PanelsRenderHook::USER_MENU_PROFILE_AFTER
            : PanelsRenderHook::GLOBAL_SEARCH_AFTER;
    }

    /**
     * Inside the user menu the switcher is a list of users, not a button with its own dropdown.
     */
    public function rendersSwitcherInUserMenu(): bool
    {
        return in_array($this->getSwitcherRenderHook(), [
            PanelsRenderHook::USER_MENU_PROFILE_BEFORE,
            PanelsRenderHook::USER_MENU_PROFILE_AFTER,
        ], true);
    }

    /**
     * @param Closure | array<string, string> $users
     *
     * @return $this
     */
    public function users(Closure | array $users): static
    {
        $this->users = $users;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getUsers(): array
    {
        return $this->evaluate($this->users);
    }

    public function redirectTo(Closure | string $redirectTo): static
    {
        $this->redirectTo = $redirectTo;

        return $this;
    }

    public function getRedirectTo(): string | null
    {
        return $this->evaluate($this->redirectTo);
    }

    public function column(string $column): static
    {
        $this->column = $column;

        return $this;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model&\Illuminate\Contracts\Auth\Authenticatable> $modelClass
     */
    public function modelClass(string $modelClass): static
    {
        $this->modelClass = $modelClass;

        return $this;
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model&\Illuminate\Contracts\Auth\Authenticatable>
     */
    public function getModelClass(): string
    {
        return $this->modelClass;
    }
}
