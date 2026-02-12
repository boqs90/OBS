# Formato del Archivo Excel para Importación de Alumnos

## Estructura del Archivo

El archivo Excel debe tener **pestañas separadas por cada grado**. Cada pestaña representa un grado diferente y contiene los alumnos de ese grado.

## Columnas Requeridas

Cada pestaña debe contener las siguientes columnas (el orden puede variar):

| Columna | Descripción | Ejemplo | Requerido |
|---------|-------------|---------|-----------|
| Nombre Completo | Nombre completo del alumno | Juan Pérez García | ✅ Sí |
| Fecha de Nacimiento | Fecha en formato DD/MM/YYYY | 15/03/2010 | ✅ Sí |
| Grado | Nombre del grado (opcional, usa nombre de pestaña) | Primer Grado | ⚠️ Opcional |
| Padre/Madre Principal | Nombre del padre/madre/encargado | María López | ✅ Sí |
| DUI/NIT | Número de identidad del padre/madre | 12345678-9 | ✅ Sí |
| Parentesco | Relación con el alumno | Madre | ✅ Sí |
| Teléfono | Teléfono de contacto | 2234-5678 | ✅ Sí |
| Ocupación | Ocupación del padre/madre | Profesora | ⚠️ Opcional |
| Email | Correo electrónico | maria@email.com | ⚠️ Opcional |

## Valores Aceptados para Parentesco

- Padre
- Madre  
- Tutor
- Encargado
- Abuelo/a
- Otro

## Ejemplo de Estructura

### Pestaña: "Primer Grado"

| Nombre Completo | Fecha de Nacimiento | Grado | Padre/Madre Principal | DUI/NIT | Parentesco | Teléfono | Ocupación | Email |
|-----------------|---------------------|-------|----------------------|---------|------------|----------|-----------|-------|
| Juan Pérez García | 15/03/2010 | Primer Grado | María López | 12345678-9 | Madre | 2234-5678 | Profesora | maria@email.com |
| Ana Martínez | 22/07/2010 | Primer Grado | Carlos Martínez | 87654321-0 | Padre | 2234-1234 | Ingeniero | carlos@email.com |

### Pestaña: "Segundo Grado"

| Nombre Completo | Fecha de Nacimiento | Grado | Padre/Madre Principal | DUI/NIT | Parentesco | Teléfono | Ocupación | Email |
|-----------------|---------------------|-------|----------------------|---------|------------|----------|-----------|-------|
| Pedro Rodríguez | 10/11/2009 | Segundo Grado | Laura Rodríguez | 45678912-3 | Madre | 2234-9876 | Doctora | laura@email.com |

## Reglas de Validación

1. **Nombre Completo**: No puede estar vacío
2. **Fecha de Nacimiento**: Debe ser válida y en formato DD/MM/YYYY
3. **Grado**: Debe existir en el sistema (si no se especifica, usa el nombre de la pestaña)
4. **Padre/Madre Principal**: No puede estar vacío
5. **DUI/NIT**: No puede estar vacío
6. **Parentesco**: Debe ser uno de los valores aceptados
7. **Teléfono**: No puede estar vacío

## Consideraciones Importantes

- Los **nombres de las pestañas** deben coincidir exactamente con los nombres de los grados configurados en el sistema
- Si una columna de **Grado** está vacía, se usará automáticamente el nombre de la pestaña
- Los **campos opcionales** pueden dejarse en blanco
- El sistema **validará** que todos los grados existan antes de importar
- Se mostrará una **vista previa** antes de confirmar la importación
- Los **errores** se mostrarán detalladamente por fila y columna

## Proceso de Importación

1. **Seleccionar archivo** Excel con las pestañas por grado
2. **Vista previa** de datos detectados
3. **Validación** automática de formato y datos
4. **Confirmación** de importación
5. **Procesamiento** individual de cada alumno
6. **Reporte** de resultados (éxitos y errores)

## Errores Comunes

- **Formato de fecha incorrecto**: Use DD/MM/YYYY
- **Grado no existe**: Verifique nombres de pestañas y columna Grado
- **Campos vacíos**: Complete todos los campos requeridos
- **Parentesco inválido**: Use solo los valores aceptados
