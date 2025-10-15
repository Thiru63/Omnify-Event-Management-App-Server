<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class RouteServiceProvider extends ServiceProvider
{
    /*
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /*
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();
            
            return [
                // 60 requests per minute per user/IP
                Limit::perMinute(60)->by($key)->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many requests. Please try again in a minute.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], Response::HTTP_TOO_MANY_REQUESTS, $headers);
                }),
                
                // 10 requests per second burst protection
                Limit::perSecond(10)->by($key)->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many rapid requests. Please slow down.',
                        'retry_after' => $headers['Retry-After'] ?? 1,
                    ], Response::HTTP_TOO_MANY_REQUESTS, $headers);
                }),
            ];
        });

        // Specific rate limit for event registration (prevent spam)
        RateLimiter::for('event-registration', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function (Request $request, array $headers) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many registration attempts. Please try again later.',
                    'retry_after' => $headers['Retry-After'] ?? 60,
                ], Response::HTTP_TOO_MANY_REQUESTS, $headers);
            });
        });
    }
}