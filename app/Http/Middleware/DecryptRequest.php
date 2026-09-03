<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DecryptRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Process and decrypt route parameters (e.g. {jobVacancy}, {student}, {id})
        if ($route = $request->route()) {
            foreach ($route->parameters() as $paramName => $paramValue) {
                if (is_string($paramValue) && ! is_numeric($paramValue)) {
                    try {
                        $decrypted = decrypt($paramValue);
                        if (is_numeric($decrypted)) {
                            $route->setParameter($paramName, (string) $decrypted);
                        }
                    } catch (\Throwable) {
                        if (app()->isProduction()) {
                            abort(422, "Parameter [{$paramName}] is not a valid encrypted identifier.");
                        }
                    }
                }
            }
        }

        // 2. Process and decrypt query string parameters
        if ($request->query->count() > 0) {
            $decryptedQuery = $this->decryptArray($request->query->all());
            $request->query->replace($decryptedQuery);
        }

        // 3. Process and decrypt request body / JSON payload
        if ($request->isJson()) {
            $decryptedInput = $this->decryptArray($request->json()->all());
            $request->json()->replace($decryptedInput);
            $request->request->replace($decryptedInput);
        } elseif ($request->request->count() > 0) {
            $decryptedInput = $this->decryptArray($request->all());
            $request->replace($decryptedInput);
        }

        return $next($request);
    }

    /**
     * Recursively decrypt ID keys in an array (including arrays of IDs like major_ids).
     */
    protected function decryptArray(array $data, ?string $parentKey = null): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->decryptArray($value, (string) $key);
            } else {
                $isTargetKey = $this->isIdKey((string) $key) || ($parentKey !== null && $this->isIdKey($parentKey));
                if ($isTargetKey && is_string($value) && $value !== '') {
                    try {
                        $decrypted = decrypt($value);
                        if (is_numeric($decrypted)) {
                            $data[$key] = (int) $decrypted;
                        }
                    } catch (\Throwable) {
                        if (app()->isProduction() && ! is_numeric($value)) {
                            abort(422, "Field [{$key}] must be a valid encrypted identifier.");
                        }

                        if (! app()->isProduction() && is_numeric($value)) {
                            $data[$key] = (int) $value;
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Determine whether a key represents an ID.
     */
    protected function isIdKey(string $key): bool
    {
        return $key === 'id'
            || $key === 'ids'
            || (bool) preg_match('/(_id|Id|_ids|Ids)$/', $key);
    }
}
