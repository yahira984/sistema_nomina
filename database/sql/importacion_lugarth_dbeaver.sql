SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Importacion para DBeaver. Ejecuta TODO el archivo como script.
-- Asistencias: 2026-05-28 al 2026-06-03.
-- Este script actualiza/crea empleados y reemplaza asistencias de esa semana.

ALTER TABLE empleados MODIFY numero_cuenta VARCHAR(50) NULL;

UPDATE empleados e
SET
    numero_empleado = NULL,
    numero_empleado_baja = '1',
    nombre_completo = 'RAMIREZ PALMA FRANCISCO',
    puesto = NULL,
    fecha_ingreso = '2025-01-28',
    fecha_baja = '2026-06-03',
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-01-28' IS NOT NULL AND '2026-06-03' IS NOT NULL THEN DATEDIFF('2026-06-03', '2025-01-28') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -10,
    forma_pago = 'Efectivo',
    es_estudiante = 0,
    sueldo_semanal = 1000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 159.42,
    saldo_prestamo = 0.00,
    banco = NULL,
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 0,
    updated_at = NOW()
WHERE (e.numero_empleado_baja = '1');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT NULL, '1', 'RAMIREZ PALMA FRANCISCO', NULL, '2025-01-28', '2026-06-03', CASE WHEN 0 > 0 THEN 0 WHEN '2025-01-28' IS NOT NULL AND '2026-06-03' IS NOT NULL THEN DATEDIFF('2026-06-03', '2025-01-28') + 1 ELSE 0 END, NULL, -10, 'Efectivo', 0, 1000.00, 0.00, 0, 49.00, 57.63, 159.42, 0.00, NULL, NULL, NULL, NULL, NULL, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado_baja = '1'));

UPDATE empleados e
SET
    numero_empleado = '2',
    numero_empleado_baja = NULL,
    nombre_completo = 'ALCANTARA PATIÑO ANGEL',
    puesto = NULL,
    fecha_ingreso = '2005-08-15',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2005-08-15' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2005-08-15') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -1,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2200.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 529.48,
    saldo_prestamo = 60.00,
    banco = 'AZTECA',
    numero_cuenta = '4027 6653 0604 9561',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '2');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '2', NULL, 'ALCANTARA PATIÑO ANGEL', NULL, '2005-08-15', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2005-08-15' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2005-08-15') + 1 ELSE 0 END, NULL, -1, 'Deposito', 0, 2200.00, 0.00, 60.00, 49.00, 57.63, 529.48, 60.00, 'AZTECA', '4027 6653 0604 9561', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '2'));

UPDATE empleados e
SET
    numero_empleado = '3',
    numero_empleado_baja = NULL,
    nombre_completo = 'CARBAJAL GARCIA VICTOR EDUARDO',
    puesto = NULL,
    fecha_ingreso = '2025-04-28',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-04-28' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-04-28') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -2,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1150.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'COPPEL',
    numero_cuenta = '10479013620',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '3');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '3', NULL, 'CARBAJAL GARCIA VICTOR EDUARDO', NULL, '2025-04-28', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-04-28' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-04-28') + 1 ELSE 0 END, NULL, -2, 'Deposito', 0, 1150.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, 'COPPEL', '10479013620', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '3'));

UPDATE empleados e
SET
    numero_empleado = '4',
    numero_empleado_baja = NULL,
    nombre_completo = 'GARCIA GUZMAN PERFECTO MIGUEL',
    puesto = NULL,
    fecha_ingreso = '2012-03-17',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2012-03-17' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2012-03-17') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -7,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1200.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'HSBC',
    numero_cuenta = '4213 1660 4232 9247',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '4');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '4', NULL, 'GARCIA GUZMAN PERFECTO MIGUEL', NULL, '2012-03-17', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2012-03-17' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2012-03-17') + 1 ELSE 0 END, NULL, -7, 'Deposito', 0, 1200.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, 'HSBC', '4213 1660 4232 9247', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '4'));

UPDATE empleados e
SET
    numero_empleado = '5',
    numero_empleado_baja = NULL,
    nombre_completo = 'FLORES RAMOS JAVIER',
    puesto = NULL,
    fecha_ingreso = '2014-06-30',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2014-06-30' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2014-06-30') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -24,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1300.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'SANTANDER',
    numero_cuenta = '5579 1004 3838 6318',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '5');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '5', NULL, 'FLORES RAMOS JAVIER', NULL, '2014-06-30', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2014-06-30' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2014-06-30') + 1 ELSE 0 END, NULL, -24, 'Deposito', 0, 1300.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'SANTANDER', '5579 1004 3838 6318', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '5'));

UPDATE empleados e
SET
    numero_empleado = '6',
    numero_empleado_baja = NULL,
    nombre_completo = 'NAVARRO LEON JOSE VICTOR',
    puesto = NULL,
    fecha_ingreso = '2003-03-10',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2003-03-10' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2003-03-10') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -20,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'COPPEL',
    numero_cuenta = '4169 1608 2038 3694',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '6');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '6', NULL, 'NAVARRO LEON JOSE VICTOR', NULL, '2003-03-10', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2003-03-10' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2003-03-10') + 1 ELSE 0 END, NULL, -20, 'Deposito', 0, 2000.00, 0.00, 60.00, 0.00, 0.00, 0.00, 60.00, 'COPPEL', '4169 1608 2038 3694', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '6'));

UPDATE empleados e
SET
    numero_empleado = '7',
    numero_empleado_baja = NULL,
    nombre_completo = 'MEDRANO SANDIA ANGEL',
    puesto = NULL,
    fecha_ingreso = '2023-12-11',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2023-12-11' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2023-12-11') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -2,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1450.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'SANTANDER',
    numero_cuenta = '56903788578',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '7');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '7', NULL, 'MEDRANO SANDIA ANGEL', NULL, '2023-12-11', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2023-12-11' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2023-12-11') + 1 ELSE 0 END, NULL, -2, 'Deposito', 0, 1450.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'SANTANDER', '56903788578', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '7'));

UPDATE empleados e
SET
    numero_empleado = '8',
    numero_empleado_baja = NULL,
    nombre_completo = 'GUZMAN MONTIJO EFRAIN ARTURO',
    puesto = NULL,
    fecha_ingreso = '2024-06-03',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2024-06-03' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-06-03') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1300.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 622.21,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'COPPEL',
    numero_cuenta = '4169 1608 5784 8593',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '8');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '8', NULL, 'GUZMAN MONTIJO EFRAIN ARTURO', NULL, '2024-06-03', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2024-06-03' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-06-03') + 1 ELSE 0 END, NULL, 0, 'Deposito', 0, 1300.00, 0.00, 0, 0.00, 622.21, 0.00, 0.00, 'COPPEL', '4169 1608 5784 8593', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '8'));

UPDATE empleados e
SET
    numero_empleado = '9',
    numero_empleado_baja = NULL,
    nombre_completo = 'GARCIA CISNEROS LIZBETH GUADALUPE',
    puesto = NULL,
    fecha_ingreso = '2026-02-08',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2026-02-08' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2026-02-08') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 1,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1500.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BANAMEX',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '9');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '9', NULL, 'GARCIA CISNEROS LIZBETH GUADALUPE', NULL, '2026-02-08', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2026-02-08' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2026-02-08') + 1 ELSE 0 END, NULL, 1, 'Deposito', 0, 1500.00, 0.00, 0, 49.00, 57.63, 0.00, 0.00, 'BANAMEX', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '9'));

