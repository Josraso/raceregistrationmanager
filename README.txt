Módulo de Gestión de Inscripciones para Múltiples Carreras de Motos
Versión: 4.0.

NUEVAS CARACTERÍSTICAS v4.0.5:
- Corregido: Soporte para múltiples inscripciones en un mismo pedido
- Corregido: Visualización del HTML de exportación
- Mejorado: Detección y procesamiento de múltiples productos de carrera por pedido
- Mejorado: Sistema de validación para pedidos con varias inscripciones
- Añadido: Indicador visual para pedidos con múltiples inscripciones

CARACTERÍSTICAS v4.0:
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
   - Soporte para múltiples inscripciones por pedido
   - Filtros avanzados por estado, fecha, etc.
   - Acciones individuales y masivas
   - Estados: Pendiente → Validado → Publicado

4. EXPORTACIÓN:
   - Exportación independiente por carrera
   - Formatos HTML y CSV
   - Nombres de archivo con identificación de carrera
   - Vista previa del HTML antes de copiar

FLUJO DE TRABAJO:
1. Cliente compra producto(s) de carrera → Pedido creado
2. Sistema detecta múltiples inscripciones si aplica
3. Admin procesa y valida → Inscripción(es) validada(s)
4. Admin publica → Visible en CMS configurado
5. Al finalizar → Archivar carrera

PEDIDOS CON MÚLTIPLES INSCRIPCIONES:
- El módulo ahora detecta automáticamente cuando un pedido contiene varios productos de carrera
- Cada inscripción se procesa de forma independiente
- Los pedidos con múltiples inscripciones se muestran con un indicador especial
- La validación y publicación afecta a todas las inscripciones del pedido

NOTAS IMPORTANTES:
- Las inscripciones se detectan automáticamente por producto
- Cada carrera mantiene su configuración independiente
- Las carreras archivadas no aparecen en el listado activo
- Los datos de carreras archivadas se mantienen para consulta
- El HTML de exportación solo muestra inscripciones validadas

SOLUCIÓN DE PROBLEMAS:
- Si no aparecen carreras: verificar nombres/referencias de productos
- Si no se guardan campos: revisar módulo an_productfields
- Si no se muestra el HTML: verificar que hay inscripciones validadas
- Para migrar datos antiguos: usar la función "Procesar y validar"

COMPATIBILIDAD:
- PrestaShop 1.7.x a 8.x
- PHP 7.2 o superior
- Compatible con an_productfields

CHANGELOG v4.0.1:
- Corregido: Detección de múltiples inscripciones en un pedido
- Corregido: Visualización del HTML de exportación cuando no hay datos
- Mejorado: Manejo de campos personalizados por producto
- Mejorado: Indicadores visuales para pedidos complejos
- Mejorado: Mensajes de error más descriptivos

CHANGELOG v4.0:
- Añadido: Gestión multi-carrera por producto
- Añadido: Configuración independiente por carrera
- Añadido: Sistema de archivo
- Añadido: Estadísticas por carrera
- Mejorado: Exportación con identificación de carrera
- Mejorado: Interfaz con selector de carrera
- Corregido: Problemas de rendimiento con muchas inscripciones
