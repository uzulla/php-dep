<?php

/**
 * Formatter helper functions.
 */

function formatDate($date, $format = 'Y-m-d H:i:s')
{
    return date($format, strtotime($date));
}

function formatCurrency($amount, $currency = 'USD')
{
    return number_format($amount, 2) . ' ' . $currency;
}

function formatFileSize($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