UPDATE empleados e
SET
    numero_empleado = '10',
    numero_empleado_baja = NULL,
    nombre_completo = 'ORDAZ RODRIGUEZ ALEXIS',
    puesto = NULL,
    fecha_ingreso = '2019-09-02',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2019-09-02' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2019-09-02') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -2,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2100.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'SANTANDER',
    numero_cuenta = '56903788595',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '10');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '10', NULL, 'ORDAZ RODRIGUEZ ALEXIS', NULL, '2019-09-02', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2019-09-02' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2019-09-02') + 1 ELSE 0 END, NULL, -2, 'Deposito', 0, 2100.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'SANTANDER', '56903788595', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '10'));

UPDATE empleados e
SET
    numero_empleado = '11',
    numero_empleado_baja = NULL,
    nombre_completo = 'APARICIO SANTIAGO SANTIAGO',
    puesto = NULL,
    fecha_ingreso = '2025-06-20',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-06-20' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-06-20') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 10,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1230.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'COPPEL',
    numero_cuenta = '4169 1614 9337 2998',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '11');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '11', NULL, 'APARICIO SANTIAGO SANTIAGO', NULL, '2025-06-20', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-06-20' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-06-20') + 1 ELSE 0 END, NULL, 10, 'Deposito', 0, 1230.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, 'COPPEL', '4169 1614 9337 2998', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '11'));

UPDATE empleados e
SET
    numero_empleado = '12',
    numero_empleado_baja = NULL,
    nombre_completo = 'ORTIZ GARCIA REMEDIOS',
    puesto = NULL,
    fecha_ingreso = '2013-01-09',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2013-01-09' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2013-01-09') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -23,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1600.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'SANTANDER',
    numero_cuenta = '56901746604',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '12');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '12', NULL, 'ORTIZ GARCIA REMEDIOS', NULL, '2013-01-09', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2013-01-09' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2013-01-09') + 1 ELSE 0 END, NULL, -23, 'Deposito', 0, 1600.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'SANTANDER', '56901746604', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '12'));

UPDATE empleados e
SET
    numero_empleado = '13',
    numero_empleado_baja = NULL,
    nombre_completo = 'VARGAS MARTINEZ VICTOR MANUEL',
    puesto = NULL,
    fecha_ingreso = '2016-04-02',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2016-04-02' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2016-04-02') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -18,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1600.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'SANTANDER',
    numero_cuenta = '56903788610',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '13');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '13', NULL, 'VARGAS MARTINEZ VICTOR MANUEL', NULL, '2016-04-02', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2016-04-02' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2016-04-02') + 1 ELSE 0 END, NULL, -18, 'Deposito', 0, 1600.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'SANTANDER', '56903788610', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '13'));

UPDATE empleados e
SET
    numero_empleado = '14',
    numero_empleado_baja = NULL,
    nombre_completo = 'ISLAS MAYORAL RICARDO',
    puesto = NULL,
    fecha_ingreso = '2017-07-07',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2017-07-07' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2017-07-07') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 10,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1500.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 200.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 274.43,
    saldo_prestamo = 200.00,
    banco = 'BANORTE',
    numero_cuenta = '4189143334058370',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '14');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '14', NULL, 'ISLAS MAYORAL RICARDO', NULL, '2017-07-07', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2017-07-07' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2017-07-07') + 1 ELSE 0 END, NULL, 10, 'Deposito', 0, 1500.00, 0.00, 200.00, 49.00, 57.63, 274.43, 200.00, 'BANORTE', '4189143334058370', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '14'));

UPDATE empleados e
SET
    numero_empleado = '15',
    numero_empleado_baja = NULL,
    nombre_completo = 'VEGA RAMOS BERNARDO',
    puesto = NULL,
    fecha_ingreso = '2016-01-07',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2016-01-07' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2016-01-07') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -22,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1950.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 200.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 200.00,
    banco = 'SANTANDER',
    numero_cuenta = '56901762667',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '15');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '15', NULL, 'VEGA RAMOS BERNARDO', NULL, '2016-01-07', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2016-01-07' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2016-01-07') + 1 ELSE 0 END, NULL, -22, 'Deposito', 0, 1950.00, 0.00, 200.00, 49.00, 57.63, 0.00, 200.00, 'SANTANDER', '56901762667', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '15'));

UPDATE empleados e
SET
    numero_empleado = '16',
    numero_empleado_baja = NULL,
    nombre_completo = 'RODRIGUEZ GUTIERREZ JESSICA',
    puesto = NULL,
    fecha_ingreso = '2025-02-24',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-02-24' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-02-24') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -12,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1850.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'COPPEL',
    numero_cuenta = '4169 1614 7892 4359',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '16');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '16', NULL, 'RODRIGUEZ GUTIERREZ JESSICA', NULL, '2025-02-24', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-02-24' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-02-24') + 1 ELSE 0 END, NULL, -12, 'Deposito', 0, 1850.00, 0.00, 0, 49.00, 57.63, 0.00, 0.00, 'COPPEL', '4169 1614 7892 4359', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '16'));

UPDATE empleados e
SET
    numero_empleado = '17',
    numero_empleado_baja = NULL,
    nombre_completo = 'PEREZ DIAZ CARLOS ALBERTO',
    puesto = NULL,
    fecha_ingreso = '2023-10-09',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2023-10-09' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2023-10-09') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -4,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2250.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 445.38,
    saldo_prestamo = 60.00,
    banco = 'SANTANDER',
    numero_cuenta = '56903788641',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '17');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '17', NULL, 'PEREZ DIAZ CARLOS ALBERTO', NULL, '2023-10-09', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2023-10-09' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2023-10-09') + 1 ELSE 0 END, NULL, -4, 'Deposito', 0, 2250.00, 0.00, 60.00, 49.00, 57.63, 445.38, 60.00, 'SANTANDER', '56903788641', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '17'));

UPDATE empleados e
SET
    numero_empleado = '18',
    numero_empleado_baja = NULL,
    nombre_completo = 'GOMEZ MENDOZA JULIO CESAR',
    puesto = NULL,
    fecha_ingreso = '2024-07-25',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2024-07-25' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-07-25') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 2,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'SANTANDER',
    numero_cuenta = '56903788655',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '18');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '18', NULL, 'GOMEZ MENDOZA JULIO CESAR', NULL, '2024-07-25', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2024-07-25' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-07-25') + 1 ELSE 0 END, NULL, 2, 'Deposito', 0, 2000.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'SANTANDER', '56903788655', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '18'));

UPDATE empleados e
SET
    numero_empleado = '19',
    numero_empleado_baja = NULL,
    nombre_completo = 'GUTIERREZ LOPEZ J CRUZ',
    puesto = NULL,
    fecha_ingreso = '2005-04-17',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2005-04-17' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2005-04-17') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 6,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1700.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 301.08,
    saldo_prestamo = 60.00,
    banco = 'AZTECA',
    numero_cuenta = '4027 6658 6498 7616',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '19');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '19', NULL, 'GUTIERREZ LOPEZ J CRUZ', NULL, '2005-04-17', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2005-04-17' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2005-04-17') + 1 ELSE 0 END, NULL, 6, 'Deposito', 0, 1700.00, 0.00, 60.00, 49.00, 57.63, 301.08, 60.00, 'AZTECA', '4027 6658 6498 7616', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '19'));

UPDATE empleados e
SET
    numero_empleado = '20',
    numero_empleado_baja = NULL,
    nombre_completo = 'FLORES JIMENEZ ADOLFO',
    puesto = NULL,
    fecha_ingreso = '2023-07-27',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2023-07-27' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2023-07-27') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -4,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1230.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'BANORTE',
    numero_cuenta = '4189143334058388',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '20');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '20', NULL, 'FLORES JIMENEZ ADOLFO', NULL, '2023-07-27', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2023-07-27' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2023-07-27') + 1 ELSE 0 END, NULL, -4, 'Deposito', 0, 1230.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'BANORTE', '4189143334058388', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '20'));

