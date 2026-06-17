<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('logged_in') || !$session->get('uid')) {
            return service('response')
                ->setJSON(['message' => 'Unauthorized'])
                ->setStatusCode(401);
        }

        // If you want controllers to access uid easily:
        // $request->setGlobal('request', ['auth' => ['uid' => $session->get('uid')]]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // no-op
    }
}