<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\StandardType;
use App\Models\User;
use Database\Seeders\CompanyIndustrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
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
});

test('can fetch form options including industries', function () {
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
});

test('admin can create company', function () {
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
});

test('company name and email are unique and non soft deleted', function () {
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
});

test('company rejects invalid phone format', function () {
    $response = $this->actingAs($this->adminUser)
        ->postJson('/api/admin/companies', [
            'name' => 'PT Contoh',
            'phone' => '12345',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['phone']);
});

test('non admin cannot access admin company routes', function () {
    $response = $this->actingAs($this->siswaUser)
        ->getJson('/api/admin/companies');

    $response->assertStatus(403);
});

test('admin can list and search companies', function () {
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
});

test('admin can fetch company detail', function () {
    $company = Company::create([
        'name' => 'PT Astra Honda Motor',
        'industry_id' => $this->industry->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->adminUser)
        ->getJson("/api/admin/companies/{$company->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'PT Astra Honda Motor');
    $this->assertEquals($company->id, decrypt($response->json('data.id')));
});

test('admin can update company', function () {
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
});

test('admin can toggle active company', function () {
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
});

test('admin can soft delete company', function () {
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
});
