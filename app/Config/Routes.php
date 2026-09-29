<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
/** @var RouteCollection $routes */

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');

// Recommended for APIs
$routes->setAutoRoute(false);

// Health check / basic
$routes->get('/', 'Home::index');

// -------------------------
// API ROUTES
// -------------------------
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function ($routes) {
    $routes->get('auth/csrf', 'AuthController::csrf');

    $routes->post('auth/login', 'AuthController::login'); 
    $routes->post('auth/logout', 'AuthController::logout');
    $routes->get('auth/me', 'UserController::me', ['filter' => 'auth']);
    $routes->post('auth/forgot-password', 'AuthController::forgotPassword');
    $routes->post('auth/reset-password',  'AuthController::resetPassword');
    $routes->post('auth/change-password',  'AuthController::changePassword');

    $routes->get('dashboard/summary', 'DashboardController::summary', [
        'filter' => 'auth,permission:dashboard.access'
    ]);

    // Users page access
    $routes->get('users', 'UsersController::index', [
        'filter' => 'auth,permission:users.view'
    ]);

    $routes->post('users', 'UsersController::create', [
        'filter' => 'auth,permission:users.create'
    ]);

    $routes->put('users/(:num)', 'UsersController::update/$1', [
        'filter' => 'auth,permission:users.update'
    ]);

    $routes->delete('users/(:num)', 'UsersController::delete/$1', [
        'filter' => 'auth,permission:users.delete'
    ]);

    $routes->group('admin', ['filter' => 'auth'], function ($routes) {

        // ========= USERS (CRUD) =========
        // ✅ List users: view permission
        $routes->get('users', 'Admin\UsersController::index', ['filter' => 'permission:users.view']);

        // ✅ CRUD permissions
        $routes->post('users', 'Admin\UsersController::create', ['filter' => 'permission:users.create']);
        $routes->put('users/(:segment)', 'Admin\UsersController::update/$1', ['filter' => 'permission:users.update']);
        $routes->delete('users/(:segment)', 'Admin\UsersController::delete/$1', ['filter' => 'permission:users.delete']);

        // ✅ Assign roles: separate permission
        $routes->get('users/(:segment)/roles', 'Admin\UsersController::roles/$1', ['filter' => 'permission:users.roles.assign']);
        $routes->put('users/(:segment)/roles', 'Admin\UsersController::setRoles/$1', ['filter' => 'permission:users.roles.assign']);

        // ✅ Toggle superadmin: separate permission
        $routes->put('users/(:segment)/superadmin', 'Admin\UsersController::setSuperadmin/$1', ['filter' => 'permission:users.superadmin.toggle']);

        // Unlock user account
        $routes->post('users/(:segment)/unlock', 'Admin\SecurityController::unlock/$1', ['filter' => 'permission:users.unlock']);

        // ========= ROLES (CRUD) =========
        $routes->get('roles', 'Admin\RolesController::index', ['filter' => 'permission:roles.view']);
        $routes->post('roles', 'Admin\RolesController::create', ['filter' => 'permission:roles.create']);
        $routes->put('roles/(:segment)', 'Admin\RolesController::update/$1', ['filter' => 'permission:roles.update']);
        $routes->delete('roles/(:segment)', 'Admin\RolesController::delete/$1', ['filter' => 'permission:roles.delete']);
        $routes->get('roles/dropdown', 'Admin\RolesController::dropdown');


        // ========= PERMISSIONS (VIEW ONLY) =========
        $routes->get('permissions', 'Admin\PermissionsController::index', ['filter' => 'permission:permissions.view']);


        // ========= ROLE PERMISSIONS (ASSIGNMENT) =========
        $routes->get('roles/(:segment)/permissions', 'Admin\RolePermissionsController::get/$1', ['filter' => 'permission:rolepermissions.assign']);
        $routes->put('roles/(:segment)/permissions', 'Admin\RolePermissionsController::set/$1', ['filter' => 'permission:rolepermissions.assign']);


        // ========= AUDIT LOGS =========
        $routes->get('audit-logs', 'Admin\AuditLogsController::index', ['filter' => 'permission:audit.view']);


        // ========= LOGIN ATTEMPTS / LOCKS =========
        $routes->get('login-attempts', 'Admin\SecurityController::attempts', ['filter' => 'permission:security.view']);
    });
});

// -------------------------
// VIEWER ROUTES
// -------------------------
$routes->group('viewer', ['namespace' => 'App\Controllers\Api'], function ($routes) {});


$routes->get('/', 'Home::index');
