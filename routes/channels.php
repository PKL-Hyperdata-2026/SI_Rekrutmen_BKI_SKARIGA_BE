<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['prefix' => 'api', 'middleware' => ['auth:sanctum']]);
Broadcast::routes(['middleware' => ['auth:sanctum']]);

Broadcast::channel('user.{id}', function ($user, $id) {
    if (is_numeric($id) && (int) $user->id === (int) $id) {
        return true;
    }

    try {
        $decrypted = decrypt((string) $id);

        return $decrypted !== null && (int) $user->id === (int) $decrypted;
    } catch (Throwable) {
        return false;
    }
});
