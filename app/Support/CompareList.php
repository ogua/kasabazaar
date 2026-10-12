<?php

namespace App\Support;

use Illuminate\Contracts\Session\Session;

/**
 * The shopper's product comparison list, kept in the session (guests can
 * compare too). Holds a small snapshot of each product so the floating tray
 * can render without an API call; the compare page fetches full details.
 */
class CompareList
{
    public const MAX_ITEMS = 4;

    private const KEY = 'storefront.compare';

    public function __construct(private Session $session) {}

    /**
     * @return array<string, array{id: string, name: string, image: ?string}>
     */
    public function all(): array
    {
        return $this->session->get(self::KEY, []);
    }

    /**
     * @return array<int, string>
     */
    public function ids(): array
    {
        return array_keys($this->all());
    }

    public function has(string $productId): bool
    {
        return array_key_exists($productId, $this->all());
    }

    public function isFull(): bool
    {
        return count($this->all()) >= self::MAX_ITEMS;
    }

    /**
     * @return bool false when the list is already full
     */
    public function add(string $productId, string $name, ?string $image = null): bool
    {
        $items = $this->all();

        if (! isset($items[$productId]) && count($items) >= self::MAX_ITEMS) {
            return false;
        }

        $items[$productId] = ['id' => $productId, 'name' => $name, 'image' => $image];
        $this->session->put(self::KEY, $items);

        return true;
    }

    public function remove(string $productId): void
    {
        $items = $this->all();
        unset($items[$productId]);
        $this->session->put(self::KEY, $items);
    }

    public function clear(): void
    {
        $this->session->forget(self::KEY);
    }
}
