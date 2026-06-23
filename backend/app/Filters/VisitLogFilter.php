<?php

namespace App\Filters;

use App\Models\VisitLogModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class VisitLogFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!($request instanceof IncomingRequest)) {
            return null;
        }

        $browserId = $request->getCookie('rc_vid');
        if (!$browserId || !preg_match('/^[a-f0-9]{32}$/', $browserId)) {
            $browserId = bin2hex(random_bytes(16));
        }

        $ip        = $request->getIPAddress();
        $userAgent = substr($request->getHeaderLine('User-Agent'), 0, 500) ?: null;
        $referer   = substr((string) $request->getServer('HTTP_REFERER'), 0, 500) ?: null;

        (new VisitLogModel())->recordVisit($ip, $browserId, $userAgent, $referer);

        Services::response()->setCookie('rc_vid', $browserId, 60 * 60 * 24 * 365);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
