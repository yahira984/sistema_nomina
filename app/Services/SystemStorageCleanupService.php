<?php

namespace App\Services;

use App\Models\SystemOperation;
use Illuminate\Support\Facades\Storage;

class SystemStorageCleanupService
{
    public function cleanupExports(int $keepDays = 14): int
    {
        $operations = SystemOperation::query()
            ->whereNotNull('result_path')
            ->where('finished_at', '<', now()->subDays(max(1, $keepDays)))
            ->get();
        $deleted = 0;

        foreach ($operations as $operation) {
            $path = (string) $operation->result_path;
            if ($path !== '' && str_starts_with($path, 'exports/')) {
                Storage::disk('local')->deleteDirectory(dirname($path));
                $operation->forceFill(['result_path' => null])->save();
                $deleted++;
            }
        }

        return $deleted;
    }
}
