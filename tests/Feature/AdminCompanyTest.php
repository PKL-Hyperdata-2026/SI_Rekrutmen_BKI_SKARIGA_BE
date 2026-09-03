<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\StandardType;
use App\Models\User;
use Database\Seeders\CompanyIndustrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $siswaUser;

    protected StandardType $industry;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CompanyIndustrySeeder::class);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->siswaUser = User::factory()->create([
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->industry = StandardType::byCategory('company_industry')->firstOrFail();
    }

    public function test_can_fetch_form_options_including_industries(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/companies/options');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'industries',
                ],
            ]);
    }

    public function test_admin_can_create_company(): void
    {
        $payload = [
            'name' => 'PT Astra Honda Motor',
            'industry_id' => $this->industry->id,
            'address' => 'Kawasan Industri EJIP, Cikarang',
            'email' => 'hrd@astra-honda.example',
            'phone' => '081234567890',
            'website' => 'https://www.astra-honda.example',
            'pic_name' => 'Budi Santoso',
            'pic_contact' => '081298765432',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/admin/companies', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'PT Astra Honda Motor')
            ->assertJsonPath('data.email', 'hrd@astra-honda.example')
            ->assertJsonPath('data.isActive', true);

        $this->assertDatabaseHas('companies', [
            'name' => 'PT Astra Honda Motor',
            'email' => 'hrd@astra-honda.example',
            'industry_id' => $this->industry->id,
        ]);
    }

    public function test_company_name_and_email_are_unique_and_non_soft_deleted(): void
    {
        Company::create([
            'name' => 'PT Astra Honda Motor',
            'email' => 'hrd@astra-honda.example',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/admin/companies', [
                'name' => 'PT Astra Honda Motor',
                'email' => 'hrd@astra-honda.example',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_company_rejects_invalid_phone_format(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/admin/companies', [
                'name' => 'PT Contoh',
                'phone' => '12345',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    public function test_non_admin_cannot_access_admin_company_routes(): void
    {
        $response = $this->actingAs($this->siswaUser)
            ->getJson('/api/admin/companies');

        $response->assertStatus(403);
    }

    public function test_admin_can_list_and_search_companies(): void
    {
        Company::create([
            'name' => 'PT Astra Honda Motor',
            'industry_id' => $this->industry->id,
            'is_active' => true,
        ]);
        Company::create([
            'name' => 'PT Telkom Indonesia',
            'industry_id' => $this->industry->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/admin/companies?search=Astra');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'PT Astra Honda Motor');
    }

    public function test_admin_can_fetch_company_detail(): void
    {
        $company = Company::create([
            'name' => 'PT Astra Honda Motor',
            'industry_id' => $this->industry->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/admin/companies/{$company->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $company->id)
            ->assertJsonPath('data.name', 'PT Astra Honda Motor');
    }

    public function test_admin_can_update_company(): void
    {
        $company = Company::create([
            'name' => 'PT Astra Honda Motor',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/admin/companies/{$company->id}", [
                'name' => 'PT Astra Honda',
                'phone' => '081298765432',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'PT Astra Honda')
            ->assertJsonPath('data.phone', '081298765432');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'PT Astra Honda',
        ]);
    }

    public function test_admin_can_toggle_active_company(): void
    {
        $company = Company::create([
            'name' => 'PT Astra Honda Motor',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->patchJson("/api/admin/companies/{$company->id}/toggle-active");

        $response->assertStatus(200)
            ->assertJsonPath('data.isActive', false);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_soft_delete_company(): void
    {
        $company = Company::create([
            'name' => 'PT Astra Honda Motor',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/admin/companies/{$company->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('companies', [
            'id' => $company->id,
        ]);
    }
}
