<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

class AuditHttpRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldAudit($request, $response)) {
            AuditLog::record('http.request', null, [
                'description' => $this->description($request),
                'metadata' => [
                    'route' => $request->route()?->getName(),
                    'status' => $response->getStatusCode(),
                    'input' => $this->safeInput($request),
                ],
            ]);
        }

        return $response;
    }

    private function shouldAudit(Request $request, Response $response): bool
    {
        if (!$request->user() || $response->getStatusCode() >= 500) {
            return false;
        }

        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            return true;
        }

        $route = (string) $request->route()?->getName();

        foreach (['exportar', 'descargar', 'generar', 'recibos', 'excel', 'reporte', 'pdf'] as $keyword) {
            if (str_contains($route, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function description(Request $request): string
    {
        $route = $request->route()?->getName() ?: $request->path();

        return "{$request->method()} {$route}";
    }

    private function safeInput(Request $request): array
    {
        $input = Arr::except($request->except(array_keys($request->files->all())), [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            '_token',
            '_method',
        ]);

        foreach ($request->files->all() as $key => $file) {
            $input[$key] = is_array($file)
                ? '[archivos]'
                : [
                    'name' => $file?->getClientOriginalName(),
                    'size' => $file?->getSize(),
                ];
        }

        return $input;
    }
}
