<?php
/**
 * LTPC Enrollment Management System (LTPC_EMS)
 *
 * @copyright  2025-2026 Clarence Buenaflor & Jester Pastor
 * @author     Clarence Buenaflor <cbuenaflor2@ssct.edu.ph>
 * @author     Jester Pastor <pastorjester98@mail.com>
 * @license    Proprietary - All Rights Reserved
 *
 * Unauthorized copying, modification, or distribution of this
 * software is strictly prohibited without express written permission.
 */
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
