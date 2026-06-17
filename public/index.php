<?php

use CodeIgniter\Boot;
use Config\Paths;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$publicPath = __DIR__;

// Allow CI4 to handle API and backend routes
if (
    str_starts_with($uri, '/api') ||
    str_starts_with($uri, '/viewer')
) {
    // continue to normal CI4 bootstrap below
} else {
    // Serve real existing files directly
    $requestedFile = realpath($publicPath . $uri);
    if ($uri !== '/' && $requestedFile && str_starts_with($requestedFile, realpath($publicPath)) && is_file($requestedFile)) {
        return false;
    }

    // For all other frontend routes, serve Vue index.html
    $vueIndex = $publicPath . '/index.html';
    if (file_exists($vueIndex)) {
        header('Content-Type: text/html; charset=UTF-8');
        readfile($vueIndex);
        exit;
    }
}

/*
 *---------------------------------------------------------------
 * CHECK PHP VERSION
 *---------------------------------------------------------------
 */

$minPhpVersion = '8.1'; // If you update this, don't forget to update `spark`.
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;

    exit(1);
}

/*
 *---------------------------------------------------------------
 * SET THE CURRENT DIRECTORY
 *---------------------------------------------------------------
 */

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

// LOAD OUR PATHS CONFIG FILE
// This is the line that might need to be changed, depending on your folder structure.
require FCPATH . '../app/Config/Paths.php';
// ^^^ Change this line if you move your application folder

$paths = new Paths();

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
