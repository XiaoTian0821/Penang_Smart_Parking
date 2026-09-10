<?php
/**
 * Plate Normalization Helper
 */
declare(strict_types=1);

/**
 * Normalize a vehicle plate number to standard format
 * Removes spaces, hyphens, and converts to uppercase
 * Example: "WXY 1234" -> "WXY1234"
 */
function normalizePlate(string $plate): string {
    // Remove spaces, hyphens, and common separators
    $clean = preg_replace('/[\s\-\.]+/', '', $plate);
    // Convert to uppercase
    $clean = strtoupper($clean);
    // Remove any non-alphanumeric characters
    $clean = preg_replace('/[^A-Z0-9]/', '', $clean);
    return $clean;
}

/**
 * Format a normalized plate for display
 */
function formatPlate(string $normalized): string {
    // Try to add space between letters and numbers
    // e.g., WXY1234 -> WXY 1234
    return preg_replace('/([A-Z]+)(\d+)/', '$1 $2', $normalized) ?: $normalized;
}
