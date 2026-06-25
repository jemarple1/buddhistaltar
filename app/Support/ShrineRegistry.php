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

        $vapidPublicKey = config('services.webpush.public_key');

        return [
            'slug' => $config['slug'],
            'apiBase' => self::apiBase($slug),
            'heartbeatPath' => '/practitioner-presence',
            'vapidPublicKey' => is_string($vapidPublicKey) && $vapidPublicKey !== '' ? $vapidPublicKey : null,
        ];
    }

    /**
     * @return list<array{url: string, image: string, label: string, orbit_angle: float, orbit_delay: float}>
     */
    public static function crosslinksFor(string $slug): array
    {
        $others = array_values(array_filter(
            self::slugs(),
            static fn (string $otherSlug): bool => $otherSlug !== $slug,
        ));

        $count = count($others);

        if ($count === 0) {
            return [];
        }

        $startAngle = 215.0;
        $endAngle = 325.0;
        $crosslinks = [];

        foreach ($others as $index => $otherSlug) {
            $config = self::config($otherSlug);
            $prefix = (string) $config['route_prefix'];
            $angle = $count === 1
                ? 270.0
                : $startAngle + ($index * ($endAngle - $startAngle) / ($count - 1));

            $crosslinks[] = [
                'url' => $prefix === '' ? '/' : '/'.trim($prefix, '/'),
                'image' => (string) $config['deity_image'],
                'label' => 'Visit the '.self::crosslinkDisplayName($otherSlug).' shrine',
                'orbit_angle' => $angle,
                'orbit_delay' => $index * -2.75,
            ];
        }

        return $crosslinks;
    }

    public static function crosslinkDisplayName(string $slug): string
    {
        return match ($slug) {
            'avalokiteshvara' => 'Avalokiteśvara',
            'amitayus' => 'Amitāyus',
            'amitabha' => 'Amitābha',
            default => ucfirst($slug),
        };
    }
}