UPDATE empleados e
SET
    numero_empleado = '21',
    numero_empleado_baja = NULL,
    nombre_completo = 'BALDERAS MAYORGA ANTONIO',
    puesto = NULL,
    fecha_ingreso = '2019-07-19',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2019-07-19' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2019-07-19') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -16,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1890.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 200.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 200.00,
    banco = 'SANTANDER',
    numero_cuenta = '56903788581',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '21');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '21', NULL, 'BALDERAS MAYORGA ANTONIO', NULL, '2019-07-19', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2019-07-19' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2019-07-19') + 1 ELSE 0 END, NULL, -16, 'Deposito', 0, 1890.00, 0.00, 200.00, 49.00, 57.63, 0.00, 200.00, 'SANTANDER', '56903788581', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '21'));

UPDATE empleados e
SET
    numero_empleado = '22',
    numero_empleado_baja = NULL,
    nombre_completo = 'PANCARDO CALDERON ARMANDO',
    puesto = NULL,
    fecha_ingreso = '2020-10-14',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2020-10-14' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2020-10-14') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 19,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1300.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'SANTANDER',
    numero_cuenta = '56901796594',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '22');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '22', NULL, 'PANCARDO CALDERON ARMANDO', NULL, '2020-10-14', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2020-10-14' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2020-10-14') + 1 ELSE 0 END, NULL, 19, 'Deposito', 0, 1300.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'SANTANDER', '56901796594', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '22'));

UPDATE empleados e
SET
    numero_empleado = '23',
    numero_empleado_baja = NULL,
    nombre_completo = 'GONZALEZ RAMIREZ PORFIRIO',
    puesto = NULL,
    fecha_ingreso = '2025-10-16',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-10-16' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-10-16') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 1,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1230.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 387.12,
    saldo_prestamo = 0.00,
    banco = 'COPPEL',
    numero_cuenta = '137180100034494977',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '23');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '23', NULL, 'GONZALEZ RAMIREZ PORFIRIO', NULL, '2025-10-16', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-10-16' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-10-16') + 1 ELSE 0 END, NULL, 1, 'Deposito', 0, 1230.00, 0.00, 0, 49.00, 57.63, 387.12, 0.00, 'COPPEL', '137180100034494977', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '23'));

UPDATE empleados e
SET
    numero_empleado = '25',
    numero_empleado_baja = NULL,
    nombre_completo = 'RAMOS RODRIGUEZ JUAN CARLOS',
    puesto = NULL,
    fecha_ingreso = '2025-07-03',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-07-03' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-07-03') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 6,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1850.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'COPPEL',
    numero_cuenta = '4169 1606 3746 1568',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '25');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '25', NULL, 'RAMOS RODRIGUEZ JUAN CARLOS', NULL, '2025-07-03', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-07-03' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-07-03') + 1 ELSE 0 END, NULL, 6, 'Deposito', 0, 1850.00, 0.00, 0, 49.00, 57.63, 0.00, 0.00, 'COPPEL', '4169 1606 3746 1568', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '25'));

UPDATE empleados e
SET
    numero_empleado = '26',
    numero_empleado_baja = NULL,
    nombre_completo = 'CRUZ PACHECO LAURO',
    puesto = NULL,
    fecha_ingreso = '2022-01-14',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2022-01-14' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2022-01-14') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -19,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1350.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'BANORTE',
    numero_cuenta = '4189143294704377',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '26');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '26', NULL, 'CRUZ PACHECO LAURO', NULL, '2022-01-14', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2022-01-14' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2022-01-14') + 1 ELSE 0 END, NULL, -19, 'Deposito', 0, 1350.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'BANORTE', '4189143294704377', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '26'));

UPDATE empleados e
SET
    numero_empleado = '27',
    numero_empleado_baja = NULL,
    nombre_completo = 'VARGAS MARTINEZ JOSE MARIO',
    puesto = NULL,
    fecha_ingreso = '2022-12-10',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2022-12-10' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2022-12-10') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 4,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'SANTANDER',
    numero_cuenta = '5579 1004 8794 6186',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '27');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '27', NULL, 'VARGAS MARTINEZ JOSE MARIO', NULL, '2022-12-10', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2022-12-10' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2022-12-10') + 1 ELSE 0 END, NULL, 4, 'Deposito', 0, 1000.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'SANTANDER', '5579 1004 8794 6186', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '27'));

UPDATE empleados e
SET
    numero_empleado = '29',
    numero_empleado_baja = NULL,
    nombre_completo = 'HERNANDEZ PEREZ VICTOR ENRIQUE',
    puesto = NULL,
    fecha_ingreso = '2025-02-17',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-02-17' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-02-17') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 8,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2230.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'AZTECA',
    numero_cuenta = '5263 5401 2928 0810',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '29');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '29', NULL, 'HERNANDEZ PEREZ VICTOR ENRIQUE', NULL, '2025-02-17', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-02-17' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-02-17') + 1 ELSE 0 END, NULL, 8, 'Deposito', 0, 2230.00, 0.00, 0, 49.00, 57.63, 0.00, 0.00, 'AZTECA', '5263 5401 2928 0810', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '29'));

UPDATE empleados e
SET
    numero_empleado = '30',
    numero_empleado_baja = NULL,
    nombre_completo = 'RODRIGUEZ FLORES JOSE FRANCISCO',
    puesto = NULL,
    fecha_ingreso = '2022-05-25',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2022-05-25' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2022-05-25') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -5,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2850.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 309.58,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 309.58,
    banco = 'COPPEL',
    numero_cuenta = '4169 1614 7478 5259',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '30');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '30', NULL, 'RODRIGUEZ FLORES JOSE FRANCISCO', NULL, '2022-05-25', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2022-05-25' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2022-05-25') + 1 ELSE 0 END, NULL, -5, 'Deposito', 0, 2850.00, 0.00, 309.58, 0.00, 0.00, 0.00, 309.58, 'COPPEL', '4169 1614 7478 5259', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '30'));

UPDATE empleados e
SET
    numero_empleado = '31',
    numero_empleado_baja = NULL,
    nombre_completo = 'ORDAZ RIVERA GAEL',
    puesto = NULL,
    fecha_ingreso = '2024-06-20',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2024-06-20' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-06-20') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -1,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2240.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'BANORTE',
    numero_cuenta = '4189143334058396',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '31');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '31', NULL, 'ORDAZ RIVERA GAEL', NULL, '2024-06-20', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2024-06-20' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-06-20') + 1 ELSE 0 END, NULL, -1, 'Deposito', 0, 2240.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'BANORTE', '4189143334058396', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '31'));

UPDATE empleados e
SET
    numero_empleado = '32',
    numero_empleado_baja = NULL,
    nombre_completo = 'ORTIZ GARCIA JESUS',
    puesto = NULL,
    fecha_ingreso = '2022-08-11',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2022-08-11' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2022-08-11') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -4,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2240.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'SANTANDER',
    numero_cuenta = '56901743366',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '32');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '32', NULL, 'ORTIZ GARCIA JESUS', NULL, '2022-08-11', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2022-08-11' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2022-08-11') + 1 ELSE 0 END, NULL, -4, 'Deposito', 0, 2240.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'SANTANDER', '56901743366', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '32'));

UPDATE empleados e
SET
    numero_empleado = '33',
    numero_empleado_baja = NULL,
    nombre_completo = 'MONTALVO PACHECO ARMANDO',
    puesto = NULL,
    fecha_ingreso = '2023-04-20',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2023-04-20' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2023-04-20') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -8,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2250.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'COPPEL',
    numero_cuenta = '4169 1608 9457 2784',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '33');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '33', NULL, 'MONTALVO PACHECO ARMANDO', NULL, '2023-04-20', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2023-04-20' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2023-04-20') + 1 ELSE 0 END, NULL, -8, 'Deposito', 0, 2250.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'COPPEL', '4169 1608 9457 2784', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '33'));

