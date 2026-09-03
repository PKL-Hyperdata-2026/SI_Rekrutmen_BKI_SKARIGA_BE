<?php

use App\Http\Middleware\DecryptRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

test('DecryptApiIds decrypts body and query parameters', function () {
    $encryptedId = encrypt(42);
    $encryptedCompanyId = encrypt(99);
    $encryptedMajorId1 = encrypt(10);
    $encryptedMajorId2 = encrypt(20);

    $request = Request::create('/api/test?filter_id=' . $encryptedId, 'POST', [
        'company_id' => $encryptedCompanyId,
        'companyId' => $encryptedCompanyId,
        'major_ids' => [$encryptedMajorId1, $encryptedMajorId2],
        'name' => 'Tech Corp',
    ]);

    $middleware = new DecryptRequest();
    $response = $middleware->handle($request, function ($req) {
        expect($req->query('filter_id'))->toBe(42)
            ->and($req->input('company_id'))->toBe(99)
            ->and($req->input('companyId'))->toBe(99)
            ->and($req->input('major_ids'))->toBe([10, 20])
            ->and($req->input('name'))->toBe('Tech Corp');

        return response()->json(['status' => 'ok']);
    });

    expect($response->getStatusCode())->toBe(200);
});

test('DecryptApiIds decrypts json request payload containing arrays of ids', function () {
    $encryptedMajorId = encrypt(15);
    $encryptedCompanyId = encrypt(88);

    $request = Request::create('/api/test-json', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'company_id' => $encryptedCompanyId,
        'major_ids' => [$encryptedMajorId],
    ]));

    $middleware = new DecryptRequest();
    $response = $middleware->handle($request, function ($req) {
        expect($req->input('company_id'))->toBe(88)
            ->and($req->input('major_ids'))->toBe([15]);

        return response()->json(['status' => 'ok']);
    });

    expect($response->getStatusCode())->toBe(200);
});

test('DecryptApiIds decrypts route parameters before action', function () {
    Route::get('/api/test-route/{student}', function ($student) {
        return response()->json(['resolved_student' => $student]);
    })->middleware(DecryptRequest::class);

    $encryptedStudentId = encrypt(77);

    $response = $this->getJson('/api/test-route/' . $encryptedStudentId);

    $response->assertOk()
        ->assertJson(['resolved_student' => '77']);
});

test('encrypt_ids_recursive encrypts nested array IDs', function () {
    $options = [
        'companies' => [
            ['id' => 1, 'name' => 'PT A'],
            ['id' => 2, 'name' => 'PT B'],
        ],
        'roles' => [
            ['value' => 'admin', 'label' => 'Admin'],
        ],
    ];

    $encryptedOptions = encrypt_ids_recursive($options);

    expect(decrypt($encryptedOptions['companies'][0]['id']))->toBe(1)
        ->and(decrypt($encryptedOptions['companies'][1]['id']))->toBe(2)
        ->and($encryptedOptions['companies'][0]['name'])->toBe('PT A')
        ->and($encryptedOptions['roles'][0]['value'])->toBe('admin');
});

test('UserResource serializes ID as encrypted string', function () {
    $user = new \App\Models\User([
        'full_name' => 'John Doe',
        'email' => 'john@example.com',
        'role' => 'admin',
        'is_active' => true,
    ]);
    $user->id = 55;

    $resource = (new UserResource($user))->toArray(request());

    expect($resource['id'])->toBeString()
        ->and($resource['id'])->not->toBe('55')
        ->and(decrypt($resource['id']))->toBe(55);
});
