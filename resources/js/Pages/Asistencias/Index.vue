<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch, nextTick } from 'vue';

const props = defineProps({
    empleados: {
        type: Array,
        default: () => [],
    },
    asistencias: {
        type: Array,
        default: () => [],
    },
    previewImportacion: {
        type: Object,
        default: null,
    },
});

const fechaActualLocal = () => {
    const hoy = new Date();
    const anio = hoy.getFullYear();
    const mes = String(hoy.getMonth() + 1).padStart(2, '0');
    const dia = String(hoy.getDate()).padStart(2, '0');

    return `${anio}-${mes}-${dia}`;
};

const tiposAsistencia = ['Normal', 'Falta', 'Incapacidad', 'Vacaciones'];
const tabActiva = ref(props.previewImportacion ? 'revision' : 'captura');
const busquedaGlobal = ref('');
const busquedaEmpleadoManual = ref('');
const busquedaRevision = ref('');
const busquedaUltimosRegistros = ref('');
const empleadoRegistrosId = ref('');
const ordenUltimosRegistros = ref('fecha_desc');
const ordenControlEmpleados = ref('num_asc');
const empleadoFaltasExpandido = ref(null);
const fechaSemanaReferencia = ref(props.asistencias?.[0]?.fecha || fechaActualLocal());
const fechaRevisionReferencia = ref(
    props.previewImportacion?.resumen?.fecha_inicio
    || props.previewImportacion?.filas?.[0]?.fecha
    || fechaSemanaReferencia.value,
);
const paginaUltimosRegistros = ref(1);
const paginaRevision = ref(1);
const editando = ref(false);
const asistenciaId = ref(null);
const archivoInput = ref(null);

const REGISTROS_POR_PAGINA = 25;
const DIAS_SEMANA_NOMINA = ['JUEVES', 'VIERNES', 'SABADO', 'DOMINGO', 'LUNES', 'MARTES', 'MIERCOLES'];
const DIAS_SEMANA_CORTOS = ['JUE', 'VIE', 'SAB', 'DOM', 'LUN', 'MAR', 'MIE'];
const empleadosSinHorasExtra = new Set(['8', '9', '22']);
const empleadosSinRetardos = new Set(['14', '76', '78']);

const form = useForm({
    empleado_id: '',
    fecha: new Date().toISOString().split('T')[0],
    tipo_asistencia: 'Normal',
    hora_entrada: '08:00',
    hora_salida: '17:00',
});

const formUpload = useForm({
    archivo_reloj: null,
    fecha_inicio: '',
    fecha_fin: '',
});

const formRevision = useForm({
    filas: [],
});

const crearUid = (fila, index) => {
    const empleado = fila.empleado_id || fila.numero_empleado || 'sin-empleado';
    return `${empleado}-${fila.fecha || 'sin-fecha'}-${fila.estado || 'fila'}-${index}`;
};

const clonarFilasRevision = (filas = []) => {
    return filas.map((fila, index) => ({
        aprobado: fila.aprobado ?? true,
        empleado_id: fila.empleado_id ?? null,
        numero_empleado: fila.numero_empleado ?? '',
        nombre_completo: fila.nombre_completo ?? '',
        csv_numero_empleado: fila.csv_numero_empleado ?? fila.numero_empleado ?? '',
        csv_nombre_completo: fila.csv_nombre_completo ?? fila.nombre_completo ?? '',
        fecha: fila.fecha ?? '',
        tipo_asistencia: fila.tipo_asistencia ?? 'Normal',
        hora_entrada: fila.hora_entrada ? fila.hora_entrada.substring(0, 5) : '',
        hora_salida: fila.hora_salida ? fila.hora_salida.substring(0, 5) : '',
        minutos_tarde: fila.minutos_tarde ?? 0,
        horas_trabajadas: fila.horas_trabajadas ?? 0,
        horas_extra: fila.horas_extra ?? 0,
        estado: fila.estado ?? 'detectada',
        mensaje: fila.mensaje ?? '',
        marcas: fila.marcas ?? 0,
        _uid: crearUid(fila, index),
    }));
};

const filasRevision = ref(clonarFilasRevision(props.previewImportacion?.filas || []));

watch(
    () => props.previewImportacion,
    (preview) => {
        filasRevision.value = clonarFilasRevision(preview?.filas || []);
        fechaRevisionReferencia.value = preview?.resumen?.fecha_inicio
            || preview?.filas?.[0]?.fecha
            || fechaSemanaReferencia.value;
        paginaRevision.value = 1;
        if (filasRevision.value.length > 0) {
            tabActiva.value = 'revision';
        }
    }
);

const crearFechaLocal = (fecha) => {
    if (!fecha) {
        const hoy = new Date();
        return new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());
    }

    const [anio, mes, dia] = String(fecha).substring(0, 10).split('-').map(Number);

    if (!anio || !mes || !dia) {
        const respaldo = new Date(fecha);
        return new Date(respaldo.getFullYear(), respaldo.getMonth(), respaldo.getDate());
    }

    return new Date(anio, mes - 1, dia);
};

const fechaIsoLocal = (fecha) => {
    const anio = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');

    return `${anio}-${mes}-${dia}`;
};

const sumarDiasFecha = (fecha, dias) => {
    const copia = crearFechaLocal(fecha);
    copia.setDate(copia.getDate() + dias);

    return fechaIsoLocal(copia);
};

const inicioSemanaNomina = (fecha) => {
    const base = crearFechaLocal(fecha);
    const diferencia = (base.getDay() - 4 + 7) % 7;
    base.setDate(base.getDate() - diferencia);

    return fechaIsoLocal(base);
};

const fechasSemanaNomina = (fechaReferencia) => {
    const inicio = inicioSemanaNomina(fechaReferencia);

    return DIAS_SEMANA_NOMINA.map((nombre, index) => {
        const iso = sumarDiasFecha(inicio, index);

        return {
            iso,
            nombre,
            corto: DIAS_SEMANA_CORTOS[index],
            diaMes: crearFechaLocal(iso).getDate(),
        };
    });
};

const empleadosPorId = computed(() => {
    return new Map(props.empleados.map((empleado) => [Number(empleado.id), empleado]));
});

const normalizarNumeroEmpleado = (numero) => {
    const texto = String(numero || '').trim();
    const sinCeros = texto.replace(/^0+/, '');
    return sinCeros || texto || '';
};

const numeroEmpleado = (empleado) => normalizarNumeroEmpleado(empleado?.numero_empleado || empleado?.numero_empleado_baja);

const valorNumeroEmpleado = (empleado) => {
    const valor = parseInt(numeroEmpleado(empleado), 10);
    return Number.isFinite(valor) ? valor : Number.MAX_SAFE_INTEGER;
};

const valorNumeroFilaRevision = (fila) => {
    const valor = parseInt(normalizarNumeroEmpleado(fila.numero_empleado || fila.csv_numero_empleado), 10);
    return Number.isFinite(valor) ? valor : Number.MAX_SAFE_INTEGER;
};

const empleadoEnRegla = (empleado, reglas) => reglas.has(numeroEmpleado(empleado));

const esEmpleadoEstudiante = (empleado) => Boolean(empleado?.es_estudiante);

const minutosDesdeHora = (hora) => {
    const [horas, minutos] = String(hora || '').substring(0, 5).split(':').map(Number);

    if (!Number.isFinite(horas) || !Number.isFinite(minutos)) {
        return null;
    }

    return (horas * 60) + minutos;
};

const esSabado = (fecha) => {
    const [anio, mes, dia] = String(fecha || '').substring(0, 10).split('-').map(Number);

    if (!anio || !mes || !dia) {
        return false;
    }

    return new Date(anio, mes - 1, dia).getDay() === 6;
};

const normalizarHorasExtraSabado = (asistencia, empleado) => {
    if (!asistencia || asistencia.tipo_asistencia !== 'Normal' || !esSabado(asistencia.fecha)) {
        return asistencia;
    }

    if (!esEmpleadoEstudiante(empleado) && empleadoEnRegla(empleado, empleadosSinHorasExtra)) {
        return { ...asistencia, horas_extra: 0 };
    }

    const entrada = minutosDesdeHora(asistencia.hora_entrada);
    const salida = minutosDesdeHora(asistencia.hora_salida);

    if (entrada === null || salida === null || salida <= entrada) {
        return asistencia;
    }

    const inicio = Math.max(entrada, 8 * 60);
    const horasExtra = Math.max(0, Math.round((salida - inicio) / 60));

    return {
        ...asistencia,
        horas_extra: horasExtra,
    };
};

const aplicarReglasVisualesAsistencia = (asistencia, empleado) => {
    const asistenciaNormalizada = normalizarHorasExtraSabado(asistencia, empleado);

    if (!asistenciaNormalizada || !esEmpleadoEstudiante(empleado)) {
        return asistenciaNormalizada;
    }

    return {
        ...asistenciaNormalizada,
        minutos_tarde: 0,
        horas_trabajadas: Number(asistenciaNormalizada.horas_trabajadas || 0) + Number(asistenciaNormalizada.horas_extra || 0),
        horas_extra: 0,
    };
};

const compararEmpleados = (a, b, criterio) => {
    if (criterio === 'nombre_desc') {
        return String(b.nombre_completo || '').localeCompare(String(a.nombre_completo || ''), 'es');
    }

    if (criterio === 'num_asc' || criterio === 'num_desc') {
        const diferencia = valorNumeroEmpleado(a) - valorNumeroEmpleado(b);
        return criterio === 'num_asc' ? diferencia : -diferencia;
    }

    return String(a.nombre_completo || '').localeCompare(String(b.nombre_completo || ''), 'es');
};

