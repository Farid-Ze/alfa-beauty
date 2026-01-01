<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * TaxService
 * 
 * Handles tax calculations for orders.
 * Supports Indonesian PPN (11%) and e-Faktur integration readiness.
 */
class TaxService
{
    /**
     * Get the default tax rate from config.
     */
    public function getDefaultTaxRate(): float
    {
        return (float) config('services.tax.default_rate', 11.00);
    }

    /**
     * Get the tax-exempt rate.
     */
    public function getTaxExemptRate(): float
    {
        return (float) config('services.tax.exempt_rate', 0.00);
    }

    /**
     * Calculate tax for a single item.
     */
    public function calculateItemTax(
        float $unitPrice,
        int $quantity,
        ?float $taxRate = null,
        bool $isTaxInclusive = false
    ): array {
        $taxRate = $taxRate ?? $this->getDefaultTaxRate();
        $lineTotal = $unitPrice * $quantity;

        if ($isTaxInclusive) {
            // Price already includes tax, extract tax amount
            $subtotalBeforeTax = $lineTotal / (1 + ($taxRate / 100));
            $taxAmount = $lineTotal - $subtotalBeforeTax;
        } else {
            // Price is before tax, calculate tax on top
            $subtotalBeforeTax = $lineTotal;
            $taxAmount = $subtotalBeforeTax * ($taxRate / 100);
        }

        return [
            'unit_price_before_tax' => $isTaxInclusive ? $unitPrice / (1 + ($taxRate / 100)) : $unitPrice,
            'subtotal_before_tax' => round($subtotalBeforeTax, 2),
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 2),
            'line_total' => round($isTaxInclusive ? $lineTotal : $subtotalBeforeTax + $taxAmount, 2),
        ];
    }

    /**
     * Calculate tax for entire order.
     * @phpstan-ignore-next-line
     */
    public function calculateOrderTax(Order $order): array
    {
        try {
            $subtotalBeforeTax = 0;
            $totalTaxAmount = 0;

            /** @phpstan-ignore-next-line */
            foreach ($order->items as $item) {
                $itemTax = $this->calculateItemTax(
                    (float) ($item->unit_price ?? 0),
                    (int) ($item->quantity ?? 0),
                    (float) ($item->tax_rate ?? $this->getDefaultTaxRate()),
                    (bool) $order->is_tax_inclusive
                );

                $subtotalBeforeTax += $itemTax['subtotal_before_tax'];
                $totalTaxAmount += $itemTax['tax_amount'];
            }

            return [
                'subtotal_before_tax' => round($subtotalBeforeTax, 2),
                'tax_rate' => (float) ($order->tax_rate ?? $this->getDefaultTaxRate()),
                'tax_amount' => round($totalTaxAmount, 2),
                'subtotal_after_tax' => round($subtotalBeforeTax + $totalTaxAmount, 2),
            ];
        } catch (\Exception $e) {
            Log::error('TaxService::calculateOrderTax failed', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);

            // Return safe defaults
            return [
                'subtotal_before_tax' => 0,
                'tax_rate' => $this->getDefaultTaxRate(),
                'tax_amount' => 0,
                'subtotal_after_tax' => 0,
            ];
        }
    }

    /**
     * Apply tax calculation to order and save.
     * @phpstan-ignore-next-line
     */
    public function applyTaxToOrder(Order $order, bool $save = true): Order
    {
        try {
            $taxData = $this->calculateOrderTax($order);

            /** @phpstan-ignore-next-line */
            $order->subtotal_before_tax = $taxData['subtotal_before_tax'];
            /** @phpstan-ignore-next-line */
            $order->tax_amount = $taxData['tax_amount'];

            // Update items with tax breakdown
            foreach ($order->items as $item) {
                $itemTax = $this->calculateItemTax(
                    (float) $item->unit_price,
                    (int) $item->quantity,
                    (float) ($order->tax_rate ?? $this->getDefaultTaxRate()),
                    (bool) $order->is_tax_inclusive
                );

                /** @phpstan-ignore-next-line */
                $item->unit_price_before_tax = $itemTax['unit_price_before_tax'];
                /** @phpstan-ignore-next-line */
                $item->subtotal_before_tax = $itemTax['subtotal_before_tax'];
                /** @phpstan-ignore-next-line */
                $item->tax_rate = $itemTax['tax_rate'];
                /** @phpstan-ignore-next-line */
                $item->tax_amount = $itemTax['tax_amount'];

                if ($save) {
                    $item->save();
                }
            }

            // Recalculate total
            $order->recalculateTotals();

            if ($save) {
                $order->save();
            }

            return $order;
        } catch (\Exception $e) {
            Log::error('TaxService::applyTaxToOrder failed', [
                'order_id' => $order->id ?? null,
                'error' => $e->getMessage(),
            ]);

            // Return order unchanged to prevent data corruption
            return $order;
        }
    }

    /**
     * Check if user/order qualifies for tax exemption.
     * E.g., certain business types or export orders.
     */
    public function isTaxExempt(Order $order): bool
    {
        // Add business logic for tax exemption
        // Examples: 
        // - Export orders
        // - Certain business entity types
        // - Special agreements

        return false;
    }

    /**
     * Format tax amount for e-Faktur.
     * Indonesian e-Faktur requires specific formatting.
     * @phpstan-ignore-next-line
     */
    public function formatForEFaktur(Order $order): array
    {
        /** @phpstan-ignore-next-line */
        return [
            'tanggal_faktur' => $order->created_at->format('d/m/Y'),
            'npwp_pembeli' => $order->user?->npwp ?? '',
            'nama_pembeli' => $order->user?->business_name ?? $order->user?->name ?? '',
            'alamat_pembeli' => $order->shipping_address ?? '',
            'dpp' => number_format((float) ($order->subtotal_before_tax ?? 0), 0, '', ''),
            'ppn' => number_format((float) ($order->tax_amount ?? 0), 0, '', ''),
            'tarif_ppn' => $order->tax_rate ?? $this->getDefaultTaxRate(),
            'keterangan' => "Order #{$order->order_number}",
            'items' => $order->items->map(fn($item) => [
                'nama_barang' => $item->product?->name ?? '',
                'harga_satuan' => number_format((float) ($item->unit_price_before_tax ?? 0), 0, '', ''),
                'jumlah' => (int) $item->quantity,
                'total_harga' => number_format((float) ($item->subtotal_before_tax ?? 0), 0, '', ''),
                'ppn' => number_format((float) ($item->tax_amount ?? 0), 0, '', ''),
            ])->toArray(),
        ];
    }
}