UPDATE empleados e
SET
    numero_empleado = '34',
    numero_empleado_baja = NULL,
    nombre_completo = 'RAMIREZ CONTRERAS GERARDO',
    puesto = NULL,
    fecha_ingreso = '2025-05-02',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-05-02' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-05-02') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -3,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2500.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '34');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '34', NULL, 'RAMIREZ CONTRERAS GERARDO', NULL, '2025-05-02', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-05-02' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-05-02') + 1 ELSE 0 END, NULL, -3, 'Deposito', 0, 2500.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '34'));

UPDATE empleados e
SET
    numero_empleado = '35',
    numero_empleado_baja = NULL,
    nombre_completo = 'HERNANDEZ GARCIA NESLI MARGARITA',
    puesto = NULL,
    fecha_ingreso = '2025-01-20',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-01-20' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-01-20') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -7,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '35');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '35', NULL, 'HERNANDEZ GARCIA NESLI MARGARITA', NULL, '2025-01-20', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-01-20' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-01-20') + 1 ELSE 0 END, NULL, -7, 'Deposito', 0, 2000.00, 0.00, 0, 49.00, 57.63, 0.00, 0.00, 'BBVA', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '35'));

UPDATE empleados e
SET
    numero_empleado = '37',
    numero_empleado_baja = NULL,
    nombre_completo = 'HERRERA LUENGAS CARLOS DANIEL',
    puesto = NULL,
    fecha_ingreso = '2025-05-19',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-05-19' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-05-19') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -6,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1700.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BANORTE',
    numero_cuenta = '4189143334058610',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '37');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '37', NULL, 'HERRERA LUENGAS CARLOS DANIEL', NULL, '2025-05-19', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-05-19' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-05-19') + 1 ELSE 0 END, NULL, -6, 'Deposito', 0, 1700.00, 0.00, 0, 49.00, 57.63, 0.00, 0.00, 'BANORTE', '4189143334058610', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '37'));

UPDATE empleados e
SET
    numero_empleado = '39',
    numero_empleado_baja = NULL,
    nombre_completo = 'PEREZ CRUZ RICARDO',
    puesto = NULL,
    fecha_ingreso = '2024-12-05',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2024-12-05' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-12-05') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 7,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1400.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 190.17,
    saldo_prestamo = 60.00,
    banco = 'BANORTE',
    numero_cuenta = '4189143294704435',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '39');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '39', NULL, 'PEREZ CRUZ RICARDO', NULL, '2024-12-05', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2024-12-05' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-12-05') + 1 ELSE 0 END, NULL, 7, 'Deposito', 0, 1400.00, 0.00, 60.00, 49.00, 57.63, 190.17, 60.00, 'BANORTE', '4189143294704435', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '39'));

UPDATE empleados e
SET
    numero_empleado = '40',
    numero_empleado_baja = NULL,
    nombre_completo = 'SANCHEZ HERNANDEZ ERICK',
    puesto = NULL,
    fecha_ingreso = '2023-07-13',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2023-07-13' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2023-07-13') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -8,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'BANORTE',
    numero_cuenta = '4189143334058404',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '40');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '40', NULL, 'SANCHEZ HERNANDEZ ERICK', NULL, '2023-07-13', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2023-07-13' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2023-07-13') + 1 ELSE 0 END, NULL, -8, 'Deposito', 0, 2000.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'BANORTE', '4189143334058404', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '40'));

UPDATE empleados e
SET
    numero_empleado = '42',
    numero_empleado_baja = NULL,
    nombre_completo = 'CAYENTE BAUTISTA MARIO ALBERTO',
    puesto = NULL,
    fecha_ingreso = '2025-01-09',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-01-09' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-01-09') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -9,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1750.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'AZTECA',
    numero_cuenta = '5263 5401 3253 6992',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '42');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '42', NULL, 'CAYENTE BAUTISTA MARIO ALBERTO', NULL, '2025-01-09', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-01-09' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-01-09') + 1 ELSE 0 END, NULL, -9, 'Deposito', 0, 1750.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, 'AZTECA', '5263 5401 3253 6992', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '42'));

UPDATE empleados e
SET
    numero_empleado = '43',
    numero_empleado_baja = NULL,
    nombre_completo = 'AVILA RAMOS FERNANDO',
    puesto = NULL,
    fecha_ingreso = '2024-05-21',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2024-05-21' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-05-21') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -11,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1850.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 200.00,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 200.00,
    banco = 'AZTECA',
    numero_cuenta = '9546 11669 0845 08',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '43');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '43', NULL, 'AVILA RAMOS FERNANDO', NULL, '2024-05-21', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2024-05-21' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-05-21') + 1 ELSE 0 END, NULL, -11, 'Deposito', 0, 1850.00, 0.00, 200.00, 0.00, 0.00, 0.00, 200.00, 'AZTECA', '9546 11669 0845 08', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '43'));

UPDATE empleados e
SET
    numero_empleado = '44',
    numero_empleado_baja = NULL,
    nombre_completo = 'DIAZ MUNGIA ERIK JONATHAN',
    puesto = NULL,
    fecha_ingreso = '2024-05-30',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2024-05-30' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-05-30') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -14,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1900.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'BANORTE',
    numero_cuenta = '4189143334058354',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '44');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '44', NULL, 'DIAZ MUNGIA ERIK JONATHAN', NULL, '2024-05-30', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2024-05-30' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-05-30') + 1 ELSE 0 END, NULL, -14, 'Deposito', 0, 1900.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'BANORTE', '4189143334058354', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '44'));

UPDATE empleados e
SET
    numero_empleado = '45',
    numero_empleado_baja = NULL,
    nombre_completo = 'LEON DE JESUS CARLOS',
    puesto = NULL,
    fecha_ingreso = '2025-05-15',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-05-15' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-05-15') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -4,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'BANORTE',
    numero_cuenta = '4189143334058412',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '45');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '45', NULL, 'LEON DE JESUS CARLOS', NULL, '2025-05-15', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-05-15' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-05-15') + 1 ELSE 0 END, NULL, -4, 'Deposito', 0, 1000.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'BANORTE', '4189143334058412', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '45'));

UPDATE empleados e
SET
    numero_empleado = '46',
    numero_empleado_baja = NULL,
    nombre_completo = 'RODRIGUEZ RIVERA MARTIN',
    puesto = NULL,
    fecha_ingreso = '2024-08-29',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2024-08-29' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-08-29') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 4,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2220.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'AZTECA',
    numero_cuenta = '4027 6658 7366 7951',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '46');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '46', NULL, 'RODRIGUEZ RIVERA MARTIN', NULL, '2024-08-29', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2024-08-29' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-08-29') + 1 ELSE 0 END, NULL, 4, 'Deposito', 0, 2220.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'AZTECA', '4027 6658 7366 7951', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '46'));

UPDATE empleados e
SET
    numero_empleado = '47',
    numero_empleado_baja = NULL,
    nombre_completo = 'AVILA TELLES JAHIR',
    puesto = NULL,
    fecha_ingreso = '2023-09-12',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2023-09-12' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2023-09-12') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -4,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'BANORTE',
    numero_cuenta = '4189143294704351',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '47');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '47', NULL, 'AVILA TELLES JAHIR', NULL, '2023-09-12', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2023-09-12' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2023-09-12') + 1 ELSE 0 END, NULL, -4, 'Deposito', 0, 1000.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'BANORTE', '4189143294704351', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '47'));

