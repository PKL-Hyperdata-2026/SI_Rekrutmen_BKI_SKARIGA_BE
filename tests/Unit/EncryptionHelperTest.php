<?php

uses(Tests\TestCase::class);

test('aes_encrypt and aes_decrypt work with default key', function () {
    $plainText = 'Skariga-Secret-Data-12345';
    $encrypted = aes_encrypt($plainText);

    expect($encrypted)->not->toBe($plainText)
        ->and(aes_decrypt($encrypted))->toBe($plainText);
});

test('aes_encrypt and aes_decrypt work with custom key', function () {
    $customKey = 'my-super-secret-key-32-chars!!';
    $plainText = 'Confidential-Payload';
    $encrypted = aes_encrypt($plainText, $customKey);

    expect($encrypted)->not->toBe($plainText)
        ->and(aes_decrypt($encrypted, $customKey))->toBe($plainText)
        ->and(aes_decrypt($encrypted, 'different-wrong-key-32-chars!!'))->toBeNull();
});

test('aes_decrypt returns null when ciphertext is tampered', function () {
    $encrypted = aes_encrypt('SafePayload');
    $tampered = substr_replace($encrypted, 'X', 10, 1);

    expect(aes_decrypt($tampered))->toBeNull();
});

test('encrypt and decrypt work for integers and strings', function () {
    $id = 42;
    $encrypted = encrypt($id);

    expect($encrypted)->toBeString()
        ->and($encrypted)->not->toBe((string) $id)
        ->and(decrypt($encrypted))->toBe(42);
});

test('encrypt_recursive encrypts nested array IDs', function () {
    $options = [
        'companies' => [
            ['id' => 1, 'name' => 'PT A'],
            ['id' => 2, 'name' => 'PT B'],
        ],
        'roles' => [
            ['value' => 'admin', 'label' => 'Admin'],
        ],
    ];

    $encryptedOptions = encrypt_recursive($options);

    expect(decrypt($encryptedOptions['companies'][0]['id']))->toBe(1)
        ->and(decrypt($encryptedOptions['companies'][1]['id']))->toBe(2)
        ->and($encryptedOptions['companies'][0]['name'])->toBe('PT A')
        ->and($encryptedOptions['roles'][0]['value'])->toBe('admin');
});

