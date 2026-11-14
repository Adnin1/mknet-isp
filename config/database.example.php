<?php
/**
 * Copy to config/database.php on each environment and insert the credentials
 * issued by that environment's hosting provider.
 *
 * IMPORTANT:
 * - config/database.php is ignored by Git and must never be committed.
 * - Never reuse credentials that were previously committed to the repository.
 * - Production requires MKNET_ENV=production and this file or MKNET_DB_* env vars.
 */
return [
    [
        'host' => 'YOUR_DATABASE_HOST',
        'db' => 'YOUR_DATABASE_NAME',
        'user' => 'YOUR_DATABASE_USER',
        'pass' => 'REPLACE_WITH_A_NEW_DATABASE_PASSWORD',
        'charset' => 'utf8mb4',
    ],
];