UPDATE empleados e
SET
    numero_empleado = '48',
    numero_empleado_baja = NULL,
    nombre_completo = 'FLORES LOPEZ EULISES',
    puesto = NULL,
    fecha_ingreso = '2024-05-02',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2024-05-02' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-05-02') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -10,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2200.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'AZTECA',
    numero_cuenta = '5263 5401 4333 8487',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '48');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '48', NULL, 'FLORES LOPEZ EULISES', NULL, '2024-05-02', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2024-05-02' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-05-02') + 1 ELSE 0 END, NULL, -10, 'Deposito', 0, 2200.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'AZTECA', '5263 5401 4333 8487', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '48'));

UPDATE empleados e
SET
    numero_empleado = '49',
    numero_empleado_baja = NULL,
    nombre_completo = 'GONZALEZ MARTINEZ CRUZ',
    puesto = NULL,
    fecha_ingreso = '2025-02-06',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-02-06' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-02-06') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -5,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1150.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'AZTECA',
    numero_cuenta = '5263 5401 2964 2316',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '49');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '49', NULL, 'GONZALEZ MARTINEZ CRUZ', NULL, '2025-02-06', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-02-06' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-02-06') + 1 ELSE 0 END, NULL, -5, 'Deposito', 0, 1150.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, 'AZTECA', '5263 5401 2964 2316', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '49'));

UPDATE empleados e
SET
    numero_empleado = '50',
    numero_empleado_baja = NULL,
    nombre_completo = 'GARCIA ESPINOSA EDUARDO',
    puesto = NULL,
    fecha_ingreso = '2025-02-27',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-02-27' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-02-27') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -4,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1230.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'AZTECA',
    numero_cuenta = '5263 5401 3084 3259',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '50');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '50', NULL, 'GARCIA ESPINOSA EDUARDO', NULL, '2025-02-27', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-02-27' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-02-27') + 1 ELSE 0 END, NULL, -4, 'Deposito', 0, 1230.00, 0.00, 0, 49.00, 57.63, 0.00, 0.00, 'AZTECA', '5263 5401 3084 3259', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '50'));

UPDATE empleados e
SET
    numero_empleado = '52',
    numero_empleado_baja = NULL,
    nombre_completo = 'LEON PEREZ ORLANDO HAIR',
    puesto = NULL,
    fecha_ingreso = '2025-05-15',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-05-15' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-05-15') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -4,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1650.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 60.00,
    descuento_imss = 49.00,
    descuento_isr = 57.63,
    descuento_infonavit = 0.00,
    saldo_prestamo = 60.00,
    banco = 'BANORTE',
    numero_cuenta = '4189143334058420',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '52');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '52', NULL, 'LEON PEREZ ORLANDO HAIR', NULL, '2025-05-15', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-05-15' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-05-15') + 1 ELSE 0 END, NULL, -4, 'Deposito', 0, 1650.00, 0.00, 60.00, 49.00, 57.63, 0.00, 60.00, 'BANORTE', '4189143334058420', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '52'));

UPDATE empleados e
SET
    numero_empleado = '53',
    numero_empleado_baja = NULL,
    nombre_completo = 'PIMENTEL GOMEZ ALEXIS HERALDO',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Efectivo',
    es_estudiante = 0,
    sueldo_semanal = 2000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = NULL,
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '53');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '53', NULL, 'PIMENTEL GOMEZ ALEXIS HERALDO', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Efectivo', 0, 2000.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '53'));

UPDATE empleados e
SET
    numero_empleado = '54',
    numero_empleado_baja = NULL,
    nombre_completo = 'MARTINEZ ROJAS ANGEL FABIAN',
    puesto = NULL,
    fecha_ingreso = '2025-04-24',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-04-24' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-04-24') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = -4,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'MERCADO PAGO',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '54');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '54', NULL, 'MARTINEZ ROJAS ANGEL FABIAN', NULL, '2025-04-24', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-04-24' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-04-24') + 1 ELSE 0 END, NULL, -4, 'Deposito', 0, 2000.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, 'MERCADO PAGO', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '54'));

UPDATE empleados e
SET
    numero_empleado = '56',
    numero_empleado_baja = NULL,
    nombre_completo = 'ANTONIO ORTIZ GILBERTO',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Efectivo',
    es_estudiante = 0,
    sueldo_semanal = 2000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = NULL,
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '56');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '56', NULL, 'ANTONIO ORTIZ GILBERTO', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Efectivo', 0, 2000.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '56'));

UPDATE empleados e
SET
    numero_empleado = '57',
    numero_empleado_baja = NULL,
    nombre_completo = 'ORTIZ DIAZ LUIS MARIO',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = NULL,
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '57');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '57', NULL, 'ORTIZ DIAZ LUIS MARIO', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 0, 2000.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '57'));

UPDATE empleados e
SET
    numero_empleado = '58',
    numero_empleado_baja = NULL,
    nombre_completo = 'CASTILLO LOPÉZ JESUS ISMAEL',
    puesto = NULL,
    fecha_ingreso = '2025-07-03',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2025-07-03' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-07-03') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 5,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'AZTECA',
    numero_cuenta = '5263 5401 7000 1644',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '58');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '58', NULL, 'CASTILLO LOPÉZ JESUS ISMAEL', NULL, '2025-07-03', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2025-07-03' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2025-07-03') + 1 ELSE 0 END, NULL, 5, 'Deposito', 0, 2000.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, 'AZTECA', '5263 5401 7000 1644', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '58'));

UPDATE empleados e
SET
    numero_empleado = '60',
    numero_empleado_baja = NULL,
    nombre_completo = 'GUTIERREZ SANCHEZ JOSE',
    puesto = NULL,
    fecha_ingreso = '2026-01-12',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2026-01-12' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2026-01-12') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 2,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 2000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'SANTANDER',
    numero_cuenta = '5579 1004 4518 0613',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '60');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '60', NULL, 'GUTIERREZ SANCHEZ JOSE', NULL, '2026-01-12', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2026-01-12' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2026-01-12') + 1 ELSE 0 END, NULL, 2, 'Deposito', 0, 2000.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, 'SANTANDER', '5579 1004 4518 0613', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '60'));

UPDATE empleados e
SET
    numero_empleado = '69',
    numero_empleado_baja = NULL,
    nombre_completo = 'ESPINOZA AHUMADA SAMANTHA ALEJANDRA',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 0,
    sueldo_semanal = 1000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'NU',
    numero_cuenta = '010150516904',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '69');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '69', NULL, 'ESPINOZA AHUMADA SAMANTHA ALEJANDRA', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 0, 1000.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, 'NU', '010150516904', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '69'));

UPDATE empleados e
SET
    numero_empleado = '70',
    numero_empleado_baja = NULL,
    nombre_completo = 'LOPEZ SOTO DANIEL',
    puesto = NULL,
    fecha_ingreso = '2024-10-10',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2024-10-10' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-10-10') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Efectivo',
    es_estudiante = 0,
    sueldo_semanal = 1000.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = NULL,
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '70');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '70', NULL, 'LOPEZ SOTO DANIEL', NULL, '2024-10-10', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2024-10-10' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-10-10') + 1 ELSE 0 END, NULL, 0, 'Efectivo', 0, 1000.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '70'));

UPDATE empleados e
SET
    numero_empleado = '71',
    numero_empleado_baja = NULL,
    nombre_completo = 'CASTILLO MENDOZA CHRISTIAN RAUL',
    puesto = NULL,
    fecha_ingreso = '2024-11-19',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2024-11-19' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-11-19') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Efectivo',
    es_estudiante = 0,
    sueldo_semanal = 1400.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = NULL,
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '71');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '71', NULL, 'CASTILLO MENDOZA CHRISTIAN RAUL', NULL, '2024-11-19', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2024-11-19' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-11-19') + 1 ELSE 0 END, NULL, 0, 'Efectivo', 0, 1400.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '71'));

