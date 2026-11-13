<?php

namespace App\Support;

/**
 * Item condition as shown to shoppers and chosen by vendors. The backend stores
 * only the key; the wording lives here so the popover copy is edited in one place.
 * Keep the keys in step with the kasabazaar App\Enums\ProductCondition enum.
 */
final class ProductCondition
{
    public const DEFAULT = 'new';

    /**
     * @return array<string, array{label: string, description: string, tone: string}>
     */
    public static function all(): array
    {
        return [
            'new' => [
                'label' => 'New',
                'description' => 'Brand new and unused. It may be out of its original packaging, or the packaging may have been opened.',
                'tone' => 'success',
            ],
            'factory_sealed' => [
                'label' => 'Factory Sealed',
                'description' => 'Brand new and never opened. Still in the manufacturer\'s original, unbroken seal.',
                'tone' => 'info',
            ],
            'refurbished' => [
                'label' => 'Refurbished',
                'description' => 'Previously owned, then inspected, repaired and restored to working order. It may show light signs of wear.',
                'tone' => 'warning',
            ],
            'used' => [
                'label' => 'Used',
                'description' => 'Previously owned and used. It works, but expect signs of wear. Check the photos and description for details.',
                'tone' => 'neutral',
            ],
        ];
    }

    /**
     * Key => label, for selects and filters.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_map(fn (array $c): string => $c['label'], self::all());
    }

    /**
     * Unknown or missing keys fall back to "new", which is what the backend
     * stores for listings created before conditions existed.
     *
     * @return array{key: string, label: string, description: string, tone: string}
     */
    public static function for(?string $key): array
    {
        $key = array_key_exists((string) $key, self::all()) ? $key : self::DEFAULT;

        return ['key' => $key] + self::all()[$key];
    }
}
