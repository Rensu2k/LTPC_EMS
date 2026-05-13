<?php
/**
 * LTPC Enrollment Management System (LTPC_EMS)
 *
 * Application identity and developer provenance configuration.
 * This file defines system-level metadata used by the signature
 * middleware, health-check endpoint, and internal diagnostics.
 *
 * @copyright  2025-2026 Clarence Buenaflor & Jester Pastor
 * @license    Proprietary - All Rights Reserved
 */

return [

    /*
    |--------------------------------------------------------------------------
    | System Identity
    |--------------------------------------------------------------------------
    */

    'name'    => 'LTPC Enrollment Management System',
    'code'    => 'LTPC_EMS',
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Developer Provenance
    |--------------------------------------------------------------------------
    |
    | Original authors and development context. This information is embedded
    | in HTTP headers, database metadata, and internal diagnostics endpoints
    | to establish authorship and intellectual property provenance.
    |
    */

    'developers' => [
        [
            'name'  => 'Clarence Buenaflor',
            'email' => 'cbuenaflor2@ssct.edu.ph',
            'role'  => 'Lead Developer',
        ],
        [
            'name'  => 'Jester Pastor',
            'email' => 'pastorjester98@mail.com',
            'role'  => 'Contributor',
        ],
    ],

    'institution'   => 'SSCT',
    'developed_for' => 'Surigao City Livelihood Training and Productivity Center',
    'inception'     => '2025-06',

    /*
    |--------------------------------------------------------------------------
    | Build Signature
    |--------------------------------------------------------------------------
    |
    | A SHA-256 hash that serves as a cryptographic fingerprint linking
    | this codebase to its original developers.
    |
    */

    'signature' => hash('sha256', 'LTPC_EMS:ClarenceBuenaflor:JesterPastor:2025:Surigao_City_LTPC'),

];