UPDATE empleados e
SET
    numero_empleado = '72',
    numero_empleado_baja = NULL,
    nombre_completo = 'MEDINA SANCHEZ GREGORY',
    puesto = NULL,
    fecha_ingreso = '2024-12-20',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2024-12-20' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-12-20') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Efectivo',
    es_estudiante = 0,
    sueldo_semanal = 1500.00,
    sueldo_por_hora = 0.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = NULL,
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '72');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '72', NULL, 'MEDINA SANCHEZ GREGORY', NULL, '2024-12-20', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2024-12-20' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2024-12-20') + 1 ELSE 0 END, NULL, 0, 'Efectivo', 0, 1500.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, NULL, NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '72'));

UPDATE empleados e
SET
    numero_empleado = '73',
    numero_empleado_baja = NULL,
    nombre_completo = 'GARCIA MENESES LUIS ANGEL',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = '4152 3145 3032 0293',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '73');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '73', NULL, 'GARCIA MENESES LUIS ANGEL', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', '4152 3145 3032 0293', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '73'));

UPDATE empleados e
SET
    numero_empleado = '75',
    numero_empleado_baja = NULL,
    nombre_completo = 'ORDUÑO MONDRAGON ERIKA JIMENA',
    puesto = NULL,
    fecha_ingreso = '2026-01-08',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2026-01-08' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2026-01-08') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'AZTECA',
    numero_cuenta = '4027 6600 2361 8327',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '75');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '75', NULL, 'ORDUÑO MONDRAGON ERIKA JIMENA', NULL, '2026-01-08', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2026-01-08' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2026-01-08') + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'AZTECA', '4027 6600 2361 8327', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '75'));

UPDATE empleados e
SET
    numero_empleado = '76',
    numero_empleado_baja = NULL,
    nombre_completo = 'ESTRADA MERINO ANA KAREN',
    puesto = NULL,
    fecha_ingreso = '2026-01-13',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2026-01-13' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2026-01-13') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = '012 180 01568789563 8',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '76');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '76', NULL, 'ESTRADA MERINO ANA KAREN', NULL, '2026-01-13', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2026-01-13' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2026-01-13') + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', '012 180 01568789563 8', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '76'));

UPDATE empleados e
SET
    numero_empleado = '77',
    numero_empleado_baja = NULL,
    nombre_completo = 'RAMIREZ VAZQUEZ ISSAC',
    puesto = NULL,
    fecha_ingreso = '2026-01-12',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2026-01-12' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2026-01-12') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = '4152 3145 0469 8526',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '77');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '77', NULL, 'RAMIREZ VAZQUEZ ISSAC', NULL, '2026-01-12', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2026-01-12' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2026-01-12') + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', '4152 3145 0469 8526', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '77'));

UPDATE empleados e
SET
    numero_empleado = '78',
    numero_empleado_baja = NULL,
    nombre_completo = 'RAMOS RODRIGUEZ ISMAEL',
    puesto = NULL,
    fecha_ingreso = '2026-01-12',
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN '2026-01-12' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2026-01-12') + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'AZTECA',
    numero_cuenta = '4027 6600 1780 7266',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '78');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '78', NULL, 'RAMOS RODRIGUEZ ISMAEL', NULL, '2026-01-12', NULL, CASE WHEN 0 > 0 THEN 0 WHEN '2026-01-12' IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, '2026-01-12') + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'AZTECA', '4027 6600 1780 7266', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '78'));

UPDATE empleados e
SET
    numero_empleado = '79',
    numero_empleado_baja = NULL,
    nombre_completo = 'SANCHEZ ALONSO JONATHAN',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '79');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '79', NULL, 'SANCHEZ ALONSO JONATHAN', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '79'));

UPDATE empleados e
SET
    numero_empleado = '80',
    numero_empleado_baja = NULL,
    nombre_completo = 'AVILA AVILA KEVIN YAHIR',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = '4152 3145 4683 4428',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '80');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '80', NULL, 'AVILA AVILA KEVIN YAHIR', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', '4152 3145 4683 4428', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '80'));

UPDATE empleados e
SET
    numero_empleado = '81',
    numero_empleado_baja = NULL,
    nombre_completo = 'DELGADO FLORES EDWIN ALEXIS',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'SPIN BY OXXO',
    numero_cuenta = '4217 4702 0679 4915',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '81');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '81', NULL, 'DELGADO FLORES EDWIN ALEXIS', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'SPIN BY OXXO', '4217 4702 0679 4915', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '81'));

UPDATE empleados e
SET
    numero_empleado = '82',
    numero_empleado_baja = NULL,
    nombre_completo = 'ESCALANTE SOLIS DIEGO',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'NU',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '82');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '82', NULL, 'ESCALANTE SOLIS DIEGO', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'NU', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '82'));

UPDATE empleados e
SET
    numero_empleado = '83',
    numero_empleado_baja = NULL,
    nombre_completo = 'GARCIA RAMIREZ ERICK ALEJANDRO',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = '4152 3146 2033 4840',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '83');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '83', NULL, 'GARCIA RAMIREZ ERICK ALEJANDRO', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', '4152 3146 2033 4840', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '83'));

UPDATE empleados e
SET
    numero_empleado = '84',
    numero_empleado_baja = NULL,
    nombre_completo = 'HERNANDEZ SANTILLAN ANGEL GABRIEL',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '84');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '84', NULL, 'HERNANDEZ SANTILLAN ANGEL GABRIEL', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '84'));

UPDATE empleados e
SET
    numero_empleado = '85',
    numero_empleado_baja = NULL,
    nombre_completo = 'GARCIA AQUINO ANA BELÉN',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'AZTECA',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '85');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '85', NULL, 'GARCIA AQUINO ANA BELÉN', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'AZTECA', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '85'));

UPDATE empleados e
SET
    numero_empleado = '86',
    numero_empleado_baja = NULL,
    nombre_completo = 'TENORIO CIGALES SARA MARIA',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'COPPEL',
    numero_cuenta = '4169 1609 0961 7277',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '86');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '86', NULL, 'TENORIO CIGALES SARA MARIA', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'COPPEL', '4169 1609 0961 7277', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '86'));

UPDATE empleados e
SET
    numero_empleado = '87',
    numero_empleado_baja = NULL,
    nombre_completo = 'GONZALES BOBADILLA LUIS ALFONSO',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '87');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '87', NULL, 'GONZALES BOBADILLA LUIS ALFONSO', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '87'));

UPDATE empleados e
SET
    numero_empleado = '88',
    numero_empleado_baja = NULL,
    nombre_completo = 'HERNANDEZ GRANADOS SANTOS EDMUNDO',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '88');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '88', NULL, 'HERNANDEZ GRANADOS SANTOS EDMUNDO', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '88'));

UPDATE empleados e
SET
    numero_empleado = '89',
    numero_empleado_baja = NULL,
    nombre_completo = 'VAZQUEZ ESTRADA OSCAR URIEL',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '89');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '89', NULL, 'VAZQUEZ ESTRADA OSCAR URIEL', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '89'));

UPDATE empleados e
SET
    numero_empleado = '90',
    numero_empleado_baja = NULL,
    nombre_completo = 'LOZADA MARTINEZ ESTEBAN ALEXIS',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '90');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '90', NULL, 'LOZADA MARTINEZ ESTEBAN ALEXIS', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '90'));

UPDATE empleados e
SET
    numero_empleado = '91',
    numero_empleado_baja = NULL,
    nombre_completo = 'ORAMASS HERNANDEZ ABRAHAM ISRAEL',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '91');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '91', NULL, 'ORAMASS HERNANDEZ ABRAHAM ISRAEL', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '91'));

