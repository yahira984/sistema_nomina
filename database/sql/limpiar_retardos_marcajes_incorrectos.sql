-- Limpieza de asistencias importadas con marca de salida tomada como entrada.
-- No borra registros: conserva la posible salida, limpia minutos/horas y deja la entrada vacia.

START TRANSACTION;

SELECT
    a.id,
    e.numero_empleado,
    e.nombre_completo,
    a.fecha,
    a.hora_entrada,
    a.hora_salida,
    a.minutos_tarde,
    a.horas_trabajadas,
    a.horas_extra
FROM asistencias a
JOIN empleados e ON e.id = a.empleado_id
WHERE a.tipo_asistencia = 'Normal'
  AND (
    (a.hora_entrada >= '16:00:00' AND a.minutos_tarde >= 240)
    OR (
      a.hora_entrada IS NOT NULL
      AND a.hora_salida IS NOT NULL
      AND a.hora_salida <= a.hora_entrada
    )
  )
ORDER BY a.fecha, e.numero_empleado;

UPDATE asistencias
SET
    hora_entrada = NULL,
    minutos_tarde = 0,
    horas_trabajadas = 0,
    horas_extra = 0,
    updated_at = NOW()
WHERE tipo_asistencia = 'Normal'
  AND (
    (hora_entrada >= '16:00:00' AND minutos_tarde >= 240)
    OR (
      hora_entrada IS NOT NULL
      AND hora_salida IS NOT NULL
      AND hora_salida <= hora_entrada
    )
  );

SELECT ROW_COUNT() AS registros_limpiados;

COMMIT;
