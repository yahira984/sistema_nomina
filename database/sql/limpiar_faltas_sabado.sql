-- Elimina faltas registradas en sabado.
-- Los sabados no se consideran falta: solo cuentan como tiempo extra si hubo asistencia.

SELECT
    a.id,
    e.numero_empleado,
    e.numero_empleado_baja,
    e.nombre_completo,
    a.fecha,
    a.tipo_asistencia
FROM asistencias a
JOIN empleados e ON e.id = a.empleado_id
WHERE a.tipo_asistencia = 'Falta'
  AND DAYOFWEEK(a.fecha) = 7
ORDER BY a.fecha, CAST(TRIM(LEADING '0' FROM COALESCE(e.numero_empleado, e.numero_empleado_baja, '999999')) AS UNSIGNED), e.nombre_completo;

DELETE a
FROM asistencias a
WHERE a.tipo_asistencia = 'Falta'
  AND DAYOFWEEK(a.fecha) = 7;
