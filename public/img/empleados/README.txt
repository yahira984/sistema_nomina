Fotos de empleados

Guarda aqui las fotos por numero de empleado.

Ejemplos:
43.webp
43.jpg
94.png

Si un numero de empleado se reutiliza, usa el ID interno para evitar cruces:
id-43.jpg
id-43.png
empleado-43.webp

La foto por ID siempre tiene prioridad sobre la foto por numero.
La foto por numero se usa solo si ese numero no esta repetido entre empleados activos o de baja.

Al dar de baja un empleado, el sistema mueve su foto a:
empleados/bajas/id-{ID}.png

Asi el numero queda libre para volver a usar archivos como:
1.png
2.png

El sistema busca en este orden: webp, jpg, jpeg y png.
No se modifica la base de datos.
