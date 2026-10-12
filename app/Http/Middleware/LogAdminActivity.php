<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencatat aksi admin ke activity_logs (spec 12) berdasarkan nama route.
 * Dipasang di grup admin; tidak mencatat GET baca murni.
 */
final class LogAdminActivity
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if ($response->isSuccessful() && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $route = $request->route();
            $action = $route?->getName() ?? $request->method() . ' ' . $request->path();

            $this->logger->log(
                userId: $request->user()?->id,
                action: (string) $action,
                subjectType: $route?->parameterNames()[0] !== null
                    ? (string) $route->parameterNames()[0]
                    : null,
                subjectId: $route?->parameters()[($route->parameterNames()[0] ?? '')] instanceof \BackedEnum
                    ? null
                    : (is_scalar($route?->parameters()[($route->parameterNames()[0] ?? '')] ?? null)
                        ? (int) $route->parameters()[($route->parameterNames()[0] ?? '')]
                        : null),
                meta: [
                    'method' => $request->method(),
                    'path' => '/' . $request->path(),
                ]
            );
        }

        return $response;
    }
}