UPDATE empleados e
SET
    numero_empleado = '92',
    numero_empleado_baja = NULL,
    nombre_completo = 'PACHECO PACHECO ULISES EMANUEL',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '92');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '92', NULL, 'PACHECO PACHECO ULISES EMANUEL', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '92'));

UPDATE empleados e
SET
    numero_empleado = '93',
    numero_empleado_baja = NULL,
    nombre_completo = 'MENDOZA PEREZ INGRID SOFIA',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'COPPEL',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '93');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '93', NULL, 'MENDOZA PEREZ INGRID SOFIA', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'COPPEL', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '93'));

UPDATE empleados e
SET
    numero_empleado = '94',
    numero_empleado_baja = NULL,
    nombre_completo = 'AVILA TELLEZ JONATHAN',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '94');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '94', NULL, 'AVILA TELLEZ JONATHAN', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '94'));

UPDATE empleados e
SET
    numero_empleado = '95',
    numero_empleado_baja = NULL,
    nombre_completo = 'MARCO DASHIELL ORTEGA HERNANDEZ',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'BBVA',
    numero_cuenta = NULL,
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '95');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '95', NULL, 'MARCO DASHIELL ORTEGA HERNANDEZ', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'BBVA', NULL, NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '95'));

UPDATE empleados e
SET
    numero_empleado = '96',
    numero_empleado_baja = NULL,
    nombre_completo = 'FRANCISCO MANUEL ISIDRO RAMOS',
    puesto = NULL,
    fecha_ingreso = NULL,
    fecha_baja = NULL,
    dias_laborados = CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END,
    motivo_baja = NULL,
    ajuste_vacaciones = 0,
    forma_pago = 'Deposito',
    es_estudiante = 1,
    sueldo_semanal = 0.00,
    sueldo_por_hora = 27.00,
    cuota_prestamo = 0,
    descuento_imss = 0.00,
    descuento_isr = 0.00,
    descuento_infonavit = 0.00,
    saldo_prestamo = 0.00,
    banco = 'COPPEL',
    numero_cuenta = '4162 1606 2948 6391',
    nss = NULL,
    rfc = NULL,
    curp = NULL,
    estatus = 1,
    updated_at = NOW()
WHERE (e.numero_empleado = '96');

INSERT INTO empleados (numero_empleado, numero_empleado_baja, nombre_completo, puesto, fecha_ingreso, fecha_baja, dias_laborados, motivo_baja, ajuste_vacaciones, forma_pago, es_estudiante, sueldo_semanal, sueldo_por_hora, cuota_prestamo, descuento_imss, descuento_isr, descuento_infonavit, saldo_prestamo, banco, numero_cuenta, nss, rfc, curp, estatus, created_at, updated_at)
SELECT '96', NULL, 'FRANCISCO MANUEL ISIDRO RAMOS', NULL, NULL, NULL, CASE WHEN 0 > 0 THEN 0 WHEN NULL IS NOT NULL AND NULL IS NOT NULL THEN DATEDIFF(NULL, NULL) + 1 ELSE 0 END, NULL, 0, 'Deposito', 1, 0.00, 27.00, 0, 0.00, 0.00, 0.00, 0.00, 'COPPEL', '4162 1606 2948 6391', NULL, NULL, NULL, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM empleados e WHERE (e.numero_empleado = '96'));

