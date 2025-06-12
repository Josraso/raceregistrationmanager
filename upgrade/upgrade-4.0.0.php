Módulo de Gestión de Inscripciones para Múltiples Carreras de Motos
Versión: 4.0.0

NUEVAS CARACTERÍSTICAS v4.0:
- Soporte para múltiples carreras simultáneas
- Gestión independiente por producto/carrera
- Configuración específica para cada carrera
- Sistema de archivo de carreras
- Estadísticas por carrera
- Exportación individual por carrera

INSTALACIÓN:
1. Comprimir todos los archivos en un ZIP: raceregistrationmanager.zip
2. Ir al Backoffice de PrestaShop > Módulos > Añadir nuevo módulo
3. Subir el archivo ZIP
4. Instalar el módulo

ESTRUCTURA DE ARCHIVOS:
/raceregistrationmanager/
├── raceregistrationmanager.php
├── config.xml
├── README.txt
├── views/
│   ├── css/
│   │   └── admin.css
│   ├── js/
│   │   └── admin.js
│   └── templates/
│       └── admin/
│           ├── race_selector.tpl
│           ├── configure.tpl
│           └── export.tpl

CONFIGURACIÓN INICIAL:
1. Crear productos para cada carrera con:
   - "carrera" o "race" en el nombre, O
   - Referencia que empiece con "RACE"
2. Ir a Módulos > Race Registration Manager
3. Seleccionar la carrera activa
4. Configurar los campos para cada carrera

USO DEL MÓDULO:

1. GESTIÓN DE CARRERAS:
   - Selector de carrera en la parte superior
   - Estadísticas de la carrera actual
   - Opción de archivar carreras finalizadas

2. CONFIGURACIÓN POR CARRERA:
   - Campos a mostrar (personalizables por carrera)
   - Campo de agrupación (categoría)
   - Campo para mostrar en listado
   - Página CMS asociada

3. GESTIÓN DE INSCRIPCIONES:
   - Las inscripciones se asocian automáticamente a la carrera
   - Filtros avanzados por estado, fecha, etc.
   - Acciones individuales y masivas
   - Estados: Pendiente → Validado → Publicado

4. EXPORTACIÓN:
   - Exportación independiente por carrera
   - Formatos HTML y CSV
   - Nombres de archivo con identificación de carrera

FLUJO DE TRABAJO:
1. Cliente compra producto de carrera → Pedido creado
2. Admin procesa y valida → Inscripción validada
3. Admin publica → Visible en CMS configurado
4. Al finalizar → Archivar carrera

NOTAS IMPORTANTES:
- Las inscripciones se detectan automáticamente por producto
- Cada carrera mantiene su configuración independiente
- Las carreras archivadas no aparecen en el listado activo
- Los datos de carreras archivadas se mantienen para consulta

SOLUCIÓN DE PROBLEMAS:
- Si no aparecen carreras: verificar nombres/referencias de productos
- Si no se guardan campos: revisar módulo an_productfields
- Para migrar datos antiguos: usar la función "Procesar y validar"

COMPATIBILIDAD:
- PrestaShop 1.7.x a 8.x
- PHP 7.2 o superior
- Compatible con an_productfields

CHANGELOG v4.0:
- Añadido: Gestión multi-carrera por producto
- Añadido: Configuración independiente por carrera
- Añadido: Sistema de archivo
- Añadido: Estadísticas por carrera
- Mejorado: Exportación con identificación de carrera
- Mejorado: Interfaz con selector de carrera
- Corregido: Problemas de rendimiento con muchas inscripciones