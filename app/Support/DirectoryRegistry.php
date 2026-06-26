<?php

namespace App\Support;

class DirectoryRegistry
{
    /**
     * @return array<string, mixed>
     */
    public static function config(): array
    {
        return config('directory', []);
    }

    /**
     * @return list<list<array{slug: string, label: string, url: string, image: string|null, image_class: string|null, alt: string}>>
     */
    public static function rows(): array
    {
        $rows = [];

        foreach (self::config()['rows'] ?? [] as $row) {
            $entries = [];

            foreach ($row as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $shrineSlug = $entry['shrine_slug'] ?? null;
                $slug = (string) ($entry['slug'] ?? $shrineSlug ?? '');
                $label = (string) ($entry['label'] ?? $slug);

                if ($shrineSlug && ShrineRegistry::exists($shrineSlug)) {
                    $shrine = ShrineRegistry::config($shrineSlug);
                    $entries[] = [
                        'slug' => $slug,
                        'label' => $label,
                        'url' => ShrineRegistry::shrinePath($shrineSlug),
                        'image' => (string) $shrine['deity_image'],
                        'image_class' => (string) $shrine['deity_image_class'],
                        'alt' => (string) $shrine['deity_alt'],
                    ];

                    continue;
                }

                $entries[] = [
                    'slug' => $slug,
                    'label' => $label,
                    'url' => '#'.$slug,
                    'image' => isset($entry['image']) ? (string) $entry['image'] : null,
                    'image_class' => isset($entry['image_class']) ? (string) $entry['image_class'] : null,
                    'alt' => (string) ($entry['alt'] ?? $label),
                ];
            }

            if ($entries !== []) {
                $rows[] = $entries;
            }
        }

        return $rows;
    }

    /**
     * @return array{title: string, description: string, canonical: string, image: string, structured_data: array<string, mixed>}
     */
    public static function seoMeta(): array
    {
        $config = self::config();
        $title = (string) ($config['seo_title'] ?? $config['page_title'] ?? 'Buddhist Altar');
        $description = (string) ($config['meta_description'] ?? '');
        $canonical = rtrim((string) config('app.url'), '/').'/';
        $image = asset('icons/icon-512.png');

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'image' => $image,
            'structured_data' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'Buddhist Altar',
                'alternateName' => $title,
                'url' => $canonical,
                'description' => $description,
                'inLanguage' => 'en',
                'isAccessibleForFree' => true,
            ],
        ];
    }
}