const ordenarEmpleados = (empleados, criterio) => {
    return [...empleados].sort((a, b) => {
        const comparacion = compararEmpleados(a, b, criterio);

        if (comparacion !== 0) {
            return comparacion;
        }

        return valorNumeroEmpleado(a) - valorNumeroEmpleado(b);
    });
};

const ordenarFilasRevision = (filas) => {
    return [...filas].sort((a, b) => {
        const diferenciaNumero = valorNumeroFilaRevision(a) - valorNumeroFilaRevision(b);

        if (diferenciaNumero !== 0) {
            return diferenciaNumero;
        }

        const diferenciaNombre = String(a.nombre_completo || '').localeCompare(String(b.nombre_completo || ''), 'es');

        if (diferenciaNombre !== 0) {
            return diferenciaNombre;
        }

        const diferenciaFecha = String(a.fecha || '').localeCompare(String(b.fecha || ''));

        if (diferenciaFecha !== 0) {
            return diferenciaFecha;
        }

        return String(a.estado || '').localeCompare(String(b.estado || ''), 'es');
    });
};

const fechasSemanaRegistros = computed(() => fechasSemanaNomina(fechaSemanaReferencia.value));
const fechasSemanaRevision = computed(() => fechasSemanaNomina(fechaRevisionReferencia.value));

const rangoSemanaRegistros = computed(() => {
    const fechas = fechasSemanaRegistros.value;

    return `${formatoFecha(fechas[0]?.iso)} - ${formatoFecha(fechas[6]?.iso)}`;
});

const rangoSemanaRevision = computed(() => {
    const fechas = fechasSemanaRevision.value;

    return `${formatoFecha(fechas[0]?.iso)} - ${formatoFecha(fechas[6]?.iso)}`;
});

const exportarSemanaUrl = computed(() => route('asistencias.exportar-semana', {
    fecha: fechaSemanaReferencia.value,
}));

const coincideEmpleado = (empleado, termino) => {
    if (!termino) return true;

    return String(empleado?.nombre_completo || '').toLowerCase().includes(termino)
        || String(empleado?.numero_empleado || '').toLowerCase().includes(termino)
        || String(empleado?.numero_empleado_baja || '').toLowerCase().includes(termino);
};

const coincideFilaRevision = (fila, termino) => {
    if (!termino) return true;

    return String(fila.numero_empleado || '').toLowerCase().includes(termino)
        || String(fila.csv_numero_empleado || '').toLowerCase().includes(termino)
        || String(fila.nombre_completo || '').toLowerCase().includes(termino)
        || String(fila.csv_nombre_completo || '').toLowerCase().includes(termino)
        || String(fila.fecha || '').toLowerCase().includes(termino)
        || String(fila.estado || '').toLowerCase().includes(termino)
        || String(fila.tipo_asistencia || '').toLowerCase().includes(termino);
};

const asistenciasPorEmpleadoFecha = computed(() => {
    const mapa = new Map();

    props.asistencias.forEach((asistencia) => {
        mapa.set(`${asistencia.empleado_id}|${asistencia.fecha}`, asistencia);
    });

    return mapa;
});

const empleadosMatrizRegistros = computed(() => {
    let resultado = [...props.empleados];

    if (empleadoRegistrosId.value) {
        resultado = resultado.filter((empleado) => Number(empleado.id) === Number(empleadoRegistrosId.value));
    }

    const termino = busquedaUltimosRegistros.value.toLowerCase().trim();
    resultado = resultado.filter((empleado) => coincideEmpleado(empleado, termino));

    const criterio = ['num_asc', 'num_desc', 'nombre_asc', 'nombre_desc'].includes(ordenUltimosRegistros.value)
        ? ordenUltimosRegistros.value
        : 'num_asc';

    return ordenarEmpleados(resultado, criterio);
});

const filasMatrizAsistencias = computed(() => {
    const fechas = fechasSemanaRegistros.value;

    return empleadosMatrizRegistros.value.map((empleado) => {
        const dias = fechas.map((dia) => {
            const asistencia = asistenciasPorEmpleadoFecha.value.get(`${empleado.id}|${dia.iso}`) || null;

            return {
                ...dia,
                asistencia: aplicarReglasVisualesAsistencia(asistencia, empleado),
            };
        });

        return {
            empleado,
            dias,
            totalRegistros: dias.filter((dia) => Boolean(dia.asistencia)).length,
            totalFaltas: dias.filter((dia) => dia.asistencia?.tipo_asistencia === 'Falta').length,
            totalRetardos: dias.reduce((total, dia) => total + Number(dia.asistencia?.minutos_tarde || 0), 0),
            totalNormales: dias.reduce((total, dia) => total + Number(dia.asistencia?.horas_trabajadas || 0), 0),
            totalExtras: dias.reduce((total, dia) => total + Number(dia.asistencia?.horas_extra || 0), 0),
        };
    });
});

const totalRegistrosSemana = computed(() => filasMatrizAsistencias.value.reduce((total, fila) => total + fila.totalRegistros, 0));

const filasMatrizAsistenciasPaginadas = computed(() => {
    const inicio = (paginaUltimosRegistros.value - 1) * REGISTROS_POR_PAGINA;

    return filasMatrizAsistencias.value.slice(inicio, inicio + REGISTROS_POR_PAGINA);
});

const llaveGrupoRevision = (fila) => {
    if (fila.empleado_id) return `empleado-${fila.empleado_id}`;

    return `csv-${fila.csv_numero_empleado || fila.numero_empleado || fila.nombre_completo || fila._uid}`;
};

const crearGrupoRevision = (fila) => ({
    key: llaveGrupoRevision(fila),
    empleado_id: fila.empleado_id || null,
    numero_empleado: fila.numero_empleado || fila.csv_numero_empleado || '',
    nombre_completo: fila.nombre_completo || fila.csv_nombre_completo || 'Sin empleado',
    csv_numero_empleado: fila.csv_numero_empleado || '',
    csv_nombre_completo: fila.csv_nombre_completo || '',
    filas: [],
    dias: [],
});

const filasMatrizRevision = computed(() => {
    const fechas = fechasSemanaRevision.value;
    const fechasSet = new Set(fechas.map((dia) => dia.iso));
    const termino = busquedaRevision.value.toLowerCase().trim();
    const grupos = new Map();

    ordenarFilasRevision(filasRevision.value)
        .filter((fila) => fechasSet.has(fila.fecha))
        .filter((fila) => coincideFilaRevision(fila, termino))
        .forEach((fila) => {
            const llave = llaveGrupoRevision(fila);

            if (!grupos.has(llave)) {
                grupos.set(llave, crearGrupoRevision(fila));
            }

            grupos.get(llave).filas.push(fila);
        });

    return [...grupos.values()].map((grupo) => {
        const porFecha = new Map(grupo.filas.map((fila) => [fila.fecha, fila]));
        const ids = [...new Set(grupo.filas.map((fila) => fila.empleado_id).filter(Boolean))];

        return {
            ...grupo,
            empleado_id: ids.length === 1 ? ids[0] : null,
            dias: fechas.map((dia) => ({
                ...dia,
                fila: porFecha.get(dia.iso) || null,
            })),
        };
    }).sort((a, b) => {
        const numeroA = parseInt(normalizarNumeroEmpleado(a.numero_empleado || a.csv_numero_empleado), 10);
        const numeroB = parseInt(normalizarNumeroEmpleado(b.numero_empleado || b.csv_numero_empleado), 10);
        const valorA = Number.isFinite(numeroA) ? numeroA : Number.MAX_SAFE_INTEGER;
        const valorB = Number.isFinite(numeroB) ? numeroB : Number.MAX_SAFE_INTEGER;

        if (valorA !== valorB) return valorA - valorB;

        return String(a.nombre_completo || '').localeCompare(String(b.nombre_completo || ''), 'es');
    });
});

const filasMatrizRevisionPaginadas = computed(() => {
    const inicio = (paginaRevision.value - 1) * REGISTROS_POR_PAGINA;

    return filasMatrizRevision.value.slice(inicio, inicio + REGISTROS_POR_PAGINA);
});

const totalPaginasUltimosRegistros = computed(() => Math.max(1, Math.ceil(filasMatrizAsistencias.value.length / REGISTROS_POR_PAGINA)));

const empleadosFiltradosGlobal = computed(() => {
    let resultado = [...props.empleados];

    if (busquedaGlobal.value) {
        const term = busquedaGlobal.value.toLowerCase();
        resultado = resultado.filter((empleado) => {
            return empleado.nombre_completo.toLowerCase().includes(term)
                || (empleado.numero_empleado && String(empleado.numero_empleado).toLowerCase().includes(term));
        });
    }

    return ordenarEmpleados(resultado, ordenControlEmpleados.value);
});

const etiquetaEmpleado = (empleado) => {
    return `${empleado.numero_empleado ? '#' + empleado.numero_empleado + ' - ' : ''}${empleado.nombre_completo}`;
};

const empleadosOrdenadosFiltro = computed(() => ordenarEmpleados(props.empleados, 'num_asc'));

const empleadoRegistrosSeleccionado = computed(() => {
    if (!empleadoRegistrosId.value) return null;
    return props.empleados.find((empleado) => Number(empleado.id) === Number(empleadoRegistrosId.value));
});

