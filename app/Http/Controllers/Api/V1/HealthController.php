<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Health Check Controller
 * 
 * Provides comprehensive health status endpoints for monitoring
 * and load balancer health checks.
 */
class HealthController extends Controller
{
    /**
     * Basic health check endpoint.
     * 
     * Returns simple status for load balancer checks.
     * Should be fast and not hit external dependencies.
     *
     * @return JsonResponse
     */
    public function basic(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
        ]);
    }

    /**
     * Comprehensive health check endpoint.
     * 
     * Checks all critical dependencies (DB, Cache) and returns
     * detailed status for monitoring systems.
     *
     * @return JsonResponse
     */
    public function detailed(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        $allHealthy = collect($checks)->every(fn ($check) => $check['status'] === 'healthy');

        return response()->json([
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
            'checks' => $checks,
            'memory' => [
                'usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ],
        ], $allHealthy ? 200 : 503);
    }

    /**
     * Check database connectivity.
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'latency_ms' => $latency,
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => app()->environment('production') 
                    ? 'Database connection failed' 
                    : $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache connectivity.
     */
    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            $key = 'health_check_' . now()->timestamp;
            
            Cache::put($key, 'test', 10);
            $retrieved = Cache::get($key);
            Cache::forget($key);
            
            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved !== 'test') {
                return [
                    'status' => 'unhealthy',
                    'error' => 'Cache read/write mismatch',
                ];
            }

            return [
                'status' => 'healthy',
                'latency_ms' => $latency,
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => app()->environment('production')
                    ? 'Cache connection failed'
                    : $e->getMessage(),
            ];
        }
    }
}
