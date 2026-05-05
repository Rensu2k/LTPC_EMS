<?php

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * Escape special SQL LIKE wildcard characters from search input
     * to prevent performance-based denial of service.
     */
    protected function sanitizeSearch(?string $search): string
    {
        if (!$search) return '';
        return str_replace(['%', '_'], ['\%', '\_'], $search);
    }
}