const empleadosFiltradosManual = computed(() => {
    const term = busquedaEmpleadoManual.value.toLowerCase().trim();
    let resultado = props.empleados;

    if (term) {
        resultado = props.empleados.filter((empleado) => {
            return empleado.nombre_completo.toLowerCase().includes(term)
                || (empleado.numero_empleado && String(empleado.numero_empleado).toLowerCase().includes(term));
        });
    }

    const limitados = ordenarEmpleados(resultado, 'num_asc').slice(0, 30);
    const seleccionado = props.empleados.find((empleado) => Number(empleado.id) === Number(form.empleado_id));

    if (seleccionado && !limitados.some((empleado) => Number(empleado.id) === Number(seleccionado.id))) {
        return [seleccionado, ...limitados];
    }

    return limitados;
});

const empleadoSeleccionado = computed(() => {
    if (!form.empleado_id) return null;
    return props.empleados.find((empleado) => Number(empleado.id) === Number(form.empleado_id));
});

const sincronizarBusquedaManual = () => {
    if (empleadoSeleccionado.value) {
        busquedaEmpleadoManual.value = etiquetaEmpleado(empleadoSeleccionado.value);
    }
};

const totalPaginasRevision = computed(() => Math.max(1, Math.ceil(filasMatrizRevision.value.length / REGISTROS_POR_PAGINA)));

const resumenRevision = computed(() => {
    const filas = filasRevision.value;

    return {
        total: filas.length,
        seleccionadas: filas.filter((fila) => fila.aprobado && fila.empleado_id).length,
        sinRegistro: filas.filter((fila) => fila.estado === 'sin_registro').length,
        incompletas: filas.filter((fila) => fila.estado === 'incompleta').length,
        noEncontradas: filas.filter((fila) => fila.estado === 'no_encontrado').length,
        actualiza: filas.filter((fila) => fila.estado === 'actualiza').length,
    };
});

const formatoFecha = (fecha) => {
    if (!fecha) return '--';
    return new Date(`${fecha}T00:00:00`).toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const formatoHora = (hora) => {
    return hora ? hora.substring(0, 5) : '--';
};

const claseTipo = (tipo) => {
    if (tipo === 'Falta') return 'border-rose-200 bg-rose-50 text-rose-700';
    if (tipo === 'Incapacidad') return 'border-amber-200 bg-amber-50 text-amber-700';
    if (tipo === 'Vacaciones') return 'border-teal-200 bg-teal-50 text-teal-700';
    return 'border-blue-200 bg-blue-50 text-blue-700';
};

const claseEstadoRevision = (estado) => {
    if (estado === 'sin_registro') return 'border-rose-200 bg-rose-50 text-rose-700';
    if (estado === 'incompleta') return 'border-orange-200 bg-orange-50 text-orange-700';
    if (estado === 'no_encontrado') return 'border-slate-300 bg-slate-100 text-slate-700';
    if (estado === 'actualiza') return 'border-amber-200 bg-amber-50 text-amber-700';
    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
};

const textoEstadoRevision = (estado) => {
    if (estado === 'sin_registro') return 'Sin registro';
    if (estado === 'incompleta') return 'Incompleta';
    if (estado === 'no_encontrado') return 'Sin empleado';
    if (estado === 'actualiza') return 'Actualiza';
    return 'Detectada';
};

const rangoPagina = (pagina, total) => {
    if (total === 0) {
        return '0 de 0';
    }

    const inicio = ((pagina - 1) * REGISTROS_POR_PAGINA) + 1;
    const fin = Math.min(total, pagina * REGISTROS_POR_PAGINA);

    return `${inicio}-${fin} de ${total}`;
};

const cambiarPaginaUltimos = (delta) => {
    paginaUltimosRegistros.value = Math.min(
        totalPaginasUltimosRegistros.value,
        Math.max(1, paginaUltimosRegistros.value + delta),
    );
};

const cambiarPaginaRevision = (delta) => {
    paginaRevision.value = Math.min(
        totalPaginasRevision.value,
        Math.max(1, paginaRevision.value + delta),
    );
};

watch([busquedaUltimosRegistros, empleadoRegistrosId, ordenUltimosRegistros, fechaSemanaReferencia], () => {
    paginaUltimosRegistros.value = 1;
});

watch(filasMatrizAsistencias, () => {
    if (paginaUltimosRegistros.value > totalPaginasUltimosRegistros.value) {
        paginaUltimosRegistros.value = totalPaginasUltimosRegistros.value;
    }
});

watch([busquedaRevision, fechaRevisionReferencia], () => {
    paginaRevision.value = 1;
});

watch(filasMatrizRevision, () => {
    if (paginaRevision.value > totalPaginasRevision.value) {
        paginaRevision.value = totalPaginasRevision.value;
    }
});

const seleccionarArchivo = (event) => {
    formUpload.archivo_reloj = event.target.files[0] || null;
};

const subirArchivo = () => {
    if (!formUpload.archivo_reloj) {
        alert('Selecciona un archivo CSV antes de analizar.');
        return;
    }

    formUpload.post(route('asistencias.importar'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            tabActiva.value = 'revision';
            formUpload.reset('archivo_reloj');
            if (archivoInput.value) {
                archivoInput.value.value = null;
            }
        },
    });
};

const guardarAsistencia = () => {
    if (editando.value) {
        form.put(route('asistencias.update', asistenciaId.value), {
            onSuccess: () => cancelarEdicion(),
        });
        return;
    }

    form.post(route('asistencias.store'), {
        onSuccess: () => {
            form.reset('hora_entrada', 'hora_salida', 'fecha', 'tipo_asistencia');
        },
    });
};

