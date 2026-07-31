<?php

namespace App\Services;

use App\Models\ReceiptGeneration;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Schema;

class ReceiptHistoryService
{
    public function record(
        string $type,
        CarbonInterface $start,
        CarbonInterface $end,
        string $fileName,
        int $count = 1,
        ?int $employeeId = null,
        ?int $payrollId = null,
        ?int $userId = null,
        ?string $content = null,
        array $metadata = []
    ): ?ReceiptGeneration {
        if (!Schema::hasTable('receipt_generations')) {
            return null;
        }

        return ReceiptGeneration::create([
            'user_id' => $userId ?? auth()->id(),
            'empleado_id' => $employeeId,
            'nomina_id' => $payrollId,
            'type' => $type,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'file_name' => $fileName,
            'receipt_count' => max(1, $count),
            'checksum' => $content === null ? null : hash('sha256', $content),
            'metadata' => $metadata,
        ]);
    }
}
