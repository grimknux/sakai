<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // must be logged in (session auth)
        $uid = session('uid');
        if (!$uid) {
            return service('response')->setJSON(['message' => 'Unauthorized'])->setStatusCode(401);
        }

        // Usage: ['filter' => 'permission:users.view']
        $permission = $arguments[0] ?? null;
        if (!$permission) {
            return service('response')->setJSON(['message' => 'Permission not specified'])->setStatusCode(500);
        }

        $permService = service('permission'); // we’ll register it below
        if (!$permService->userHasPermission((int)$uid, $permission)) {
            return service('response')->setJSON(['message' => 'Forbidden'])->setStatusCode(403);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}