const editarAsistencia = (asistencia) => {
    editando.value = true;
    asistenciaId.value = asistencia.id;
    form.empleado_id = asistencia.empleado_id;
    const empleado = props.empleados.find((item) => Number(item.id) === Number(asistencia.empleado_id));
    busquedaEmpleadoManual.value = empleado ? etiquetaEmpleado(empleado) : '';
    form.fecha = asistencia.fecha;
    form.tipo_asistencia = asistencia.tipo_asistencia || 'Normal';
    form.hora_entrada = asistencia.hora_entrada ? asistencia.hora_entrada.substring(0, 5) : '08:00';
    form.hora_salida = asistencia.hora_salida ? asistencia.hora_salida.substring(0, 5) : '17:00';
    tabActiva.value = 'captura';
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const cancelarEdicion = () => {
    editando.value = false;
    asistenciaId.value = null;
    form.reset('hora_entrada', 'hora_salida', 'tipo_asistencia');
};

const enfocarUltimosRegistros = () => {
    nextTick(() => {
        document.getElementById('ultimos-registros')?.scrollIntoView({
            behavior: 'smooth',
            block: 'start',
        });
    });
};

const verRegistrosEmpleadoSeleccionado = () => {
    if (!form.empleado_id) {
        return;
    }

    empleadoRegistrosId.value = form.empleado_id;
    busquedaUltimosRegistros.value = '';
    paginaUltimosRegistros.value = 1;
    enfocarUltimosRegistros();
};

const limpiarFiltrosRegistros = () => {
    empleadoRegistrosId.value = '';
    busquedaUltimosRegistros.value = '';
    ordenUltimosRegistros.value = 'fecha_desc';
    paginaUltimosRegistros.value = 1;
};

const cambiarSemana = (destino, delta) => {
    const referencia = destino === 'revision' ? fechaRevisionReferencia.value : fechaSemanaReferencia.value;
    const nuevaFecha = sumarDiasFecha(inicioSemanaNomina(referencia), delta * 7);

    if (destino === 'revision') {
        fechaRevisionReferencia.value = nuevaFecha;
        return;
    }

    fechaSemanaReferencia.value = nuevaFecha;
};

const irSemanaActual = (destino) => {
    const hoy = fechaIsoLocal(new Date());

    if (destino === 'revision') {
        fechaRevisionReferencia.value = hoy;
        return;
    }

    fechaSemanaReferencia.value = hoy;
};

const formatoHoraMatriz = (hora) => hora ? hora.substring(0, 5) : '';

const formatoHorasResumen = (valor) => {
    const numero = Number(valor || 0);

    if (numero === 0) return '0';
    if (Number.isInteger(numero)) return String(numero);

    return numero.toFixed(2).replace(/\.?0+$/, '');
};

const etiquetaTipoCorta = (tipo) => {
    if (tipo === 'Falta') return 'FALTA';
    if (tipo === 'Incapacidad') return 'INCAP.';
    if (tipo === 'Vacaciones') return 'VAC.';
    return 'NORMAL';
};

const claseCeldaAsistencia = (registro) => {
    if (!registro) return 'asistencia-cell-empty';
    if (registro.tipo_asistencia === 'Falta') return 'asistencia-cell-falta';
    if (registro.tipo_asistencia === 'Incapacidad') return 'asistencia-cell-incapacidad';
    if (registro.tipo_asistencia === 'Vacaciones') return 'asistencia-cell-vacaciones';
    if (Number(registro.minutos_tarde || 0) >= 30) return 'asistencia-cell-retardo';

    return 'asistencia-cell-normal';
};

const claseFilaRevision = (fila) => {
    if (!fila) return 'asistencia-cell-empty';
    if (!fila.aprobado) return 'asistencia-cell-omitida';
    if (fila.tipo_asistencia === 'Falta') return 'asistencia-cell-falta';
    if (fila.tipo_asistencia === 'Incapacidad') return 'asistencia-cell-incapacidad';
    if (fila.tipo_asistencia === 'Vacaciones') return 'asistencia-cell-vacaciones';
    if (fila.estado === 'incompleta') return 'asistencia-cell-incompleta';

    return 'asistencia-cell-normal';
};

const sincronizarEmpleadoGrupoRevision = (grupo, empleadoId) => {
    grupo.filas.forEach((fila) => {
        fila.empleado_id = empleadoId ? Number(empleadoId) : null;
        sincronizarEmpleadoFila(fila);
    });
};

const eliminarAsistencia = (id) => {
    if (confirm('Estas seguro de eliminar este registro? Puede afectar calculos ya generados.')) {
        router.delete(route('asistencias.destroy', id));
    }
};

const sincronizarEmpleadoFila = (fila) => {
    const empleado = empleadosPorId.value.get(Number(fila.empleado_id));

    if (!empleado) {
        fila.numero_empleado = '';
        fila.nombre_completo = '';
        fila.aprobado = false;
        return;
    }

    fila.numero_empleado = empleado.numero_empleado || '';
    fila.nombre_completo = empleado.nombre_completo;
    fila.aprobado = true;
    calcularHorasFilaRevision(fila);
};

const aplicarReglasFilaRevision = (fila) => {
    const empleado = empleadosPorId.value.get(Number(fila.empleado_id));

    if (!empleado) {
        return;
    }

    if (esEmpleadoEstudiante(empleado)) {
        fila.horas_trabajadas = Number(fila.horas_trabajadas || 0) + Number(fila.horas_extra || 0);
        fila.horas_extra = 0;
        fila.minutos_tarde = 0;
        return;
    }

    if (empleadoEnRegla(empleado, empleadosSinRetardos)) {
        fila.minutos_tarde = 0;
    }

    if (empleadoEnRegla(empleado, empleadosSinHorasExtra)) {
        fila.horas_extra = 0;
    }
};

const calcularHorasFilaRevision = (fila) => {
    if (fila.tipo_asistencia !== 'Normal' || !fila.fecha || !fila.hora_entrada || !fila.hora_salida) {
        fila.minutos_tarde = 0;
        fila.horas_trabajadas = 0;
        fila.horas_extra = 0;
        return;
    }

    const entrada = new Date(`${fila.fecha}T${fila.hora_entrada}:00`);
    const salida = new Date(`${fila.fecha}T${fila.hora_salida}:00`);
    const horaOficial = new Date(`${fila.fecha}T08:00:00`);

    if (Number.isNaN(entrada.getTime()) || Number.isNaN(salida.getTime()) || salida <= entrada) {
        fila.minutos_tarde = 0;
        fila.horas_trabajadas = 0;
        fila.horas_extra = 0;
        return;
    }

    if (fila.estado === 'incompleta') {
        fila.estado = 'detectada';
        fila.mensaje = 'Horario completado manualmente en revision.';
        fila.aprobado = Boolean(fila.empleado_id);
    }

    fila.minutos_tarde = entrada > horaOficial ? Math.round((entrada - horaOficial) / 60000) : 0;

    if (entrada.getDay() === 6) {
        const inicioSabado = entrada < horaOficial ? horaOficial : entrada;
        fila.horas_trabajadas = 0;
        fila.horas_extra = Math.max(0, Math.round((salida - inicioSabado) / 3600000));
        aplicarReglasFilaRevision(fila);
        return;
    }

    const limiteNormal = new Date(`${fila.fecha}T17:30:00`);
    const inicioJornada = entrada < horaOficial || fila.minutos_tarde < 30 ? horaOficial : entrada;

    if (salida > limiteNormal) {
        fila.horas_trabajadas = Math.max(0, (limiteNormal - inicioJornada) / 3600000);
        fila.horas_extra = Math.max(0, Math.floor((salida - limiteNormal) / 3600000));
        aplicarReglasFilaRevision(fila);
        return;
    }

    fila.horas_trabajadas = Math.max(0, (salida - inicioJornada) / 3600000);
    fila.horas_extra = 0;
    aplicarReglasFilaRevision(fila);
};

const prepararCambioTipo = (fila) => {
    if (fila.tipo_asistencia !== 'Normal') {
        fila.hora_entrada = '';
        fila.hora_salida = '';
        calcularHorasFilaRevision(fila);
        return;
    }

    fila.hora_entrada = fila.hora_entrada || '08:00';
    fila.hora_salida = fila.hora_salida || '17:00';
    calcularHorasFilaRevision(fila);
};

const descartarFilaRevision = (uid) => {
    filasRevision.value = filasRevision.value.filter((fila) => fila._uid !== uid);
};

const seleccionarRevision = (seleccionar) => {
    filasRevision.value.forEach((fila) => {
        fila.aprobado = seleccionar && Boolean(fila.empleado_id) && fila.estado !== 'incompleta';
    });
};

const aprobarRevision = () => {
    const incompletasAprobadas = filasRevision.value.filter((fila) => {
        return fila.aprobado
            && fila.empleado_id
            && fila.tipo_asistencia === 'Normal'
            && (!fila.hora_entrada || !fila.hora_salida);
    });

    if (incompletasAprobadas.length > 0) {
        alert('Hay marcas incompletas seleccionadas. Captura entrada y salida o quita la palomita antes de aprobar.');
        return;
    }

    const filas = filasRevision.value
        .filter((fila) => fila.aprobado && fila.empleado_id)
        .map((fila) => ({
            aprobado: true,
            empleado_id: fila.empleado_id,
            fecha: fila.fecha,
            tipo_asistencia: fila.tipo_asistencia,
            hora_entrada: fila.tipo_asistencia === 'Normal' ? (fila.hora_entrada || null) : null,
            hora_salida: fila.tipo_asistencia === 'Normal' ? (fila.hora_salida || null) : null,
        }));

    if (filas.length === 0) {
        alert('No hay filas seleccionadas para aprobar.');
        return;
    }

    formRevision.filas = filas;
    formRevision.post(route('asistencias.importar.aprobar'), {
        preserveScroll: true,
        onSuccess: () => {
            filasRevision.value = [];
            tabActiva.value = 'captura';
        },
    });
};

const descartarRevision = () => {
    if (!confirm('Descartar la revision actual del CSV?')) {
        return;
    }

    router.delete(route('asistencias.importar.descartar'), {
        preserveScroll: true,
        onSuccess: () => {
            filasRevision.value = [];
            tabActiva.value = 'captura';
        },
    });
};

const toggleDetalleFaltas = (empleadoId) => {
    empleadoFaltasExpandido.value = empleadoFaltasExpandido.value === empleadoId ? null : empleadoId;
};

const fechasFaltasEmpleado = (empleado) => empleado.fechas_faltas || [];
</script>

<template>
    <Head title="Control de Asistencias" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                <Link :href="route('dashboard')" class="icon-button" aria-label="Volver al panel">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19 3 12m0 0 7-7m-7 7h18" />
                    </svg>
                </Link>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-teal-700">Registro y Control</p>
                    <h2 class="text-xl font-semibold text-slate-950 sm:text-2xl">Jornadas e Incidencias</h2>
                </div>
            </div>
        </template>

        <div class="page-shell">
            <div class="content-wrap space-y-6">
                <div class="tab-strip sm:grid-cols-2 md:grid-cols-5">
                    <button
                        @click="tabActiva = 'captura'"
                        :class="tabActiva === 'captura' ? 'bg-white text-teal-700 shadow font-bold' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-800'"
                        class="tab-button"
                        type="button"
                    >
                        <i class="ti ti-clock-plus" aria-hidden="true"></i>
                        Captura y Reloj
                    </button>
                    <button
                        @click="tabActiva = 'revision'"
                        :class="tabActiva === 'revision' ? 'bg-white text-blue-700 shadow font-bold' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-800'"
                        class="tab-button"
                        type="button"
                    >
                        <i class="ti ti-file-search" aria-hidden="true"></i>
                        Revision CSV
                        <span v-if="filasRevision.length" class="ml-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">
                            {{ filasRevision.length }}
                        </span>
                    </button>
                    <button
                        @click="tabActiva = 'vacaciones'"
                        :class="tabActiva === 'vacaciones' ? 'bg-white text-teal-700 shadow font-bold' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-800'"
                        class="tab-button"
                        type="button"
                    >
                        <i class="ti ti-beach" aria-hidden="true"></i>
                        Control Vacaciones
                    </button>
                    <button
                        @click="tabActiva = 'faltas'"
                        :class="tabActiva === 'faltas' ? 'bg-white text-rose-700 shadow font-bold' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-800'"
                        class="tab-button"
                        type="button"
                    >
                        <i class="ti ti-user-x" aria-hidden="true"></i>
                        Control Faltas
                    </button>
                    <Link
                        :href="route('asistencias.alumnos-horas')"
                        class="tab-button text-slate-600 hover:bg-slate-200 hover:text-slate-800"
                    >
                        <i class="ti ti-school" aria-hidden="true"></i>
                        Horas Alumnos
                    </Link>
                </div>

                <div v-show="tabActiva === 'captura'" class="space-y-8 animate-fade-in">
                    <section class="app-panel border border-emerald-200 bg-emerald-50/50">
                        <form @submit.prevent="subirArchivo" class="grid gap-4 p-5 sm:p-6 lg:grid-cols-[auto_1.5fr_1fr_1fr_auto] lg:items-end">
                            <div class="hidden h-14 w-14 items-center justify-center rounded-xl border border-emerald-200 bg-white text-2xl text-emerald-600 shadow-sm lg:flex">
                                <i class="ti ti-file-spreadsheet" aria-hidden="true"></i>
                            </div>
                            <div>
                                <label class="field-label">Archivo CSV del reloj</label>
                                <input
                                    ref="archivoInput"
                                    type="file"
                                    accept=".csv,.txt"
                                    :disabled="formUpload.processing"
                                    @change="seleccionarArchivo"
                                    class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-emerald-700"
                                />
                                <div v-if="formUpload.errors.archivo_reloj" class="mt-2 text-sm font-medium text-rose-600">
                                    {{ formUpload.errors.archivo_reloj }}
                                </div>
                            </div>

                            <div>
                                <label class="field-label">Inicio semana</label>
                                <input v-model="formUpload.fecha_inicio" type="date" class="field-input-soft" />
                            </div>

                            <div>
                                <label class="field-label">Fin semana</label>
                                <input v-model="formUpload.fecha_fin" type="date" class="field-input-soft" />
                                <div v-if="formUpload.errors.fecha_fin" class="mt-2 text-sm font-medium text-rose-600">
                                    {{ formUpload.errors.fecha_fin }}
                                </div>
                            </div>

                            <button type="submit" :disabled="formUpload.processing" class="btn-accent w-full lg:w-auto">
                                <svg v-if="!formUpload.processing" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1M12 4v12m0-12 4 4m-4-4-4 4" />
                                </svg>
                                {{ formUpload.processing ? 'Analizando...' : 'Analizar CSV' }}
                            </button>

                            <progress v-if="formUpload.progress" :value="formUpload.progress.percentage" max="100" class="lg:col-span-5 w-full"></progress>
                        </form>
                    </section>

                    <section class="app-panel" :class="editando ? 'ring-2 ring-amber-400/70' : ''">
                        <div class="panel-header border-b border-slate-100">
                            <div class="flex items-start gap-3">
                                <div :class="editando ? 'soft-icon-amber' : 'soft-icon-blue'">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="panel-title">{{ editando ? 'Editar registro' : 'Capturar nueva asistencia / incidencia' }}</h3>
                                    <p class="panel-subtitle">Selecciona empleado, fecha y tipo de jornada.</p>
                                </div>
                            </div>
                            <button v-if="editando" @click="cancelarEdicion" class="btn-secondary w-full sm:w-auto" type="button">Cancelar edicion</button>
                        </div>

                        <div class="bg-slate-50/50 p-5 sm:p-6">
                            <div class="mb-6 grid gap-3 lg:grid-cols-[1fr_1.2fr]">
                                <div>
                                    <label class="field-label text-base">Buscar empleado <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                            <i class="ti ti-search" aria-hidden="true"></i>
                                        </div>
                                        <input
                                            v-model="busquedaEmpleadoManual"
                                            type="text"
                                            :disabled="editando"
                                            class="field-input-soft pl-9 text-base font-semibold text-slate-800"
                                            placeholder="Numero o nombre..."
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="field-label text-base">Selecciona un empleado <span class="text-rose-500">*</span></label>
                                    <select v-model="form.empleado_id" @change="sincronizarBusquedaManual" required :disabled="editando" class="field-input-soft text-base font-bold text-slate-800">
                                        <option value="" disabled>Selecciona un trabajador...</option>
                                        <option v-for="empleado in empleadosFiltradosManual" :key="empleado.id" :value="empleado.id">
                                            {{ etiquetaEmpleado(empleado) }}
                                        </option>
                                    </select>
                                    <p v-if="busquedaEmpleadoManual && empleadosFiltradosManual.length === 0" class="mt-2 text-sm font-semibold text-rose-600">
                                        Sin resultados para esa busqueda.
                                    </p>
                                </div>
                            </div>

                            <div v-if="empleadoSeleccionado" class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 xl:grid-cols-5">
                                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                    <p class="text-xs font-semibold uppercase text-slate-500">Antiguedad</p>
                                    <p class="text-lg font-bold text-slate-800">{{ empleadoSeleccionado.antiguedad_anios }} anio(s)</p>
                                </div>
                                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                    <p class="text-xs font-semibold uppercase text-slate-500">Vacaciones totales</p>
                                    <p class="text-lg font-bold text-teal-700">
                                        {{ empleadoSeleccionado.fecha_ingreso ? empleadoSeleccionado.dias_vacaciones_totales + ' dias' : 'Falta fecha ing.' }}
                                    </p>
                                </div>
                                <div class="rounded-lg border border-slate-100 bg-slate-50 p-3">
                                    <p class="text-xs font-semibold uppercase text-slate-500">Vacaciones restantes</p>
                                    <p class="text-lg font-bold" :class="empleadoSeleccionado.dias_vacaciones_restantes > 0 ? 'text-emerald-600' : 'text-slate-400'">
                                        {{ empleadoSeleccionado.fecha_ingreso ? empleadoSeleccionado.dias_vacaciones_restantes + ' disp.' : '--' }}
                                    </p>
                                </div>
                                <div class="rounded-lg border border-rose-100 bg-rose-50 p-3">
                                    <p class="text-xs font-semibold uppercase text-rose-600">Faltas totales</p>
                                    <p class="text-lg font-bold text-rose-700">{{ empleadoSeleccionado.dias_faltas_totales }} faltas</p>
                                </div>
                                <div class="flex flex-col justify-between gap-3 rounded-lg border border-blue-100 bg-blue-50 p-3">
                                    <div>
                                        <p class="text-xs font-semibold uppercase text-blue-600">Historial</p>
                                        <p class="text-sm font-semibold text-blue-900">{{ numeroEmpleado(empleadoSeleccionado) ? '#' + numeroEmpleado(empleadoSeleccionado) : 'Empleado seleccionado' }}</p>
                                    </div>
                                    <button @click="verRegistrosEmpleadoSeleccionado" type="button" class="btn-secondary justify-center text-xs">
                                        <i class="ti ti-list-search" aria-hidden="true"></i>
                                        Ver registros
                                    </button>
                                </div>
                            </div>

                            <form @submit.prevent="guardarAsistencia" class="grid grid-cols-1 items-end gap-5 md:grid-cols-4">
                                <div>
                                    <label class="field-label">Fecha <span class="text-rose-500">*</span></label>
                                    <input v-model="form.fecha" type="date" required class="field-input-soft" />
                                </div>

                                <div>
                                    <label class="field-label">Tipo <span class="text-rose-500">*</span></label>
                                    <select v-model="form.tipo_asistencia" required class="field-input-soft">
                                        <option value="Normal">Normal (Asistencia)</option>
                                        <option value="Falta">Falta Injustificada</option>
                                        <option value="Incapacidad">Incapacidad (60%)</option>
                                        <option value="Vacaciones">Vacaciones (+25% Prima)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="field-label" :class="form.tipo_asistencia !== 'Normal' ? 'text-slate-400' : ''">Entrada</label>
                                    <input
                                        v-model="form.hora_entrada"
                                        type="time"
                                        :required="form.tipo_asistencia === 'Normal'"
                                        :disabled="form.tipo_asistencia !== 'Normal'"
                                        class="field-input-soft"
                                        :class="form.tipo_asistencia !== 'Normal' ? 'cursor-not-allowed opacity-50' : ''"
                                    />
                                </div>

                                <div>
                                    <label class="field-label" :class="form.tipo_asistencia !== 'Normal' ? 'text-slate-400' : ''">Salida</label>
                                    <input
                                        v-model="form.hora_salida"
                                        type="time"
                                        :required="form.tipo_asistencia === 'Normal'"
                                        :disabled="form.tipo_asistencia !== 'Normal'"
                                        class="field-input-soft"
                                        :class="form.tipo_asistencia !== 'Normal' ? 'cursor-not-allowed opacity-50' : ''"
                                    />
                                </div>

                                <div class="flex justify-stretch md:col-span-4 sm:justify-end">
                                    <button type="submit" :disabled="form.processing" :class="editando ? 'btn-warning w-full sm:w-auto' : 'btn-accent w-full sm:w-auto'">
                                        <svg v-if="!form.processing" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7" />
                                        </svg>
                                        {{ form.processing ? 'Procesando...' : (editando ? 'Actualizar registro' : 'Guardar asistencia') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <section id="ultimos-registros" class="app-panel scroll-mt-6">
                        <div class="border-b border-slate-200/80 bg-gradient-to-b from-white to-slate-50/80 px-5 py-5 sm:px-6">
                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                <div class="flex min-w-0 items-start gap-3">
                                    <div class="soft-icon-teal">
                                        <i class="ti ti-table-options text-xl" aria-hidden="true"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="panel-title">Asistencias por semana</h3>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs font-bold">
                                            <span class="rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-teal-700">
                                                {{ totalRegistrosSemana }} registros
                                            </span>
                                            <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-blue-700">
                                                {{ filasMatrizAsistencias.length }} empleados
                                            </span>
                                            <span v-if="empleadoRegistrosSeleccionado" class="max-w-full truncate rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-amber-700">
                                                {{ etiquetaEmpleado(empleadoRegistrosSeleccionado) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex w-full flex-col gap-2 sm:flex-row sm:items-end xl:w-auto xl:justify-end">
                                    <div class="grid w-full grid-cols-[2.75rem_1fr_3.25rem_2.75rem] gap-2 rounded-xl border border-slate-200 bg-white p-2 shadow-sm sm:w-[34rem]">
                                        <button type="button" class="btn-secondary h-11 px-0 text-xs" @click="cambiarSemana('registros', -1)">
                                            <i class="ti ti-chevron-left" aria-hidden="true"></i>
                                        </button>
                                        <label class="block min-w-0">
                                            <span class="field-label mb-1">Semana</span>
                                            <input v-model="fechaSemanaReferencia" type="date" class="field-input-soft h-11" />
                                        </label>
                                        <button type="button" class="btn-secondary h-11 px-0 text-xs" @click="irSemanaActual('registros')">
                                            Hoy
                                        </button>
                                        <button type="button" class="btn-secondary h-11 px-0 text-xs" @click="cambiarSemana('registros', 1)">
                                            <i class="ti ti-chevron-right" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                    <a :href="exportarSemanaUrl" class="btn-accent h-11 w-full justify-center text-xs sm:w-auto">
                                        <i class="ti ti-file-spreadsheet" aria-hidden="true"></i>
                                        Exportar Excel
                                    </a>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(16rem,1fr)_14rem_minmax(16rem,1fr)_auto] lg:items-end">
                                <label class="block">
                                    <span class="field-label mb-1">Empleado</span>
                                    <select v-model="empleadoRegistrosId" class="field-input-soft">
                                        <option value="">Todos los empleados</option>
                                        <option v-for="empleado in empleadosOrdenadosFiltro" :key="empleado.id" :value="empleado.id">
                                            {{ etiquetaEmpleado(empleado) }}
                                        </option>
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="field-label mb-1">Ordenar por</span>
                                    <select v-model="ordenUltimosRegistros" class="field-input-soft">
                                        <option value="fecha_desc">Fecha reciente</option>
                                        <option value="num_asc">No. empleado menor</option>
                                        <option value="num_desc">No. empleado mayor</option>
                                        <option value="nombre_asc">Nombre A - Z</option>
                                        <option value="nombre_desc">Nombre Z - A</option>
                                    </select>
                                </label>
                                <div>
                                    <span class="field-label mb-1 block">Buscar</span>
                                    <div class="relative">
                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                            <i class="ti ti-search" aria-hidden="true"></i>
                                        </div>
                                        <input
                                            v-model="busquedaUltimosRegistros"
                                            type="text"
                                            class="field-input-soft pl-9"
                                            placeholder="Buscar nombre o numero..."
                                        />
                                    </div>
                                </div>
                                <button
                                    v-if="empleadoRegistrosId || busquedaUltimosRegistros || ordenUltimosRegistros !== 'fecha_desc'"
                                    type="button"
                                    class="btn-secondary h-11 w-full justify-center text-xs lg:w-auto"
                                    @click="limpiarFiltrosRegistros"
                                >
                                    <i class="ti ti-filter-x" aria-hidden="true"></i>
                                    Limpiar
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1 border-b border-slate-100 bg-slate-50/70 px-5 py-3 text-sm font-bold text-slate-700 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <span>Semana del <span class="text-teal-700">{{ rangoSemanaRegistros }}</span></span>
                            <span class="text-xs text-slate-500">Página {{ paginaUltimosRegistros }} de {{ totalPaginasUltimosRegistros }}</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="asistencia-week-table">
                                <thead>
                                    <tr class="asistencia-title-row">
                                        <th colspan="13">LUGARTH - PROMATEC 2026 - NOMINA PACHUCA</th>
                                    </tr>
                                    <tr>
                                        <th class="w-14">No.</th>
                                        <th class="min-w-72">Nombre</th>
                                        <th v-for="dia in fechasSemanaRegistros" :key="dia.iso" class="min-w-40 text-center">
                                            {{ dia.nombre }} {{ dia.diaMes }}
                                        </th>
                                        <th class="text-center">Reg.</th>
                                        <th class="text-center">Faltas</th>
                                        <th class="text-center">H.E.</th>
                                        <th class="text-center">Ret.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="fila in filasMatrizAsistenciasPaginadas" :key="fila.empleado.id">
                                        <td class="text-center font-black text-slate-700">{{ numeroEmpleado(fila.empleado) || 'S/N' }}</td>
                                        <td>
                                            <div class="font-black uppercase text-slate-950">{{ fila.empleado.nombre_completo }}</div>
                                            <div class="mt-1 text-[10px] font-bold uppercase tracking-wide text-slate-400">Firma</div>
                                        </td>
                                        <td
                                            v-for="dia in fila.dias"
                                            :key="`${fila.empleado.id}-${dia.iso}`"
                                            class="asistencia-day-cell"
                                            :class="claseCeldaAsistencia(dia.asistencia)"
                                        >
                                            <template v-if="dia.asistencia">
                                                <div class="cell-main">
                                                    <template v-if="dia.asistencia.tipo_asistencia === 'Normal'">
                                                        {{ formatoHoraMatriz(dia.asistencia.hora_entrada) || '--' }}
                                                        <span class="text-slate-400">/</span>
                                                        {{ formatoHoraMatriz(dia.asistencia.hora_salida) || '--' }}
                                                    </template>
                                                    <template v-else>
                                                        {{ etiquetaTipoCorta(dia.asistencia.tipo_asistencia) }}
                                                    </template>
                                                </div>
                                                <div class="cell-meta">
                                                    <span>H.E. {{ formatoHorasResumen(dia.asistencia.horas_extra) }}</span>
                                                    <span>Ret. {{ Number(dia.asistencia.minutos_tarde || 0) }}</span>
                                                </div>
                                                <div class="cell-actions">
                                                    <button @click="editarAsistencia(dia.asistencia)" type="button" title="Editar">
                                                        <i class="ti ti-pencil" aria-hidden="true"></i>
                                                    </button>
                                                    <button @click="eliminarAsistencia(dia.asistencia.id)" type="button" title="Eliminar">
                                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </template>
                                            <span v-else class="cell-empty">--</span>
                                        </td>
                                        <td class="text-center font-black">{{ fila.totalRegistros }}</td>
                                        <td class="text-center font-black" :class="fila.totalFaltas > 0 ? 'text-rose-600' : 'text-slate-400'">{{ fila.totalFaltas }}</td>
                                        <td class="text-center font-black text-blue-700">{{ formatoHorasResumen(fila.totalExtras) }}</td>
                                        <td class="text-center font-black" :class="fila.totalRetardos >= 30 ? 'text-orange-600' : 'text-slate-400'">{{ fila.totalRetardos }}</td>
                                    </tr>
                                    <tr v-if="filasMatrizAsistencias.length === 0">
                                        <td colspan="13" class="empty-state">No se encontraron trabajadores para esta semana.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="filasMatrizAsistencias.length > 0" class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 text-sm font-semibold text-slate-600 sm:flex-row sm:items-center sm:justify-between">
                            <span>Mostrando {{ rangoPagina(paginaUltimosRegistros, filasMatrizAsistencias.length) }}</span>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="btn-secondary text-xs"
                                    :disabled="paginaUltimosRegistros === 1"
                                    @click="cambiarPaginaUltimos(-1)"
                                >
                                    <i class="ti ti-chevron-left" aria-hidden="true"></i>
                                    Anterior
                                </button>
                                <span class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-black text-slate-700">
                                    Pagina {{ paginaUltimosRegistros }} de {{ totalPaginasUltimosRegistros }}
                                </span>
                                <button
                                    type="button"
                                    class="btn-secondary text-xs"
                                    :disabled="paginaUltimosRegistros === totalPaginasUltimosRegistros"
                                    @click="cambiarPaginaUltimos(1)"
                                >
                                    Siguiente
                                    <i class="ti ti-chevron-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </section>
                </div>

                <div v-show="tabActiva === 'revision'" class="space-y-6 animate-fade-in">
                    <section class="app-panel">
                        <div class="panel-header">
                            <div class="flex items-start gap-3">
                                <div class="soft-icon-blue">
                                    <i class="ti ti-file-analytics text-xl" aria-hidden="true"></i>
                                </div>
                                <div>
                                <h3 class="panel-title">Revision de importacion CSV</h3>
                                <p class="panel-subtitle">
                                    {{ filasRevision.length ? 'Ajusta las filas detectadas antes de aprobarlas.' : 'Sube un CSV desde Captura y Reloj.' }}
                                </p>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <button @click="seleccionarRevision(true)" :disabled="!filasRevision.length" class="btn-secondary w-full sm:w-auto" type="button">
                                    <i class="ti ti-checks" aria-hidden="true"></i>
                                    Seleccionar todo
                                </button>
                                <button @click="seleccionarRevision(false)" :disabled="!filasRevision.length" class="btn-secondary w-full sm:w-auto" type="button">
                                    <i class="ti ti-square-x" aria-hidden="true"></i>
                                    Quitar seleccion
                                </button>
                            </div>
                        </div>

                        <div v-if="filasRevision.length" class="space-y-5 p-5 sm:p-6">
                            <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
                                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <p class="metric-label">Total</p>
                                    <p class="mt-2 text-2xl font-bold text-slate-950">{{ resumenRevision.total }}</p>
                                </div>
                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                                    <p class="metric-label text-emerald-700">Seleccionadas</p>
                                    <p class="mt-2 text-2xl font-bold text-emerald-700">{{ resumenRevision.seleccionadas }}</p>
                                </div>
                                <div class="rounded-lg border border-rose-200 bg-rose-50 p-4">
                                    <p class="metric-label text-rose-700">Sin registro</p>
                                    <p class="mt-2 text-2xl font-bold text-rose-700">{{ resumenRevision.sinRegistro }}</p>
                                </div>
                                <div class="rounded-lg border border-orange-200 bg-orange-50 p-4">
                                    <p class="metric-label text-orange-700">Incompletas</p>
                                    <p class="mt-2 text-2xl font-bold text-orange-700">{{ resumenRevision.incompletas }}</p>
                                </div>
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                    <p class="metric-label text-amber-700">Actualizan</p>
                                    <p class="mt-2 text-2xl font-bold text-amber-700">{{ resumenRevision.actualiza }}</p>
                                </div>
                                <div class="rounded-lg border border-slate-200 bg-white p-4">
                                    <p class="metric-label">Sin empleado</p>
                                    <p class="mt-2 text-2xl font-bold text-slate-700">{{ resumenRevision.noEncontradas }}</p>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="text-sm font-medium text-slate-600">
                                    Rango:
                                    <span class="font-bold text-slate-900">
                                        {{ previewImportacion?.resumen?.fecha_inicio ? formatoFecha(previewImportacion.resumen.fecha_inicio) : '--' }}
                                        -
                                        {{ previewImportacion?.resumen?.fecha_fin ? formatoFecha(previewImportacion.resumen.fecha_fin) : '--' }}
                                    </span>
                                    <span class="ml-2 rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
                                        Ordenado por empleado
                                    </span>
                                </div>
                                <div class="grid w-full gap-3 xl:w-auto xl:grid-cols-[auto_1fr_auto_auto_1.4fr] xl:items-end">
                                    <button type="button" class="btn-secondary px-3 text-xs" @click="cambiarSemana('revision', -1)">
                                        <i class="ti ti-chevron-left" aria-hidden="true"></i>
                                    </button>
                                    <label class="block">
                                        <span class="field-label mb-1">Semana a revisar</span>
                                        <input v-model="fechaRevisionReferencia" type="date" class="field-input-soft" />
                                    </label>
                                    <button type="button" class="btn-secondary px-3 text-xs" @click="irSemanaActual('revision')">
                                        Hoy
                                    </button>
                                    <button type="button" class="btn-secondary px-3 text-xs" @click="cambiarSemana('revision', 1)">
                                        <i class="ti ti-chevron-right" aria-hidden="true"></i>
                                    </button>
                                    <label class="block">
                                        <span class="field-label mb-1">Buscar en revision</span>
                                        <input v-model="busquedaRevision" type="text" class="field-input-soft" placeholder="Numero, nombre, fecha o estado..." />
                                    </label>
                                </div>
                            </div>

                            <div class="rounded-lg border border-blue-100 bg-blue-50/60 px-4 py-3 text-sm font-bold text-blue-900">
                                Vista semanal del CSV: <span class="text-blue-700">{{ rangoSemanaRevision }}</span>
                            </div>

                            <div class="overflow-x-auto rounded-lg border border-slate-200">
                                <table class="asistencia-week-table revision-week-table">
                                    <thead>
                                        <tr class="asistencia-title-row">
                                            <th colspan="13">REVISION CSV - ASISTENCIAS POR EMPLEADO</th>
                                        </tr>
                                        <tr>
                                            <th class="w-14">No.</th>
                                            <th class="min-w-80">Empleado</th>
                                            <th v-for="dia in fechasSemanaRevision" :key="dia.iso" class="min-w-52 text-center">
                                                {{ dia.nombre }} {{ dia.diaMes }}
                                            </th>
                                            <th class="text-center">Sel.</th>
                                            <th class="text-center">Faltas</th>
                                            <th class="text-center">H.E.</th>
                                            <th class="text-center">Ret.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="grupo in filasMatrizRevisionPaginadas" :key="grupo.key">
                                            <td class="text-center font-black text-slate-700">{{ grupo.numero_empleado || 'S/N' }}</td>
                                            <td>
                                                <select
                                                    :value="grupo.empleado_id || ''"
                                                    @change="sincronizarEmpleadoGrupoRevision(grupo, $event.target.value)"
                                                    class="field-input-soft min-w-72 text-xs font-bold"
                                                >
                                                    <option value="">Sin empleado</option>
                                                    <option v-for="empleado in empleadosOrdenadosFiltro" :key="empleado.id" :value="empleado.id">
                                                        {{ empleado.numero_empleado ? '#' + empleado.numero_empleado + ' - ' : '' }}{{ empleado.nombre_completo }}
                                                    </option>
                                                </select>
                                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                                    CSV: {{ grupo.csv_numero_empleado || grupo.numero_empleado || 'S/N' }} - {{ grupo.csv_nombre_completo || grupo.nombre_completo || 'Sin nombre' }}
                                                </p>
                                            </td>
                                            <td
                                                v-for="dia in grupo.dias"
                                                :key="`${grupo.key}-${dia.iso}`"
                                                class="asistencia-day-cell revision-day-cell"
                                                :class="claseFilaRevision(dia.fila)"
                                            >
                                                <template v-if="dia.fila">
                                                    <div class="mb-2 flex items-center justify-between gap-2">
                                                        <label class="inline-flex items-center gap-1 text-[10px] font-black uppercase text-slate-600">
                                                            <input
                                                                v-model="dia.fila.aprobado"
                                                                type="checkbox"
                                                                :disabled="!dia.fila.empleado_id"
                                                                class="rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                                            />
                                                            OK
                                                        </label>
                                                        <button @click="descartarFilaRevision(dia.fila._uid)" class="text-rose-600 hover:text-rose-700" type="button" title="Quitar fila">
                                                            <i class="ti ti-x" aria-hidden="true"></i>
                                                        </button>
                                                    </div>
                                                    <span class="status-pill mb-2" :class="claseEstadoRevision(dia.fila.estado)">
                                                        {{ textoEstadoRevision(dia.fila.estado) }}
                                                    </span>
                                                    <input v-model="dia.fila.fecha" type="date" class="mini-field mb-2" @change="calcularHorasFilaRevision(dia.fila)" />
                                                    <select v-model="dia.fila.tipo_asistencia" @change="prepararCambioTipo(dia.fila)" class="mini-field mb-2">
                                                        <option v-for="tipo in tiposAsistencia" :key="tipo" :value="tipo">{{ tipo }}</option>
                                                    </select>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <input
                                                            v-model="dia.fila.hora_entrada"
                                                            type="time"
                                                            :disabled="dia.fila.tipo_asistencia !== 'Normal'"
                                                            @change="calcularHorasFilaRevision(dia.fila)"
                                                            class="mini-field"
                                                            :class="dia.fila.tipo_asistencia !== 'Normal' ? 'cursor-not-allowed opacity-50' : ''"
                                                        />
                                                        <input
                                                            v-model="dia.fila.hora_salida"
                                                            type="time"
                                                            :disabled="dia.fila.tipo_asistencia !== 'Normal'"
                                                            @change="calcularHorasFilaRevision(dia.fila)"
                                                            class="mini-field"
                                                            :class="dia.fila.tipo_asistencia !== 'Normal' ? 'cursor-not-allowed opacity-50' : ''"
                                                        />
                                                    </div>
                                                    <div class="mt-2 grid grid-cols-3 gap-1 text-center text-[10px] font-black uppercase text-slate-500">
                                                        <span>{{ dia.fila.marcas }} m.</span>
                                                        <span>{{ formatoHorasResumen(dia.fila.horas_extra) }} H.E.</span>
                                                        <span>{{ Number(dia.fila.minutos_tarde || 0) }} ret.</span>
                                                    </div>
                                                    <p v-if="dia.fila.mensaje" class="mt-2 line-clamp-2 text-[10px] font-semibold text-slate-500">
                                                        {{ dia.fila.mensaje }}
                                                    </p>
                                                </template>
                                                <span v-else class="cell-empty">--</span>
                                            </td>
                                            <td class="text-center font-black text-emerald-700">{{ grupo.filas.filter((fila) => fila.aprobado && fila.empleado_id).length }}</td>
                                            <td class="text-center font-black text-rose-600">{{ grupo.filas.filter((fila) => fila.tipo_asistencia === 'Falta').length }}</td>
                                            <td class="text-center font-black text-blue-700">{{ formatoHorasResumen(grupo.filas.reduce((total, fila) => total + Number(fila.horas_extra || 0), 0)) }}</td>
                                            <td class="text-center font-black text-orange-600">{{ grupo.filas.reduce((total, fila) => total + Number(fila.minutos_tarde || 0), 0) }}</td>
                                        </tr>
                                        <tr v-if="filasMatrizRevision.length === 0">
                                            <td colspan="13" class="empty-state">No se encontraron filas en esta semana de revision.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-if="filasMatrizRevision.length > 0" class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-600 sm:flex-row sm:items-center sm:justify-between">
                                <span>Mostrando {{ rangoPagina(paginaRevision, filasMatrizRevision.length) }}</span>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="btn-secondary text-xs"
                                        :disabled="paginaRevision === 1"
                                        @click="cambiarPaginaRevision(-1)"
                                    >
                                        <i class="ti ti-chevron-left" aria-hidden="true"></i>
                                        Anterior
                                    </button>
                                    <span class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700">
                                        Pagina {{ paginaRevision }} de {{ totalPaginasRevision }}
                                    </span>
                                    <button
                                        type="button"
                                        class="btn-secondary text-xs"
                                        :disabled="paginaRevision === totalPaginasRevision"
                                        @click="cambiarPaginaRevision(1)"
                                    >
                                        Siguiente
                                        <i class="ti ti-chevron-right" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                                <button @click="descartarRevision" :disabled="formRevision.processing" class="btn-secondary w-full sm:w-auto" type="button">
                                    <i class="ti ti-trash" aria-hidden="true"></i>
                                    Descartar revision
                                </button>
                                <button @click="aprobarRevision" :disabled="formRevision.processing || resumenRevision.seleccionadas === 0" class="btn-accent w-full sm:w-auto" type="button">
                                    <svg v-if="!formRevision.processing" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7" />
                                    </svg>
                                    {{ formRevision.processing ? 'Guardando...' : `Aprobar ${resumenRevision.seleccionadas} seleccionada(s)` }}
                                </button>
                            </div>
                        </div>

                        <div v-else class="empty-state">
                            No hay una revision CSV pendiente.
                        </div>
                    </section>
                </div>

                <div v-show="tabActiva === 'vacaciones'" class="space-y-6 animate-fade-in">
                    <section class="app-panel">
                        <div class="panel-header">
                            <div class="flex items-start gap-3">
                                <div class="soft-icon-emerald">
                                    <i class="ti ti-beach text-xl" aria-hidden="true"></i>
                                </div>
                                <div>
                                <h3 class="panel-title">Cuadro de Vacaciones</h3>
                                <p class="panel-subtitle">Control global de dias correspondientes, tomados y restantes.</p>
                                </div>
                            </div>
                            <div class="flex w-full flex-col gap-3 lg:w-auto lg:flex-row">
                                <label class="block w-full lg:w-56">
                                    <span class="field-label mb-1">Ordenar por</span>
                                    <select v-model="ordenControlEmpleados" class="field-input-soft">
                                        <option value="num_asc">No. empleado menor</option>
                                        <option value="num_desc">No. empleado mayor</option>
                                        <option value="nombre_asc">Nombre A - Z</option>
                                        <option value="nombre_desc">Nombre Z - A</option>
                                    </select>
                                </label>
                                <label class="block w-full lg:w-96">
                                    <span class="field-label mb-1">Buscar</span>
                                    <input v-model="busquedaGlobal" type="text" class="field-input-soft" placeholder="Buscar trabajador..." />
                                </label>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="table-premium">
                                <thead>
                                    <tr>
                                        <th>Num</th>
                                        <th>Nombre del Trabajador</th>
                                        <th>Fecha de Ingreso</th>
                                        <th class="text-center">Años Cumplidos</th>
                                        <th class="text-center">Dias por Ley</th>
                                        <th class="text-center">Dias Tomados</th>
                                        <th class="text-center">Desc. años pasados</th>
                                        <th class="text-center">Dias Pendientes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="empleado in empleadosFiltradosGlobal" :key="empleado.id">
                                        <td class="whitespace-nowrap font-bold text-slate-500">{{ empleado.numero_empleado || 'S/N' }}</td>
                                        <td class="whitespace-nowrap font-semibold text-slate-900">{{ empleado.nombre_completo }}</td>
                                        <td class="whitespace-nowrap text-slate-600">{{ empleado.fecha_ingreso || 'No registrada' }}</td>
                                        <td class="whitespace-nowrap text-center font-medium">{{ empleado.antiguedad_anios }}</td>
                                        <td class="whitespace-nowrap text-center">
                                            <span class="inline-flex items-center justify-center rounded-lg bg-slate-100 px-3 py-1 font-bold text-slate-700">
                                                {{ empleado.dias_vacaciones_totales }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap text-center">
                                            <span class="inline-flex items-center justify-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-1 font-bold text-amber-700">
                                                {{ empleado.dias_vacaciones_tomados }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap text-center">
                                            <span
                                                class="inline-flex items-center justify-center rounded-lg border px-3 py-1 font-bold"
                                                :class="empleado.ajuste_vacaciones < 0 ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-slate-200 bg-slate-50 text-slate-400'"
                                            >
                                                {{ empleado.ajuste_vacaciones < 0 ? Math.abs(empleado.ajuste_vacaciones) : 0 }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap text-center">
                                            <span
                                                class="inline-flex items-center justify-center rounded-lg border px-3 py-1 font-bold"
                                                :class="empleado.dias_vacaciones_restantes > 0 ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-400'"
                                            >
                                                {{ empleado.dias_vacaciones_restantes }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="empleadosFiltradosGlobal.length === 0">
                                        <td colspan="8" class="empty-state">No se encontraron trabajadores.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                <div v-show="tabActiva === 'faltas'" class="space-y-6 animate-fade-in">
                    <section class="app-panel">
                        <div class="panel-header border-b-rose-100">
                            <div class="flex items-start gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700">
                                    <i class="ti ti-user-x text-xl" aria-hidden="true"></i>
                                </div>
                                <div>
                                <h3 class="panel-title text-rose-900">Control de Faltas Injustificadas</h3>
                                <p class="panel-subtitle">Acumulado anual de ausencias por empleado.</p>
                                </div>
                            </div>
                            <div class="flex w-full flex-col gap-3 lg:w-auto lg:flex-row">
                                <label class="block w-full lg:w-56">
                                    <span class="field-label mb-1 text-rose-700">Ordenar por</span>
                                    <select v-model="ordenControlEmpleados" class="field-input-soft focus:border-rose-400 focus:ring-rose-500/20">
                                        <option value="num_asc">No. empleado menor</option>
                                        <option value="num_desc">No. empleado mayor</option>
                                        <option value="nombre_asc">Nombre A - Z</option>
                                        <option value="nombre_desc">Nombre Z - A</option>
                                    </select>
                                </label>
                                <label class="block w-full lg:w-96">
                                    <span class="field-label mb-1 text-rose-700">Buscar</span>
                                    <input v-model="busquedaGlobal" type="text" class="field-input-soft focus:border-rose-400 focus:ring-rose-500/20" placeholder="Buscar trabajador..." />
                                </label>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="table-premium">
                                <thead class="bg-rose-50/50">
                                    <tr>
                                        <th>Num</th>
                                        <th>Nombre del Trabajador</th>
                                        <th class="text-center">Estatus</th>
                                        <th class="text-right text-rose-700">Faltas Totales</th>
                                        <th class="text-right">Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="empleado in empleadosFiltradosGlobal" :key="empleado.id">
                                        <tr class="hover:bg-rose-50/30">
                                            <td class="whitespace-nowrap font-bold text-slate-500">{{ empleado.numero_empleado || 'S/N' }}</td>
                                            <td class="whitespace-nowrap font-semibold text-slate-900">{{ empleado.nombre_completo }}</td>
                                            <td class="whitespace-nowrap text-center">
                                                <span v-if="empleado.dias_faltas_totales === 0" class="status-pill status-success">Asistencia Perfecta</span>
                                                <span v-else-if="empleado.dias_faltas_totales < 3" class="status-pill status-warning">Alerta Minima</span>
                                                <span v-else class="status-pill border-rose-200 bg-rose-50 text-rose-700">Problema de Ausentismo</span>
                                            </td>
                                            <td class="whitespace-nowrap text-right">
                                                <span class="inline-flex items-center justify-center text-lg font-black" :class="empleado.dias_faltas_totales > 0 ? 'text-rose-600' : 'text-slate-300'">
                                                    {{ empleado.dias_faltas_totales }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap text-right">
                                                <button
                                                    type="button"
                                                    :disabled="Number(empleado.dias_faltas_totales || 0) === 0"
                                                    @click="toggleDetalleFaltas(empleado.id)"
                                                    :class="Number(empleado.dias_faltas_totales || 0) > 0 ? 'btn-secondary text-xs' : 'inline-flex cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold text-slate-300'"
                                                >
                                                    <i class="ti ti-calendar-search" aria-hidden="true"></i>
                                                    {{ empleadoFaltasExpandido === empleado.id ? 'Ocultar' : 'Ver fechas' }}
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="empleadoFaltasExpandido === empleado.id" class="bg-rose-50/40">
                                            <td colspan="5" class="px-6 py-4">
                                                <div class="rounded-lg border border-rose-100 bg-white p-4">
                                                    <div class="mb-3 flex items-center gap-2 text-sm font-black text-rose-800">
                                                        <i class="ti ti-calendar-x" aria-hidden="true"></i>
                                                        Fechas con falta de {{ empleado.nombre_completo }}
                                                    </div>
                                                    <div v-if="fechasFaltasEmpleado(empleado).length" class="flex flex-wrap gap-2">
                                                        <span
                                                            v-for="fecha in fechasFaltasEmpleado(empleado)"
                                                            :key="fecha"
                                                            class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-bold text-rose-700"
                                                        >
                                                            {{ formatoFecha(fecha) }}
                                                        </span>
                                                    </div>
                                                    <p v-else class="text-sm font-semibold text-slate-500">
                                                        Sin fechas registradas.
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr v-if="empleadosFiltradosGlobal.length === 0">
                                        <td colspan="5" class="empty-state">No se encontraron trabajadores.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

.asistencia-week-table {
    min-width: 1420px;
    width: 100%;
    border-collapse: collapse;
    background: white;
    font-size: 12px;
}

.asistencia-week-table th,
.asistencia-week-table td {
    border: 1px solid #cbd5e1;
    padding: 8px;
    vertical-align: middle;
}

.asistencia-week-table thead th {
    background: #f8fafc;
    color: #0f172a;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
}

.asistencia-week-table tbody tr:hover td {
    background-color: #f8fafc;
}

.asistencia-title-row th {
    background: #ffffff !important;
    color: #0f172a;
    font-size: 14px;
    letter-spacing: 0;
    text-align: center;
}

.asistencia-day-cell {
    min-width: 160px;
    height: 96px;
    vertical-align: top !important;
}

.revision-day-cell {
    min-width: 208px;
    height: 176px;
}

.asistencia-cell-empty {
    background: #ffffff;
    color: #94a3b8;
}

.asistencia-cell-normal {
    background: #ffffff;
}

.asistencia-cell-retardo,
.asistencia-cell-incompleta {
    background: #fff7ed;
}

.asistencia-cell-falta {
    background: #fff1f2;
}

.asistencia-cell-incapacidad {
    background: #fffbeb;
}

.asistencia-cell-vacaciones {
    background: #ecfdf5;
}

.asistencia-cell-omitida {
    background: #f1f5f9;
    opacity: 0.75;
}

.cell-main {
    color: #0f172a;
    font-size: 13px;
    font-weight: 900;
    text-align: center;
}

.cell-meta {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 6px;
    color: #475569;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}

.cell-actions {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-top: 8px;
}

.cell-actions button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    color: #475569;
    background: #ffffff;
    transition: 0.15s ease;
}

.cell-actions button:hover {
    border-color: #0f766e;
    color: #0f766e;
}

.cell-empty {
    display: block;
    text-align: center;
    color: #cbd5e1;
    font-weight: 900;
}

.mini-field {
    width: 100%;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    padding: 6px 8px;
    color: #0f172a;
    font-size: 11px;
    font-weight: 700;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
