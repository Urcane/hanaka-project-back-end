<?php

declare(strict_types=1);

namespace App\Domain\Order;

/**
 * Raised when an order cannot be created because a size ran out between the
 * moment it was added to the cart and the moment checkout was submitted.
 * Carries a customer-facing Indonesian message.
 */
class InsufficientStockException extends \RuntimeException
{
    public static function forItem(string $productName, string $sizeLabel, int $available): self
    {
        $stockText = $available > 0 ? "sisa {$available}" : 'habis';

        return new self("Stok {$productName} ({$sizeLabel}) tidak mencukupi — {$stockText}.");
    }
}
