<?php
/**
 * config.php — shared configuration for contact.php and admin.php
 * ------------------------------------------------------------------
 * Fill in your real values below, then upload this alongside
 * contact.php and admin.php. Keep it OUTSIDE any publicly-listable
 * folder if your host allows it — otherwise it's fine in the same
 * folder as your site, since PHP files execute rather than display
 * their contents when visited directly.
 * ------------------------------------------------------------------
 */

// Database (from hPanel → Databases → MySQL Databases)
const DB_HOST = 'localhost';                    // usually 'localhost' on Hostinger shared hosting
const DB_NAME = 'u495789616_contacts';          // your actual prefixed database name
const DB_USER = 'u495789616_omnipulse';       // your actual prefixed database user
const DB_PASS = 'Omnipulse#2026#';        // the password you set for that user

// Email notification (used by contact.php)
const RECIPIENT_EMAIL = 'info@support.omnipulseagency.com';
const SITE_NAME       = 'OmniPulse';
const FROM_ADDRESS    = 'noreply@omnipulseagency.com'; // must be a real mailbox on your domain

// Admin login (used by admin.php)
// Generate this with hash-generator.php — see the instructions there.
// This placeholder hash is for the password "changeme" — do NOT leave it as-is.
const ADMIN_PASSWORD_HASH = '$2y$10$YSRMTtZ.eDN0h1mND5g7deIC/Q6TAKXl6UjzcIVjAIGTD5Vw3bKvO';

/**
 * Returns a connected PDO instance. Shared by contact.php and admin.php
 * so there is only one place that knows how to talk to the database.
 */
function getPdo(): PDO {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
}