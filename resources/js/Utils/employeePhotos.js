const PHOTO_VERSION = '20260622-2';

export const clavesFotoEmpleado = (empleado) => {
    const raw = String(empleado?.id || empleado?.numero_empleado || empleado?.numero_empleado_baja || '').trim();
    if (!raw) return [];

    return [raw];
};

export const fotoEmpleadoSrc = (empleado) => {
    const claves = clavesFotoEmpleado(empleado);
    const clave = claves[0];
    if (!clave) return '';

    return `/empleados/fotos/${encodeURIComponent(clave)}?v=${PHOTO_VERSION}`;
};

export const mostrarFotoEmpleado = (event) => {
    event.target.style.display = 'block';
};

export const probarSiguienteFotoEmpleado = (empleado, event) => {
    const img = event.target;
    img.style.display = 'none';
};
