<?php
/**
 * MiMargen Platform Bootstrap
 *
 * Provides error handling, autoloading, and base constants.
 * Must be included before any other core file.
 */

// Prevent direct access
if (!defined('MIMARGEN_BOOTSTRAP')) {
    define('MIMARGEN_BOOTSTRAP', true);
}

// Base constants
if (!defined('APP_NAME')) {
    define('APP_NAME', 'MiMargen');
}

if (!defined('APP_SECRET')) {
    // Use a default secret for local/dev. Production should override via env.
    $envSecret = getenv('MIMARGEN_APP_SECRET');
    define('APP_SECRET', $envSecret !== false ? $envSecret : bin2hex(random_bytes(32)));
}

if (!defined('BASE_DOMAIN')) {
    $envDomain = getenv('MIMARGEN_BASE_DOMAIN');
    define('BASE_DOMAIN', $envDomain !== false ? $envDomain : 'mimargen.cl');
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('CORE_DIR')) {
    define('CORE_DIR', APP_ROOT . '/core');
}

if (!defined('DATA_DIR')) {
    define('DATA_DIR', APP_ROOT . '/data/_platform');
}

// Error reporting: show errors in dev, log in production
$env = getenv('MIMARGEN_ENV') ?: 'production';
if ($env === 'development' || $env === 'dev') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', APP_ROOT . '/data/_platform/error.log');
}

// Autoloader for core classes
spl_autoload_register(function (string $class): void {
    $file = CORE_DIR . '/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// Ensure data directory exists
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0750, true);
}
