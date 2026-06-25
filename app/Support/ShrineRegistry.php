<?php

namespace App\Support;

use InvalidArgumentException;

class ShrineRegistry
{
    /**
     * @return array<string, mixed>
     */
    public static function config(string $slug): array
    {
        $config = config("shrines.{$slug}");

        if (! is_array($config)) {
            throw new InvalidArgumentException("Unknown shrine [{$slug}].");
        }

        return $config;
    }

    public static function exists(string $slug): bool
    {
        return is_array(config("shrines.{$slug}"));
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(config('shrines', []));
    }

    public static function apiBase(string $slug): string
    {
        $prefix = (string) self::config($slug)['route_prefix'];

        return $prefix === '' ? '' : '/'.trim($prefix, '/');
    }

    /**
     * @return array<string, mixed>
     */
    public static function clientConfig(string $slug): array
    {
        $config = self::config($slug);

        return [
            'slug' => $config['slug'],
            'apiBase' => self::apiBase($slug),
            'heartbeatPath' => '/practitioner-presence',
        ];
    }
}
