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

    public static function shrinePath(string $slug): string
    {
        $prefix = (string) self::config($slug)['route_prefix'];

        return '/'.trim($prefix !== '' ? $prefix : $slug, '/');
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

        /** @var list<float> Compass slots around the image; top center (270°) is omitted. */
        $angleSlots = [315.0, 45.0, 135.0, 225.0, 0.0, 180.0, 90.0];
        $crosslinks = [];

        foreach ($others as $index => $otherSlug) {
            $config = self::config($otherSlug);
            $angle = $angleSlots[$index % count($angleSlots)];

            $crosslinks[] = [
                'url' => self::shrinePath($otherSlug),
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
            'shakyamuni' => 'Śākyamuni',
            'avalokiteshvara' => 'Avalokiteśvara',
            'vajrasattva' => 'Vajrasattva',
            'amitayus' => 'Amitāyus',
            'amitabha' => 'Amitābha',
            default => ucfirst($slug),
        };
    }

    public static function canonicalUrl(string $slug): string
    {
        $base = rtrim((string) config('app.url'), '/');

        return $base.self::shrinePath($slug);
    }

    /**
     * @return array{title: string, description: string, canonical: string, image: string, structured_data: array<string, mixed>}
     */
    public static function seoMeta(string $slug): array
    {
        $config = self::config($slug);
        $title = (string) ($config['seo_title'] ?? $config['page_title']);
        $description = (string) $config['meta_description'];
        $canonical = self::canonicalUrl($slug);

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'image' => asset($config['deity_image']),
            'structured_data' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebApplication',
                'name' => $title,
                'alternateName' => 'Buddhist Altar',
                'url' => $canonical,
                'description' => $description,
                'applicationCategory' => 'LifestyleApplication',
                'operatingSystem' => 'Web',
                'inLanguage' => 'en',
                'isAccessibleForFree' => true,
                'image' => asset($config['deity_image']),
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'USD',
                ],
            ],
        ];
    }
}
