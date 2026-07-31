<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\Nomina;
use App\Models\PaymentReconciliation;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class PaymentReconciliationService
{
    public function reconcile(string $path, Carbon $start, Carbon $end, string $sourceName, ?int $userId): PaymentReconciliation
    {
        $rows = $this->readRows($path);
        $payrolls = Nomina::query()
            ->whereDate('fecha_inicio', $start)
            ->whereDate('fecha_fin', $end)
            ->with('empleado:id,numero_empleado,numero_cuenta,nombre_completo')
            ->get()
            ->keyBy('empleado_id');
        $employeesByNumber = $payrolls->pluck('empleado')->filter()->keyBy(fn (Empleado $employee) => $this->key($employee->numero_empleado));
        $employeesByAccount = $payrolls->pluck('empleado')->filter()->filter->numero_cuenta->keyBy(fn (Empleado $employee) => $this->key($employee->numero_cuenta));
        $results = collect();

        foreach ($rows as $row) {
            $employee = $employeesByNumber->get($this->key($row['employee_number'] ?? ''))
                ?? $employeesByAccount->get($this->key($row['account'] ?? ''));
            $amount = $this->amount($row['amount'] ?? 0);

            if (!$employee || !$payrolls->has($employee->id)) {
                $results->push([
                    'status' => 'unmatched',
                    'reference' => $row['employee_number'] ?: $row['account'],
                    'name' => $row['name'] ?? 'Sin identificar',
                    'statement_amount' => $amount,
                    'payroll_amount' => null,
                    'difference' => null,
                ]);
                continue;
            }

            $payroll = $payrolls->get($employee->id);
            $expected = round((float) $payroll->pago_neto, 2);
            $difference = round($amount - $expected, 2);
            $results->push([
                'status' => abs($difference) < 0.01 ? 'matched' : 'difference',
                'empleado_id' => $employee->id,
                'nomina_id' => $payroll->id,
                'reference' => $employee->numero_empleado,
                'name' => $employee->nombre_completo,
                'statement_amount' => $amount,
                'payroll_amount' => $expected,
                'difference' => $difference,
            ]);
        }

        return PaymentReconciliation::create([
            'user_id' => $userId,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'source_name' => $sourceName,
            'checksum' => hash_file('sha256', $path),
            'status' => 'completed',
            'matched_count' => $results->where('status', 'matched')->count(),
            'difference_count' => $results->where('status', 'difference')->count(),
            'unmatched_count' => $results->where('status', 'unmatched')->count(),
            'results' => $results->values()->all(),
        ]);
    }

    private function readRows(string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
        if (count($sheet) < 2) {
            throw new RuntimeException('El archivo no contiene movimientos para conciliar.');
        }

        $headers = collect(array_shift($sheet))->map(fn ($value) => $this->header($value))->all();
        $mapped = collect($sheet)->map(function (array $values) use ($headers) {
            $row = [];
            foreach ($headers as $index => $header) {
                if ($header !== null) {
                    $row[$header] = $values[$index] ?? null;
                }
            }
            return $row;
        })->filter(fn ($row) => !empty($row['employee_number']) || !empty($row['account']));

        if ($mapped->isEmpty() || !$mapped->contains(fn ($row) => array_key_exists('amount', $row))) {
            throw new RuntimeException('Usa columnas Número de empleado o Cuenta, y Monto/Importe.');
        }

        return $mapped->values()->all();
    }

    private function header(mixed $value): ?string
    {
        $header = Str::of((string) $value)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->toString();

        return match (true) {
            str_contains($header, 'empleado'), str_contains($header, 'numero') && str_contains($header, 'trabajador') => 'employee_number',
            str_contains($header, 'cuenta'), str_contains($header, 'clabe') => 'account',
            str_contains($header, 'monto'), str_contains($header, 'importe'), str_contains($header, 'deposito') => 'amount',
            str_contains($header, 'nombre') => 'name',
            default => null,
        };
    }

    private function key(mixed $value): string
    {
        return ltrim(preg_replace('/[^0-9A-Za-z]/', '', (string) $value), '0');
    }

    private function amount(mixed $value): float
    {
        $normalized = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) $value));

        return round((float) $normalized, 2);
    }
}
