<?php

declare(strict_types=1);

namespace App\Support;

class SocialMedia
{
    public static function platforms(): array
    {
        return config('social_media_platforms', []);
    }

    public static function platformKeys(): array
    {
        return array_keys(self::platforms());
    }

    public static function buildUrl(string $platform, string $username): string
    {
        $prefix = self::platforms()[$platform]['url'] ?? null;

        return $prefix !== null ? $prefix.ltrim($username, '@') : $username;
    }

    /**
     * Normalize any stored value (legacy assoc map or new list) into a list of
     * [{platform, username, url}] entries. Backward-compatible with old rows
     * that store full URLs keyed by platform.
     */
    public static function normalize(mixed $socialMedia): array
    {
        if (is_string($socialMedia)) {
            $socialMedia = json_decode($socialMedia, true) ?? [];
        }

        if (! is_array($socialMedia)) {
            return [];
        }

        if (! array_is_list($socialMedia)) {
            return self::fromLegacyMap($socialMedia);
        }

        return array_values(array_filter(array_map(
            static function (mixed $item): ?array {
                if (! is_array($item) || ! isset($item['platform'], $item['username'])) {
                    return null;
                }

                $platform = (string) $item['platform'];
                $username = (string) $item['username'];

                return [
                    'platform' => $platform,
                    'username' => $username,
                    'url' => self::buildUrl($platform, $username),
                ];
            },
            $socialMedia
        )));
    }

    /**
     * Prepare the value to be persisted: list of [{platform, username}].
     */
    public static function storable(mixed $socialMedia): array
    {
        return array_map(
            static fn (array $item): array => [
                'platform' => $item['platform'],
                'username' => $item['username'],
            ],
            self::normalize($socialMedia)
        );
    }

    /**
     * @param  array<string, mixed>  $map
     */
    private static function fromLegacyMap(array $map): array
    {
        $items = [];

        foreach ($map as $platform => $url) {
            if (! is_string($platform) || ! is_string($url) || $url === '') {
                continue;
            }

            $items[] = [
                'platform' => $platform,
                'username' => self::extractUsername($platform, $url),
                'url' => $url,
            ];
        }

        return array_values($items);
    }

    private static function extractUsername(string $platform, string $url): string
    {
        $prefix = self::platforms()[$platform]['url'] ?? null;

        if ($prefix !== null && str_starts_with($url, $prefix)) {
            return rtrim(substr($url, strlen($prefix)), '/');
        }

        $pos = strrpos($url, '/');

        return $pos === false ? $url : rtrim(substr($url, $pos + 1), '/');
    }
}
