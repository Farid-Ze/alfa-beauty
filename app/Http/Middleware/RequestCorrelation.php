<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RequestCorrelation
{
    public function handle(Request $request, Closure $next)
    {
        $requestId = $request->headers->get('X-Request-Id')
            ?: $request->headers->get('Request-Id')
            ?: (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);

        Log::withContext([
            'request_id' => $requestId,
            'method' => $request->getMethod(),
            'path' => $request->path(),
        ]);

        $response = $next($request);

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
