<?php

declare(strict_types=1);

/**
 * View helpers for the server-rendered admin panel.
 * Loaded once by App\Admin\Support\View. Functions are guarded so the file
 * can be required multiple times safely.
 */

if (!function_exists('e')) {
    /**
     * Escape a value for safe HTML output.
     */
    function e($value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('rupiah')) {
    /**
     * Format an integer amount as Indonesian Rupiah — mirrors the frontend formatRupiah().
     */
    function rupiah($value): string
    {
        $number = (float) $value;
        if (is_nan($number)) {
            $number = 0;
        }
        return 'Rp ' . number_format($number, 0, ',', '.');
    }
}

if (!function_exists('admin_date')) {
    /**
     * Format a MySQL datetime as id-ID short date (optionally with time).
     */
    function admin_date(?string $value, bool $withTime = false): string
    {
        if (empty($value)) {
            return '-';
        }
        try {
            $dt = new DateTime($value);
        } catch (\Exception) {
            return e($value);
        }
        return $withTime ? $dt->format('d/m/Y H.i') : $dt->format('d/m/Y');
    }
}

if (!function_exists('badge_class')) {
    /**
     * Map an order status to its badge CSS class — mirrors statusBadgeClass() in React.
     */
    function badge_class(string $status): string
    {
        switch ($status) {
            case 'menunggu konfirmasi':
                return 'badge-pending';
            case 'diproses':
                return 'badge-processing';
            case 'siap diambil':
            case 'diantar':
                return 'badge-ready';
            case 'selesai':
                return 'badge-done';
            case 'dibatalkan':
                return 'badge-cancelled';
            default:
                return '';
        }
    }
}

if (!function_exists('product_image')) {
    /**
     * Resolve a product cover image to a same-origin URL, or null when there is
     * only a gradient / a legacy frontend-bundled asset the backend can't serve.
     */
    function product_image(?string $coverImage): ?string
    {
        if (empty($coverImage)) {
            return null;
        }
        if (str_starts_with($coverImage, 'uploads/')) {
            return '/' . $coverImage;
        }
        return null;
    }
}

if (!function_exists('old_query')) {
    /**
     * Build a query string from an associative array, dropping empty values.
     */
    function old_query(array $params): string
    {
        $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
        return empty($params) ? '' : '?' . http_build_query($params);
    }
}
