<?php

namespace App\Support;

class PublicShrineState
{
    /**
     * Remove offering names from shrine state embedded in public HTML.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    public static function forHtml(array $state): array
    {
        $public = $state;

        if (isset($public['lamps']) && is_array($public['lamps'])) {
            $public['lamps'] = array_map(
                static fn (array $lamp): array => self::withoutName($lamp),
                $public['lamps'],
            );
        }

        if (isset($public['flowers']) && is_array($public['flowers'])) {
            $public['flowers'] = array_map(
                static fn (array $flower): array => self::withoutName($flower),
                $public['flowers'],
            );
        }

        if (isset($public['music']) && is_array($public['music'])) {
            if (isset($public['music']['active']) && is_array($public['music']['active'])) {
                $public['music']['active'] = array_map(
                    static fn (array $offering): array => self::withoutName($offering),
                    $public['music']['active'],
                );
            }
        }

        if (isset($public['water']) && is_array($public['water'])) {
            unset($public['water']['display_name']);
        }

        unset($public['offering_names'], $public['dedication_names']);

        return $public;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private static function withoutName(array $item): array
    {
        unset($item['name']);

        return $item;
    }
}
