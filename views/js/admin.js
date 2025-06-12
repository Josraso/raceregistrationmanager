$(document).ready(function() {
    // Variable global para el producto seleccionado
    var currentProductId = typeof selectedProduct !== 'undefined' ? selectedProduct : 0;
    // Si no hay producto seleccionado, redirigir al primero disponible
    if (!currentProductId && $('#race-selector option').length > 1) {
        var firstProduct = $('#race-selector option:not(:first)').first().val();
        if (firstProduct) {
            window.location.href = window.location.href + '&selected_product=' + firstProduct;
        }
    }
    // Función para actualizar los números de orden después de mover
    function updateFieldOrderNumbers() {
        $('#fields-table tbody tr').each(function(index) {
            var orderIndex = index + 1;
            var field = $(this).data('field');
            
            // Actualizar el número de orden visible
            $(this).find('.order-num').text(orderIndex);
            
            // Actualizar el valor del input oculto
            $(this).find('.order-input').val(orderIndex);
            
            // Actualizar el valor del checkbox
            var checkbox = $(this).find('.field-checkbox');
            if (checkbox.length) {
                checkbox.val(field + '|' + orderIndex);
            }
        });
    }
    
    // Mover fila hacia arriba
    $(document).on('click', '.move-up', function(e) {
        e.preventDefault();
        var row = $(this).closest('tr');
        var prev = row.prev();
        if (prev.length) {
            row.insertBefore(prev);
            updateFieldOrderNumbers();
        }
    });
    
    // Mover fila hacia abajo
    $(document).on('click', '.move-down', function(e) {
        e.preventDefault();
        var row = $(this).closest('tr');
        var next = row.next();
        if (next.length) {
            row.insertAfter(next);
            updateFieldOrderNumbers();
        }
    });
    
    // Checkbox seleccionar/deseleccionar todos
    $('#select-all').on('change', function() {
        $('.order-checkbox').prop('checked', $(this).prop('checked'));
    });
    
    // Si se selecciona algún checkbox individual
    $(document).on('change', '.order-checkbox', function() {
        updateSelectAllCheckbox();
    });
    
    // Función para actualizar el estado del checkbox "select-all"
    function updateSelectAllCheckbox() {
        if ($('.order-checkbox').length > 0 && $('.order-checkbox:checked').length === $('.order-checkbox').length) {
            $('#select-all').prop('checked', true);
        } else {
            $('#select-all').prop('checked', false);
        }
    }

    // Procesar y validar pedido - usando AJAX
    $(document).on('click', '.process-order', function(e) {
        e.preventDefault();
        var orderId = $(this).data('id');
        var button = $(this);
        
        // Deshabilitar botón y mostrar spinner
        button.addClass('disabled');
        button.html('<i class="icon-spinner icon-spin"></i> Procesando...');
        
        $.ajax({
            url: ajaxUrl, // Asegúrate que ajaxUrl está definida globalmente
            type: 'POST',
            dataType: 'json',
            data: {
                actionType: 'process_validate',
                id: orderId,
                selected_product: currentProductId
            },
            success: function(response) {
                if (response.status === 'success') {
                    showSuccess(response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showError(response.message);
                    button.removeClass('disabled');
                    button.html('<i class="icon-check"></i> Procesar y validar');
                }
            },
            error: function() {
                showError('Error de comunicación con el servidor');
                button.removeClass('disabled');
                button.html('<i class="icon-check"></i> Procesar y validar');
            }
        });
    });
    
    // Anular validación - usando AJAX
    $(document).on('click', '.unvalidate-order', function(e) {
        e.preventDefault();
        var orderId = $(this).data('id');
        var button = $(this);
        
        // Deshabilitar botón y mostrar spinner
        button.addClass('disabled');
        button.html('<i class="icon-spinner icon-spin"></i> Anulando...');
        
        $.ajax({
            url: ajaxUrl, // Asegúrate que ajaxUrl está definida globalmente
            type: 'POST',
            dataType: 'json',
            data: {
                actionType: 'unvalidate',
                id: orderId,
                selected_product: currentProductId
            },
            success: function(response) {
                if (response.status === 'success') {
                    showSuccess(response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showError(response.message);
                    button.removeClass('disabled');
                    button.html('<i class="icon-ban-circle"></i> Anular');
                }
            },
            error: function() {
                showError('Error de comunicación con el servidor');
                button.removeClass('disabled');
                button.html('<i class="icon-ban-circle"></i> Anular');
            }
        });
    });
    
    // Publicar inscripción - usando AJAX
    $(document).on('click', '.publish-order', function(e) {
        e.preventDefault();
        var regId = $(this).data('id');
        var button = $(this);
        
        // Deshabilitar botón y mostrar spinner
        button.addClass('disabled');
        button.html('<i class="icon-spinner icon-spin"></i> Publicando...');
        
        $.ajax({
            url: ajaxUrl, // Asegúrate que ajaxUrl está definida globalmente
            type: 'POST',
            dataType: 'json',
            data: {
                actionType: 'publish',
                id: regId,
                selected_product: currentProductId
            },
            success: function(response) {
                if (response.status === 'success') {
                    showSuccess(response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showError(response.message);
                    button.removeClass('disabled');
                    button.html('<i class="icon-upload"></i> Publicar');
                }
            },
            error: function() {
                showError('Error de comunicación con el servidor');
                button.removeClass('disabled');
                button.html('<i class="icon-upload"></i> Publicar');
            }
        });
    });

    // Anular publicación - usando AJAX
    $(document).on('click', '.unpublish-order', function(e) {
        e.preventDefault();
        var regId = $(this).data('id');
        var button = $(this);
        
        // Deshabilitar botón y mostrar spinner
        button.addClass('disabled');
        button.html('<i class="icon-spinner icon-spin"></i> Anulando publicación...');
        
        $.ajax({
            url: ajaxUrl, // Asegúrate que ajaxUrl está definida globalmente
            type: 'POST',
            dataType: 'json',
            data: {
                actionType: 'unpublish',
                id: regId,
                selected_product: currentProductId
            },
            success: function(response) {
                if (response.status === 'success') {
                    showSuccess(response.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showError(response.message);
                    button.removeClass('disabled');
                    button.html('<i class="icon-remove"></i> Anular publicación');
                }
            },
            error: function() {
                showError('Error de comunicación con el servidor');
                button.removeClass('disabled');
                button.html('<i class="icon-remove"></i> Anular publicación');
            }
        });
    });

    // Verificar que hay pedidos seleccionados antes de enviar formularios de acciones masivas
    $('#process-selected, #validate-selected, #publish-selected, #unvalidate-selected, #unpublish-selected').click(function(e) {
        if ($('.order-checkbox:checked').length === 0) {
            e.preventDefault();
            showError('Debes seleccionar al menos un pedido para realizar esta acción');
        }
    });
    
    // Función para mostrar mensajes de éxito
    function showSuccess(message) {
        $('#success-message').text(message).fadeIn().delay(3000).fadeOut();
    }
    
    // Función para mostrar mensajes de error
    function showError(message) {
        $('#error-message').text(message).fadeIn().delay(3000).fadeOut();
    }
    
    // Agregar funcionalidad para el botón de filtrar
    $('button[name="submitFilter"]').on('click', function() {
        $('#filter-form').submit();
    });
    
    // Cargar configuración de carrera cuando cambia el producto
    $(document).on('change', 'select[name="selected_product"]', function() {
        var productId = $(this).val();
        if (productId) {
            loadRaceConfiguration(productId);
        }
    });
    
    // Función para cargar configuración de carrera via AJAX
    function loadRaceConfiguration(productId) {
        // Asegúrate que ajaxUrl está definida globalmente y es correcta para esta acción
        var raceConfigAjaxUrl = ajaxUrl.replace('action=process_action', 'action=get_race_config'); 
        $.ajax({
            url: raceConfigAjaxUrl, 
            type: 'POST',
            dataType: 'json',
            data: {
                id_product: productId
            },
            success: function(response) {
                if (response.status === 'success' && response.config) {
                    // Actualizar campos de configuración
                    updateConfigurationFields(response.config);
                }
            },
            error: function() {
                console.error('Error al cargar la configuración de la carrera');
            }
        });
    }
    
    // Actualizar campos de configuración
    function updateConfigurationFields(config) {
        // Actualizar campo de categoría
        if (config.category_field) {
            $('select[name="category_field"]').val(config.category_field);
        }
        
        // Actualizar campo de lista
        if (config.list_field !== undefined) {
            $('select[name="list_field"]').val(config.list_field);
        }

        
        // Actualizar campos mostrados
        $('.field-checkbox').prop('checked', false);
        if (config.display_fields && typeof config.display_fields === 'object') {
            $.each(config.display_fields, function(field, order) {
                var checkbox = $('.field-checkbox[data-field="' + field + '"]');
                if (checkbox.length) {
                    checkbox.prop('checked', true);
                }
            });
        }
    }
    
    // Mantener el producto seleccionado en los enlaces de paginación
    $(document).on('click', '.pagination a', function(e) {
        var href = $(this).attr('href');
        if (href && currentProductId && href.indexOf('selected_product=') === -1) {
            e.preventDefault();
            window.location.href = href + '&selected_product=' + currentProductId;
        }
    });
    
    // Confirmación antes de archivar carrera
    $('button[name="archiveRace"]').on('click', function(e) {
        if (!confirm('¿Estás seguro de que quieres archivar esta carrera? Esta acción ocultará la carrera del listado activo.')) {
            e.preventDefault();
        }
    });
    
    // Añadir el producto seleccionado a todos los formularios
    $('form').each(function() {
        if (currentProductId && !$(this).find('input[name="selected_product"]').length) {
            $(this).append('<input type="hidden" name="selected_product" value="' + currentProductId + '">');
        }
    });

    // Botón de copiar HTML PrestaShop - Mejorado (ESTA ES LA VERSIÓN ACTUALIZADA)
    $('#copy-prestashop-btn').click(function() {
        var htmlCodeTextarea = $('#prestashop-html'); // El textarea que contiene el HTML
        var copyErrorDiv = $('.copy-error-ps');     // Div para mensajes de error de copia
        var copySuccessDiv = $('.copy-success-ps'); // Div para mensajes de éxito de copia

        // Ocultar mensajes previos al hacer clic de nuevo
        copyErrorDiv.hide();
        copySuccessDiv.hide();

        // Verificar si el textarea existe y tiene contenido
        if (!htmlCodeTextarea.length || !htmlCodeTextarea.val() || htmlCodeTextarea.val().trim() === '') {
            // Mostrar error en el div específico para errores de copia
            copyErrorDiv.text('No hay contenido HTML para copiar. Asegúrate de tener inscripciones validadas.').fadeIn().delay(3000).fadeOut();
            return; // Salir de la función si no hay nada que copiar
        }

        // Seleccionar el contenido del textarea
        htmlCodeTextarea.select();
        // La siguiente línea es a veces usada para móviles, pero htmlCodeTextarea.select() suele ser suficiente.
        // Si tienes problemas en móviles, puedes probar a descomentarla:
        // htmlCodeTextarea[0].setSelectionRange(0, 99999); 

        try {
            // Intentar ejecutar el comando de copiar
            var successful = document.execCommand('copy');
            if (successful) {
                // Mostrar mensaje de éxito
                copySuccessDiv.text('¡Código HTML copiado al portapapeles!').fadeIn().delay(2000).fadeOut();
            } else {
                // Mostrar mensaje de error si execCommand devuelve false
                copyErrorDiv.text('Error al copiar. El navegador podría no soportar esta acción o los permisos fueron denegados. Intenta copiar manualmente.').fadeIn().delay(3000).fadeOut();
            }
        } catch (err) {
            // Capturar cualquier excepción durante el proceso de copia
            console.error('Error al intentar copiar con execCommand:', err);
            copyErrorDiv.text('Error crítico al copiar. Revisa la consola del navegador para más detalles.').fadeIn().delay(3000).fadeOut();
        }

        // Deseleccionar el texto copiado para una mejor experiencia de usuario
        if (window.getSelection) {
            if (window.getSelection().empty) {  // Para Chrome
                window.getSelection().empty();
            } else if (window.getSelection().removeAllRanges) {  // Para Firefox
                window.getSelection().removeAllRanges();
            }
        } else if (document.selection) {  // Para versiones antiguas de IE
            document.selection.empty();
        }
    });
	// Eliminar el cambio automático del selector (ya no lo necesitamos)
    // El cambio ahora se hace con el botón
    
    // Mantener sincronizado el selector visual con la carrera actual
    if (currentProductId) {
        $('#race-selector').val(currentProductId);
    }
});
