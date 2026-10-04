<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AccessMenu;
use App\Models\Menu;
use App\Models\User;
use Database\Seeders\ClassSeeder;
use Database\Seeders\CompanyIndustrySeeder;
use Database\Seeders\MajorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessMenuTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;

    protected User $admin;

    protected User $student;

    protected Menu $dashboardMenu;

    protected Menu $usersMenu;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CompanyIndustrySeeder::class,
            ClassSeeder::class,
            MajorSeeder::class,
        ]);

        $this->superadmin = User::factory()->create([
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->dashboardMenu = Menu::create([
            'menu_name' => 'Dashboard',
            'menu_icon' => 'LayoutDashboard',
            'menu_link' => '/admin/dashboard',
            'section' => 1,
            'is_active' => true,
        ]);

        $this->usersMenu = Menu::create([
            'menu_name' => 'Manajemen Pengguna',
            'menu_icon' => 'UserCog',
            'menu_link' => '/admin/users',
            'section' => 2,
            'is_active' => true,
        ]);

        AccessMenu::create(['role' => 'superadmin', 'menu_id' => $this->dashboardMenu->id]);
        AccessMenu::create(['role' => 'superadmin', 'menu_id' => $this->usersMenu->id]);
        AccessMenu::create(['role' => 'admin', 'menu_id' => $this->dashboardMenu->id]);
    }

    public function test_superadmin_can_view_access_menu_matrix(): void
    {
        $response = $this->actingAs($this->superadmin, 'sanctum')
            ->getJson('/api/admin/access-menus');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $matrix = $response->json('data.matrix');
        $this->assertNotEmpty($matrix);
    }

    public function test_superadmin_can_update_role_menu_permissions(): void
    {
        $response = $this->actingAs($this->superadmin, 'sanctum')
            ->postJson('/api/admin/access-menus', [
                'role' => 'admin',
                'menu_ids' => [$this->dashboardMenu->id, $this->usersMenu->id],
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $hasAccess = AccessMenu::where('role', 'admin')
            ->where('menu_id', $this->usersMenu->id)
            ->exists();

        $this->assertTrue($hasAccess);
    }

    public function test_regular_admin_cannot_modify_access_matrix(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/access-menus', [
                'role' => 'admin',
                'menu_ids' => [$this->dashboardMenu->id],
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_fetch_accessible_menus(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/me/menus');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $menus = $response->json('data');
        $this->assertCount(1, $menus);
        $this->assertSame('Dashboard', $menus[0]['menu_name']);
    }
}
