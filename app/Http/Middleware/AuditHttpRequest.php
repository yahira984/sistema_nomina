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
        // Uploaded files may be moved by the controller, so capture their safe
        // metadata while PHP's temporary upload still exists.
        $safeInput = $request->files->count() > 0
            ? $this->safeInput($request)
            : null;
        $response = $next($request);

        if ($this->shouldAudit($request, $response)) {
            AuditLog::record('http.request', null, [
                'description' => $this->description($request),
                'metadata' => [
                    'route' => $request->route()?->getName(),
                    'status' => $response->getStatusCode(),
                    'input' => $safeInput ?? $this->safeInput($request),
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
                : $this->safeFileMetadata($file);
        }

        return $input;
    }

    private function safeFileMetadata(mixed $file): array
    {
        try {
            return [
                'name' => $file?->getClientOriginalName(),
                'size' => $file?->getSize(),
            ];
        } catch (\Throwable) {
            return [
                'name' => is_object($file) && method_exists($file, 'getClientOriginalName')
                    ? $file->getClientOriginalName()
                    : null,
                'size' => null,
            ];
        }
    }
}
