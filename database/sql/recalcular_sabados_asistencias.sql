-- Recalcula asistencias normales de sabado.
-- Regla:
-- - Si entra antes de 08:00, cuenta desde 08:00.
-- - Sabado redondea a la hora completa mas cercana.
-- - Empleados 8, 9 y 22 no generan horas extra.
-- - Empleados 14, 76 y 78 no generan retardos.

UPDATE asistencias a
JOIN empleados e ON e.id = a.empleado_id
SET
    a.minutos_tarde = CASE
        WHEN TRIM(LEADING '0' FROM COALESCE(e.numero_empleado, e.numero_empleado_baja, '')) IN ('14', '76', '78') THEN 0
        WHEN a.hora_entrada IS NOT NULL AND TIME(a.hora_entrada) > '08:00:00'
            THEN TIMESTAMPDIFF(MINUTE, CONCAT(a.fecha, ' 08:00:00'), CONCAT(a.fecha, ' ', TIME(a.hora_entrada)))
        ELSE 0
    END,
    a.horas_trabajadas = 0,
    a.horas_extra = CASE
        WHEN TRIM(LEADING '0' FROM COALESCE(e.numero_empleado, e.numero_empleado_baja, '')) IN ('8', '9', '22') THEN 0
        WHEN a.hora_entrada IS NULL OR a.hora_salida IS NULL THEN 0
        ELSE GREATEST(
            0,
            ROUND(
                TIMESTAMPDIFF(
                    MINUTE,
                    CONCAT(a.fecha, ' ', IF(TIME(a.hora_entrada) < '08:00:00', '08:00:00', TIME(a.hora_entrada))),
                    CONCAT(a.fecha, ' ', TIME(a.hora_salida))
                ) / 60
            )
        )
    END,
    a.updated_at = NOW()
WHERE a.tipo_asistencia = 'Normal'
  AND DAYOFWEEK(a.fecha) = 7;

SELECT
    e.numero_empleado,
    e.numero_empleado_baja,
    e.nombre_completo,
    a.fecha,
    a.hora_entrada,
    a.hora_salida,
    a.minutos_tarde,
    a.horas_extra
FROM asistencias a
JOIN empleados e ON e.id = a.empleado_id
WHERE a.tipo_asistencia = 'Normal'
  AND DAYOFWEEK(a.fecha) = 7
ORDER BY a.fecha DESC, CAST(TRIM(LEADING '0' FROM COALESCE(e.numero_empleado, e.numero_empleado_baja, '999999')) AS UNSIGNED), e.nombre_completo;
