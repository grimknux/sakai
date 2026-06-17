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

    $routes->post('auth/login', 'AuthController::login');  // csrf filter applied via Filters.php
    $routes->post('auth/logout', 'AuthController::logout'); // csrf filter applied via Filters.php
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

    $routes->group('pms', ['filter' => 'auth'], function ($routes) {

        // ========= PMS Records (CRUD) =========
        $routes->get('pms-records', 'PMS\PmsRecordsController::index', ['filter' => 'permission:pms.record.view']);
        $routes->post('pms-records', 'PMS\PmsRecordsController::create', ['filter' => 'permission:pms.record.create']);
        $routes->put('pms-records/(:segment)', 'PMS\PmsRecordsController::update/$1', ['filter' => 'permission:pms.record.update']);
        $routes->delete('pms-records/(:segment)', 'PMS\PmsRecordsController::delete/$1', ['filter' => 'permission:pms.record.delete']);

        $routes->get('pms-records/(:segment)/answers', 'PMS\PmsRecordsController::answers/$1', ['filter' => 'permission:pms.record.view']);
        $routes->put('pms-records/(:segment)/answers', 'PMS\PmsRecordsController::updateAnswers/$1', ['filter' => 'permission:pms.record.update']);
        $routes->delete('pms-records/(:segment)/answers', 'PMS\PmsRecordsController::clearAnswers/$1', ['filter' => 'permission:pms.record.delete']);

        // ========= PMS Schedule (CRUD) =========
        $routes->get('pms-schedules', 'PMS\PmsSchedulesController::index', ['filter' => 'permission:pms.schedule.view']);
        $routes->post('pms-schedules', 'PMS\PmsSchedulesController::create', ['filter' => 'permission:pms.schedule.create']);
        $routes->post('pms-schedules/(:segment)', 'PMS\PmsSchedulesController::update/$1', ['filter' => 'permission:pms.schedule.update']);
        $routes->delete('pms-schedules/(:segment)', 'PMS\PmsSchedulesController::delete/$1', ['filter' => 'permission:pms.schedule.delete']);

        $routes->get('pms-schedules/dropdown', 'PMS\PmsSchedulesController::dropdown');
        

        // ========= PMS Question (CRUD) =========
        $routes->get('pms-questions', 'PMS\PmsQuestionsController::index', ['filter' => 'permission:pms.question.view']);
        $routes->post('pms-questions', 'PMS\PmsQuestionsController::create', ['filter' => 'permission:pms.question.create']);
        $routes->put('pms-questions/(:segment)', 'PMS\PmsQuestionsController::update/$1', ['filter' => 'permission:pms.question.update']);
        $routes->delete('pms-questions/(:segment)', 'PMS\PmsQuestionsController::delete/$1', ['filter' => 'permission:pms.question.delete']);
        $routes->get('pms-questions/active', 'PMS\PmsQuestionsController::active');
    });

    $routes->group('report', ['filter' => 'auth'], function ($routes) {
        $routes->get('pms-records', 'Reports\PMSReportController::pms_record_view', ['filter' => 'permission:pms.record.view.report']);

    });

    $routes->group('assets', ['filter' => 'auth'], function ($routes) {

        // ========= INVENTORY (CRUD) =========
        $routes->get('inventory', 'Assets\InventoryController::index', ['filter' => 'permission:inventory.view']);
        $routes->post('inventory', 'Assets\InventoryController::create', ['filter' => 'permission:inventory.create']);
        $routes->put('inventory/(:segment)', 'Assets\InventoryController::update/$1', ['filter' => 'permission:inventory.update']);
        $routes->delete('inventory/(:segment)', 'Assets\InventoryController::delete/$1', ['filter' => 'permission:inventory.delete']);
        $routes->get('inventory/dropdown', 'Assets\InventoryController::dropdown');
        $routes->get('inventory/dropdown-available-pms/(:segment)', 'Assets\InventoryController::dropdownAvailableForPmsSchedule/$1');
        $routes->post('inventory/(:segment)/sync-location-from-pms', 'Assets\InventoryController::syncPms/$1', ['filter' => 'permission:inventory.update']);
        $routes->put('inventory/(:segment)/location', 'Assets\InventoryController::updateLocation/$1', ['filter' => 'permission:inventory.update']);

        // ========= INVENTORY - SOFTWARE (CRUD) =========
        $routes->get('inventory/(:segment)/software', 'Assets\InventorySoftwareController::index/$1', ['filter' => 'permission:inventory.create']);
        $routes->post('inventory/(:segment)/software', 'Assets\InventorySoftwareController::create/$1', ['filter' => 'permission:inventory.create']);
        $routes->put('inventory/(:segment)/software/(:segment)', 'Assets\InventorySoftwareController::update/$1/$2', ['filter' => 'permission:inventory.update']);
        $routes->delete('inventory/(:segment)/software/(:segment)', 'Assets\InventorySoftwareController::delete/$1/$2', ['filter' => 'permission:inventory.delete']);

        // ========= SOFTWARE (CRUD) =========
        $routes->get('softwares', 'Assets\SoftwaresController::index', ['filter' => 'permission:softwares.view']);
        $routes->get('softwares/dropdown', 'Assets\SoftwaresController::dropdown');
        $routes->post('softwares', 'Assets\SoftwaresController::create', ['filter' => 'permission:softwares.create']);
        $routes->put('softwares/(:segment)', 'Assets\SoftwaresController::update/$1', ['filter' => 'permission:softwares.update']);
        $routes->delete('softwares/(:segment)', 'Assets\SoftwaresController::delete/$1', ['filter' => 'permission:softwares.delete']);

        // ========= DEVICE TYPES (CRUD) =========
        $routes->get('device-types', 'Assets\DeviceTypesController::index', ['filter' => 'permission:device_types.view']);
        $routes->post('device-types', 'Assets\DeviceTypesController::create', ['filter' => 'permission:device_types.create']);
        $routes->put('device-types/(:segment)', 'Assets\DeviceTypesController::update/$1', ['filter' => 'permission:device_types.update']);
        $routes->delete('device-types/(:segment)', 'Assets\DeviceTypesController::delete/$1', ['filter' => 'permission:device_types.delete']);
        $routes->get('device-types/dropdown', 'Assets\DeviceTypesController::dropdown');

        // ========= SOFTWARE TYPES (CRUD) =========
        $routes->get('software-types', 'Assets\SoftwareTypesController::index', ['filter' => 'permission:software_types.view']);
        $routes->post('software-types', 'Assets\SoftwareTypesController::create', ['filter' => 'permission:software_types.create']);
        $routes->put('software-types/(:segment)', 'Assets\SoftwareTypesController::update/$1', ['filter' => 'permission:software_types.update']);
        $routes->delete('software-types/(:segment)', 'Assets\SoftwareTypesController::delete/$1', ['filter' => 'permission:software_types.delete']);
        $routes->get('software-types/dropdown', 'Assets\SoftwareTypesController::dropdown');

        // ========= LICENSE TYPES (CRUD) =========
        $routes->get('license-types', 'Assets\LicenseTypesController::index', ['filter' => 'permission:license_types.view']);
        $routes->post('license-types', 'Assets\LicenseTypesController::create', ['filter' => 'permission:license_types.create']);
        $routes->put('license-types/(:segment)', 'Assets\LicenseTypesController::update/$1', ['filter' => 'permission:license_types.update']);
        $routes->delete('license-types/(:segment)', 'Assets\LicenseTypesController::delete/$1', ['filter' => 'permission:license_types.delete']);
        $routes->get('license-types/dropdown', 'Assets\LicenseTypesController::dropdown');

    });

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

        $routes->group('org', ['filter' => 'auth'], function ($routes) {
            $routes->get('sections', 'Admin\Organization\SectionsController::index', ['filter' => 'permission:sections.view']);
            $routes->get('sections/dropdown', 'Admin\Organization\SectionsController::dropdown');
            $routes->post('sections', 'Admin\Organization\SectionsController::create', ['filter' => 'permission:sections.create']);
            $routes->put('sections/(:any)', 'Admin\Organization\SectionsController::update/$1', ['filter' => 'permission:sections.update']);
            $routes->delete('sections/(:any)', 'Admin\Organization\SectionsController::delete/$1', ['filter' => 'permission:sections.delete']);

            $routes->get('divisions', 'Admin\Organization\DivisionsController::index', ['filter' => 'permission:divisions.view']);
            $routes->get('divisions/dropdown', 'Admin\Organization\DivisionsController::dropdown');
            $routes->post('divisions', 'Admin\Organization\DivisionsController::create', ['filter' => 'permission:divisions.create']);
            $routes->put('divisions/(:any)', 'Admin\Organization\DivisionsController::update/$1', ['filter' => 'permission:divisions.update']);
            $routes->delete('divisions/(:any)', 'Admin\Organization\DivisionsController::delete/$1', ['filter' => 'permission:divisions.delete']);

            $routes->get('buildings', 'Admin\Organization\BuildingsController::index', ['filter' => 'permission:buildings.view']);
            $routes->get('buildings/dropdown', 'Admin\Organization\BuildingsController::dropdown');
            $routes->post('buildings', 'Admin\Organization\BuildingsController::create', ['filter' => 'permission:buildings.create']);
            $routes->put('buildings/(:any)', 'Admin\Organization\BuildingsController::update/$1', ['filter' => 'permission:buildings.update']);
            $routes->delete('buildings/(:any)', 'Admin\Organization\BuildingsController::delete/$1', ['filter' => 'permission:buildings.delete']);
        });

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
$routes->group('viewer', ['namespace' => 'App\Controllers\Api'], function ($routes) {
    $routes->group('pms', ['filter' => 'auth'], function ($routes) {
        $routes->get('pms-schedules/(:segment)/view-attachment', 'PMS\PmsSchedulesController::view/$1', ['filter' => 'permission:pms.schedule.view.attachment']);
        $routes->get('pms-records/(:segment)/view-pdf', 'PMS\PmsRecordPdf::index/$1');
        $routes->get('pms-records/bulk', 'PMS\PmsRecordPdf::bulk_pdf');
    });
    $routes->group('report', ['filter' => 'auth'], function ($routes) {
        $routes->get('pms-records', 'Reports\PDF\PMSReport::pdf', ['filter' => 'permission:pms.record.view.report']);
    });
});


$routes->get('/', 'Home::index');
