<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\EmployeeDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class EmployeeDocumentStorageService
{
    private const MAX_IMAGE_EDGE = 1800;
    private const MAX_IMAGE_PIXELS = 25000000;
    private const WEBP_QUALITY = 78;

    public function store(Empleado $employee, string $type, UploadedFile $file, ?int $userId): EmployeeDocument
    {
        $directory = "employees/{$employee->id}/documents";
        $originalSize = $file->getSize() ?: 0;
        [$contents, $extension, $mime] = $this->optimize($file);
        $storedName = $type.'_'.Str::uuid().'.'.$extension;
        $path = $directory.'/'.$storedName;

        if (! Storage::disk('local')->put($path, $contents)) {
            throw new RuntimeException('No fue posible guardar el documento.');
        }

        $previous = EmployeeDocument::where('empleado_id', $employee->id)
            ->where('document_type', $type)
            ->first();

        try {
            $document = EmployeeDocument::updateOrCreate(
                ['empleado_id' => $employee->id, 'document_type' => $type],
                [
                    'uploaded_by' => $userId,
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name' => $storedName,
                    'path' => $path,
                    'mime_type' => $mime,
                    'extension' => $extension,
                    'original_size_bytes' => $originalSize ?: strlen($contents),
                    'stored_size_bytes' => strlen($contents),
                    'checksum' => hash('sha256', $contents),
                ]
            );
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        if ($previous && $previous->path !== $path) {
            Storage::disk('local')->delete($previous->path);
        }

        return $document->fresh('uploadedBy:id,name');
    }

    public function delete(EmployeeDocument $document): void
    {
        Storage::disk('local')->delete($document->path);
        $document->delete();
    }

    private function optimize(UploadedFile $file): array
    {
        $mime = (string) $file->getMimeType();
        $path = $file->getRealPath();

        if ($mime === 'application/pdf') {
            $contents = file_get_contents($path);
            if ($contents === false) {
                throw new RuntimeException('No fue posible leer el PDF seleccionado.');
            }
            return [$contents, 'pdf', 'application/pdf'];
        }

        $dimensions = @getimagesize($path);
        if (!$dimensions || empty($dimensions[0]) || empty($dimensions[1])) {
            throw new RuntimeException('La imagen no es válida o está dañada.');
        }
        [$width, $height] = $dimensions;
        if ($width * $height > self::MAX_IMAGE_PIXELS) {
            throw new RuntimeException('La imagen tiene una resolución excesiva. Escanéala a 300 DPI o reduce su tamaño antes de subirla.');
        }

        $previousMemoryLimit = ini_get('memory_limit');
        $this->raiseMemoryLimitForImage();
        $source = null;
        $target = null;

        try {
            $source = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($path),
                'image/png' => @imagecreatefrompng($path),
                'image/webp' => @imagecreatefromwebp($path),
                default => false,
            };
            if (!$source) {
                throw new RuntimeException('La imagen no es válida o está dañada.');
            }

            $scale = min(1, self::MAX_IMAGE_EDGE / max($width, $height));
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));
            $target = imagecreatetruecolor($targetWidth, $targetHeight);
            if (!$target) {
                throw new RuntimeException('No hay memoria suficiente para optimizar esta imagen.');
            }
            $background = imagecolorallocate($target, 255, 255, 255);
            imagefill($target, 0, 0, $background);
            imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

            ob_start();
            $encoded = imagewebp($target, null, self::WEBP_QUALITY);
            $optimized = ob_get_clean();
        } finally {
            if ($source) imagedestroy($source);
            if ($target) imagedestroy($target);
            if ($previousMemoryLimit !== false) ini_set('memory_limit', (string) $previousMemoryLimit);
        }

        if (! $encoded || ! is_string($optimized) || $optimized === '') {
            throw new RuntimeException('No fue posible comprimir la imagen.');
        }

        return [$optimized, 'webp', 'image/webp'];
    }

    private function raiseMemoryLimitForImage(): void
    {
        $current = (string) ini_get('memory_limit');
        if ($current === '-1') {
            return;
        }
        $bytes = $this->memoryToBytes($current);
        if ($bytes > 0 && $bytes < 256 * 1024 * 1024) {
            @ini_set('memory_limit', '256M');
        }
    }

    private function memoryToBytes(string $value): int
    {
        $value = trim($value);
        $number = (int) $value;
        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
