<?php

namespace App\Support;

class EmployeeDocumentCatalog
{
    public const TYPES = [
        'solicitud_empleo' => 'Solicitud de empleo firmada',
        'ine' => 'Identificación oficial INE',
        'acta_nacimiento' => 'Acta de nacimiento',
        'constancia_fiscal' => 'Constancia de situación fiscal actualizada',
        'cartilla_militar' => 'Cartilla de servicio militar',
        'comprobante_domicilio' => 'Comprobante de domicilio vigente',
        'carta_recomendacion' => 'Carta de recomendación',
        'no_antecedentes' => 'Constancia de no antecedentes penales',
    ];

    public static function label(string $type): ?string
    {
        return self::TYPES[$type] ?? null;
    }

    public static function options(): array
    {
        return collect(self::TYPES)
            ->map(fn (string $label, string $key) => compact('key', 'label'))
            ->values()
            ->all();
    }
}
