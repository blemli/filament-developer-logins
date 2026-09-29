<?php

namespace DutchCodingCompany\FilamentDeveloperLogins\Tests\Feature;

use DutchCodingCompany\FilamentDeveloperLogins\Exceptions\ImplementationException;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use DutchCodingCompany\FilamentDeveloperLogins\Livewire\MenuLogins;
use DutchCodingCompany\FilamentDeveloperLogins\Tests\Fixtures\TestUser;
use DutchCodingCompany\FilamentDeveloperLogins\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Livewire\Livewire;

final class MenuLoginsTest extends TestCase
{
    public function test_component_is_rendered(): void
    {
        $user = TestUser::factory()->create();

        $this->actingAs($user)
            ->get(Dashboard::getUrl())
            ->assertSeeLivewire(MenuLogins::class);
    }

    public function test_component_is_not_rendered_when_switchable_is_false(): void
    {
        FilamentDeveloperLoginsPlugin::current()
            ->switchable(false);

        $user = TestUser::factory()->create();

        $this->actingAs($user)
            ->get(Dashboard::getUrl())
            ->assertDontSeeLivewire(MenuLogins::class);
    }

    public function test_component_is_not_rendered_when_plugin_is_not_enabled(): void
    {
        FilamentDeveloperLoginsPlugin::current()
            ->enabled(false);

        $user = TestUser::factory()->create();

        $this->actingAs($user)
            ->get(Dashboard::getUrl())
            ->assertDontSeeLivewire(MenuLogins::class);
    }

    public function test_livewire_displays_to_correct_amount_of_users(): void
    {
        Livewire::actingAs(TestUser::factory()->create())
            ->test(MenuLogins::class)
            ->assertViewHas('users', function (array $users) {
                return count($users) === 1;
            });
    }

    public function test_forbidden_is_returned_when_enabled_or_switchable_is_false(): void
    {
        $authenticatedUser = TestUser::factory()->create();

        TestUser::factory()->create([
            'email' => 'developer@dutchcodingcompany.com',
            'is_admin' => false,
        ]);

        FilamentDeveloperLoginsPlugin::current()
            ->enabled(false);

        Livewire::actingAs($authenticatedUser)
            ->test(MenuLogins::class)
            ->call('loginAs', 'developer@dutchcodingcompany.com')
            ->assertForbidden();

        FilamentDeveloperLoginsPlugin::current()
            ->enabled()
            ->switchable(false);

        Livewire::actingAs($authenticatedUser)
            ->test(MenuLogins::class)
            ->call('loginAs', 'developer@dutchcodingcompany.com')
            ->assertForbidden();
    }

    public function test_component_is_only_rendered_in_panel_with_plugin(): void
    {
        $user = TestUser::factory()->create();

        $this->actingAs($user)
            ->get(Dashboard::getUrl(panel: $this->panelName))
            ->assertSuccessful()
            ->assertSeeLivewire(MenuLogins::class);

        $this->expectException(ImplementationException::class);

        $this->actingAs($user)
            ->get(Dashboard::getUrl(panel: $this->panelName.'-other'))
            ->assertSuccessful()
            ->assertDontSeeLivewire(MenuLogins::class);
    }

    public function test_switcher_renders_next_to_the_global_search_when_the_panel_has_a_topbar(): void
    {
        $user = TestUser::factory()->create();

        $this->actingAs($user)
            ->get(Dashboard::getUrl())
            ->assertSeeLivewire(MenuLogins::class);

        $this->assertSame(PanelsRenderHook::GLOBAL_SEARCH_AFTER, FilamentDeveloperLoginsPlugin::current()->getSwitcherRenderHook());
        $this->assertFalse(FilamentDeveloperLoginsPlugin::current()->rendersSwitcherInUserMenu());
        $this->assertTrue(FilamentView::hasRenderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER));

        Livewire::actingAs($user)
            ->test(MenuLogins::class)
            ->assertViewHas('inUserMenu', false)
            ->assertSeeHtml('fi-dropdown-trigger');
    }

    public function test_switcher_moves_into_the_user_menu_when_the_panel_has_no_topbar(): void
    {
        Filament::getPanel($this->panelName)->topbar(false);

        $user = TestUser::factory()->create();

        $this->actingAs($user)
            ->get(Dashboard::getUrl())
            ->assertSeeLivewire(MenuLogins::class)
            ->assertSee(__('filament-developer-logins::auth.switch-to'));

        $this->assertSame(PanelsRenderHook::USER_MENU_PROFILE_AFTER, FilamentDeveloperLoginsPlugin::current()->getSwitcherRenderHook());
        $this->assertTrue(FilamentDeveloperLoginsPlugin::current()->rendersSwitcherInUserMenu());
        $this->assertTrue(FilamentView::hasRenderHook(PanelsRenderHook::USER_MENU_PROFILE_AFTER));
        $this->assertFalse(FilamentView::hasRenderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER));

        Livewire::actingAs($user)
            ->test(MenuLogins::class)
            ->assertViewHas('inUserMenu', true)
            ->assertSee('Administrator')
            ->assertDontSee('Administrator (developer@dutchcodingcompany.com)')
            ->assertSeeHtml('developer@dutchcodingcompany.com')
            ->assertDontSeeHtml('fi-dropdown-trigger');
    }

    public function test_switcher_render_hook_can_be_chosen(): void
    {
        FilamentDeveloperLoginsPlugin::current()
            ->switcherRenderHook(fn (): string => PanelsRenderHook::SIDEBAR_FOOTER);

        $user = TestUser::factory()->create();

        $this->actingAs($user)
            ->get(Dashboard::getUrl())
            ->assertSeeLivewire(MenuLogins::class);

        $this->assertSame(PanelsRenderHook::SIDEBAR_FOOTER, FilamentDeveloperLoginsPlugin::current()->getSwitcherRenderHook());
        $this->assertFalse(FilamentDeveloperLoginsPlugin::current()->rendersSwitcherInUserMenu());
        $this->assertTrue(FilamentView::hasRenderHook(PanelsRenderHook::SIDEBAR_FOOTER));
        $this->assertFalse(FilamentView::hasRenderHook(PanelsRenderHook::GLOBAL_SEARCH_AFTER));
    }
}