DELETE a
FROM asistencias a
JOIN empleados e ON e.id = a.empleado_id
WHERE a.fecha BETWEEN '2026-05-28' AND '2026-06-03'
  AND (e.numero_empleado IN ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18') OR e.numero_empleado_baja IN ('1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18'));

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 0, '07:37:00', '19:01:00', 9.50, 1.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '1' OR e.numero_empleado_baja = '1')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 0, '07:37:00', '17:35:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '1' OR e.numero_empleado_baja = '1')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 1, '08:01:00', '13:06:00', 0.00, 5.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '1' OR e.numero_empleado_baja = '1')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Falta', 0, NULL, NULL, 0.00, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '1' OR e.numero_empleado_baja = '1')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Falta', 0, NULL, NULL, 0.00, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '1' OR e.numero_empleado_baja = '1')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 0, '07:40:00', '18:00:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '2' OR e.numero_empleado_baja = '2')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Falta', 0, NULL, NULL, 0.00, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '2' OR e.numero_empleado_baja = '2')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 0, '07:47:00', '13:38:00', 0.00, 5.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '2' OR e.numero_empleado_baja = '2')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 0, '07:48:00', '17:31:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '2' OR e.numero_empleado_baja = '2')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 0, '07:35:00', '17:31:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '2' OR e.numero_empleado_baja = '2')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Normal', 0, '07:39:00', '17:30:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '2' OR e.numero_empleado_baja = '2')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 20, '08:20:00', '19:09:00', 9.50, 1.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '3' OR e.numero_empleado_baja = '3')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 6, '08:06:00', '17:37:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '3' OR e.numero_empleado_baja = '3')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 9, '08:09:00', '13:05:00', 0.00, 4.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '3' OR e.numero_empleado_baja = '3')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 26, '08:26:00', '19:34:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '3' OR e.numero_empleado_baja = '3')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 15, '08:15:00', '17:39:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '3' OR e.numero_empleado_baja = '3')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Falta', 0, NULL, NULL, 0.00, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '3' OR e.numero_empleado_baja = '3')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 0, '07:44:00', '19:01:00', 9.50, 1.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '4' OR e.numero_empleado_baja = '4')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 0, '07:45:00', '17:32:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '4' OR e.numero_empleado_baja = '4')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 0, '07:46:00', '13:02:00', 0.00, 5.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '4' OR e.numero_empleado_baja = '4')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 0, '07:47:00', '19:30:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '4' OR e.numero_empleado_baja = '4')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 0, '07:42:00', '19:33:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '4' OR e.numero_empleado_baja = '4')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Normal', 0, '07:40:00', '17:30:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '4' OR e.numero_empleado_baja = '4')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 15, '08:15:00', '19:09:00', 9.50, 1.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '5' OR e.numero_empleado_baja = '5')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 0, '07:59:00', '17:41:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '5' OR e.numero_empleado_baja = '5')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 13, '08:13:00', '13:10:00', 0.00, 4.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '5' OR e.numero_empleado_baja = '5')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 14, '08:14:00', '19:38:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '5' OR e.numero_empleado_baja = '5')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 10, '08:10:00', '19:40:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '5' OR e.numero_empleado_baja = '5')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Normal', 0, '08:00:00', '17:30:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '5' OR e.numero_empleado_baja = '5')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 0, '07:46:00', '19:32:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '6' OR e.numero_empleado_baja = '6')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 0, '07:50:00', '17:58:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '6' OR e.numero_empleado_baja = '6')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 29, '08:29:00', '13:02:00', 0.00, 4.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '6' OR e.numero_empleado_baja = '6')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 0, '07:49:00', '19:39:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '6' OR e.numero_empleado_baja = '6')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 0, '07:45:00', '22:18:00', 9.50, 4.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '6' OR e.numero_empleado_baja = '6')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Normal', 0, '07:29:00', '17:30:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '6' OR e.numero_empleado_baja = '6')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 6, '08:06:00', '19:06:00', 9.50, 1.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '7' OR e.numero_empleado_baja = '7')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 12, '08:12:00', '17:35:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '7' OR e.numero_empleado_baja = '7')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 9, '08:09:00', '13:07:00', 0.00, 4.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '7' OR e.numero_empleado_baja = '7')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 1, '08:01:00', '17:35:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '7' OR e.numero_empleado_baja = '7')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 3, '08:03:00', '19:33:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '7' OR e.numero_empleado_baja = '7')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Normal', 3, '08:03:00', '17:30:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '7' OR e.numero_empleado_baja = '7')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 0, '08:37:00', NULL, 0.00, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '8' OR e.numero_empleado_baja = '8')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 0, '08:24:00', NULL, 0.00, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '8' OR e.numero_empleado_baja = '8')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 0, '08:22:00', NULL, 0.00, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '8' OR e.numero_empleado_baja = '8')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 23, '08:23:00', '20:31:00', 9.50, 3.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '8' OR e.numero_empleado_baja = '8')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 0, NULL, '18:15:00', 0.00, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '8' OR e.numero_empleado_baja = '8')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Normal', 33, '08:33:00', '17:30:00', 8.95, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '8' OR e.numero_empleado_baja = '8')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 6, '08:06:00', '21:24:00', 9.50, 3.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '9' OR e.numero_empleado_baja = '9')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 19, '08:19:00', '19:08:00', 9.50, 1.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '9' OR e.numero_empleado_baja = '9')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 11, '08:11:00', '15:13:00', 0.00, 7.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '9' OR e.numero_empleado_baja = '9')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 21, '08:21:00', '21:03:00', 9.50, 3.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '9' OR e.numero_empleado_baja = '9')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 3, '08:03:00', '20:33:00', 9.50, 3.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '9' OR e.numero_empleado_baja = '9')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Normal', 41, '08:41:00', '17:30:00', 8.82, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '9' OR e.numero_empleado_baja = '9')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 3, '08:03:00', '18:04:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '10' OR e.numero_empleado_baja = '10')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 11, '08:11:00', '17:32:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '10' OR e.numero_empleado_baja = '10')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 5, '08:05:00', '13:03:00', 0.00, 4.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '10' OR e.numero_empleado_baja = '10')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 0, '07:59:00', '17:37:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '10' OR e.numero_empleado_baja = '10')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 4, '08:04:00', '17:34:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '10' OR e.numero_empleado_baja = '10')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Falta', 0, NULL, NULL, 0.00, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '10' OR e.numero_empleado_baja = '10')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 0, '07:51:00', '19:02:00', 9.50, 1.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '11' OR e.numero_empleado_baja = '11')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 0, '07:46:00', '17:38:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '11' OR e.numero_empleado_baja = '11')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 0, '07:47:00', '13:02:00', 0.00, 5.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '11' OR e.numero_empleado_baja = '11')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 0, '07:56:00', '19:38:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '11' OR e.numero_empleado_baja = '11')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 0, '07:43:00', '19:35:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '11' OR e.numero_empleado_baja = '11')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Normal', 0, '07:49:00', '17:30:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '11' OR e.numero_empleado_baja = '11')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 0, '07:58:00', '19:01:00', 9.50, 1.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '12' OR e.numero_empleado_baja = '12')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 6, '08:06:00', '17:42:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '12' OR e.numero_empleado_baja = '12')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 1, '08:01:00', '13:05:00', 0.00, 5.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '12' OR e.numero_empleado_baja = '12')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 0, '07:53:00', '19:33:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '12' OR e.numero_empleado_baja = '12')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 8, '08:08:00', '19:32:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '12' OR e.numero_empleado_baja = '12')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Falta', 0, NULL, NULL, 0.00, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '12' OR e.numero_empleado_baja = '12')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 2, '08:02:00', '19:07:00', 9.50, 1.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '13' OR e.numero_empleado_baja = '13')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 0, '07:59:00', '17:34:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '13' OR e.numero_empleado_baja = '13')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 5, '08:05:00', '13:03:00', 0.00, 4.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '13' OR e.numero_empleado_baja = '13')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 4, '08:04:00', '19:33:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '13' OR e.numero_empleado_baja = '13')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 6, '08:06:00', '19:32:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '13' OR e.numero_empleado_baja = '13')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Normal', 9, '08:09:00', '17:30:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '13' OR e.numero_empleado_baja = '13')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 0, NULL, '21:31:00', 0.00, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '14' OR e.numero_empleado_baja = '14')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 22, '08:22:00', '17:45:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '14' OR e.numero_empleado_baja = '14')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 16, '08:16:00', '14:15:00', 0.00, 5.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '14' OR e.numero_empleado_baja = '14')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 22, '08:22:00', '18:54:00', 9.50, 1.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '14' OR e.numero_empleado_baja = '14')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 60, '09:00:00', '22:04:00', 8.50, 4.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '14' OR e.numero_empleado_baja = '14')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Normal', 0, '07:59:00', '17:30:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '14' OR e.numero_empleado_baja = '14')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 10, '08:10:00', '19:03:00', 9.50, 1.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '15' OR e.numero_empleado_baja = '15')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 9, '08:09:00', '17:41:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '15' OR e.numero_empleado_baja = '15')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Falta', 0, NULL, NULL, 0.00, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '15' OR e.numero_empleado_baja = '15')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-31', 'Normal', 108, '09:48:00', '11:46:00', 1.97, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '15' OR e.numero_empleado_baja = '15')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 0, '07:59:00', '17:43:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '15' OR e.numero_empleado_baja = '15')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 0, '07:59:00', NULL, 0.00, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '15' OR e.numero_empleado_baja = '15')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Normal', 0, '07:58:00', '17:30:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '15' OR e.numero_empleado_baja = '15')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 0, '07:51:00', '19:01:00', 9.50, 1.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '16' OR e.numero_empleado_baja = '16')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 0, '07:49:00', '17:40:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '16' OR e.numero_empleado_baja = '16')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 0, '07:56:00', '13:02:00', 0.00, 5.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '16' OR e.numero_empleado_baja = '16')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 0, '07:49:00', '19:32:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '16' OR e.numero_empleado_baja = '16')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 0, '07:47:00', '17:33:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '16' OR e.numero_empleado_baja = '16')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Normal', 0, '07:50:00', '17:30:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '16' OR e.numero_empleado_baja = '16')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 0, '07:40:00', '19:01:00', 9.50, 1.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '17' OR e.numero_empleado_baja = '17')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 0, '07:52:00', '17:34:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '17' OR e.numero_empleado_baja = '17')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 0, '07:51:00', '13:04:00', 0.00, 5.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '17' OR e.numero_empleado_baja = '17')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 0, '07:43:00', '17:33:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '17' OR e.numero_empleado_baja = '17')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 0, '07:38:00', '19:31:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '17' OR e.numero_empleado_baja = '17')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Normal', 0, '07:38:00', '17:30:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '17' OR e.numero_empleado_baja = '17')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-28', 'Normal', 0, '07:50:00', '18:03:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '18' OR e.numero_empleado_baja = '18')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-29', 'Normal', 1, '08:01:00', '17:33:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '18' OR e.numero_empleado_baja = '18')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-05-30', 'Normal', 0, '07:58:00', '14:06:00', 0.00, 6.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '18' OR e.numero_empleado_baja = '18')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-01', 'Normal', 0, '07:51:00', '19:32:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '18' OR e.numero_empleado_baja = '18')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-02', 'Normal', 0, '07:54:00', '19:31:00', 9.50, 2.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '18' OR e.numero_empleado_baja = '18')
ORDER BY e.id DESC
LIMIT 1;

INSERT INTO asistencias (empleado_id, fecha, tipo_asistencia, minutos_tarde, hora_entrada, hora_salida, horas_trabajadas, horas_extra, created_at, updated_at)
SELECT e.id, '2026-06-03', 'Normal', 0, '07:48:00', '17:30:00', 9.50, 0.00, NOW(), NOW()
FROM empleados e
WHERE (e.numero_empleado = '18' OR e.numero_empleado_baja = '18')
ORDER BY e.id DESC
LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Empleados en SQL' AS resultado, 79 AS total;
SELECT 'Asistencias en SQL' AS resultado, 108 AS total;
