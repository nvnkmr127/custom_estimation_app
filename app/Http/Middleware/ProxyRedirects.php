<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProxyRedirects
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof \Symfony\Component\HttpFoundation\RedirectResponse) {
            $location = $response->headers->get('Location');
            if ($location) {
                // Replace 127.0.0.1:8000 with the proxy host
                $proxyHost = 'run-agent-69d668482069862ce054766e-mnq5kjbm.remote-agent.svc.cluster.local';
                $location = str_replace('http://127.0.0.1:8000', 'http://' . $proxyHost, $location);
                
                // Append _port=8000
                if (strpos($location, '?') !== false) {
                    $location .= '&_port=8000';
                } else {
                    $location .= '?_port=8000';
                }
                
                $response->headers->set('Location', $location);
            }
        }

        return $response;
    }
}
