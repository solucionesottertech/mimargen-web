<?php
/**
 * Platform Bootstrap
 *
 * Provides error handling, autoloading, and base constants.
 * Included by landing.php when invoked from the apex domain context.
 *
 * Contract: landing.php is NOT called directly — it is dispatched by
 * core/bootstrap.php when the resolved host matches BASE_DOMAIN and
 * the context is UNKNOWN.
 */

// Prevent direct access
if (!defined('OTTER_BOOTSTRAP')) {
    define('OTTER_BOOTSTRAP', true);
}

// ── Base constants ──────────────────────────────────────────────
if (!defined('APP_NAME')) {
    define('APP_NAME', getenv('APP_NAME') ?: 'MiMargen');
}

if (!defined('APP_SECRET')) {
    $envSecret = getenv('APP_SECRET');
    define('APP_SECRET', $envSecret !== false && $envSecret !== '' ? $envSecret : bin2hex(random_bytes(32)));
}

if (!defined('BASE_DOMAIN')) {
    $envDomain = getenv('BASE_DOMAIN');
    define('BASE_DOMAIN', $envDomain !== false && $envDomain !== '' ? $envDomain : ($_SERVER['HTTP_HOST'] ?? 'mimargen.cl'));
}

// ── Error reporting ─────────────────────────────────────────────
$env = getenv('APP_ENV') ?: 'production';
if ($env === 'development' || $env === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ── Autoloader for core classes ─────────────────────────────────
spl_autoload_register(function (string $class): void {
    $coreDir = __DIR__;
    $file = $coreDir . '/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
