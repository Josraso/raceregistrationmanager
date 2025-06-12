{* Este es un archivo de plantilla, probablemente para PrestaShop (.tpl) *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-code"></i> {l s='Exportar datos' mod='raceregistrationmanager'}
        {if isset($race_stats)}
            - <strong>{$race_stats.race_name}</strong>
        {/if}
    </div>
    
    <div class="panel-body">
        <div class="alert alert-info">
            <p><strong>{l s='Esta sección te permite exportar los datos de inscripciones validadas de la carrera actual:' mod='raceregistrationmanager'}</strong></p>
            <ul>
                <li>{l s='HTML PrestaShop - Formato adaptado al estilo de PrestaShop' mod='raceregistrationmanager'}</li>
                <li>{l s='CSV - Formato para importar en Excel o Google Sheets' mod='raceregistrationmanager'}</li>
            </ul>
            {if isset($race_stats)}
                <p><strong>{l s='Inscripciones validadas en esta carrera:' mod='raceregistrationmanager'}</strong> {$race_stats.validated}</p>
            {/if}
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label><i class="icon-download"></i> {l s='Descargar en diferentes formatos:' mod='raceregistrationmanager'}</label>
                    <div class="btn-group">
                        <a href="{$action_url}&selected_product={$selected_product}&action=downloadPrestashopHtml" class="btn btn-primary" target="_blank">
                            <i class="icon-html5"></i> {l s='Descargar HTML PrestaShop' mod='raceregistrationmanager'}
                        </a>
                        <a href="{$action_url}&selected_product={$selected_product}&action=downloadCsv" class="btn btn-success" target="_blank">
                            <i class="icon-table"></i> {l s='Descargar CSV' mod='raceregistrationmanager'}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        {if isset($exportable_prestashop_html) && $exportable_prestashop_html}
        <div class="form-group">
            <label>{l s='HTML con formato PrestaShop:' mod='raceregistrationmanager'}</label>
            <div class="input-group-btn">
                <button type="button" class="btn btn-default" id="copy-prestashop-btn">
                    <i class="icon-copy"></i> {l s='Copiar al portapapeles' mod='raceregistrationmanager'}
                </button>
            </div>
        </div>
        <div class="alert alert-success copy-success-ps" style="display: none;">
            <i class="icon-check"></i> {l s='¡Código HTML copiado al portapapeles!' mod='raceregistrationmanager'}
        </div>
        <div class="alert alert-danger copy-error-ps" style="display: none;">
            <i class="icon-warning"></i> {l s='Error al copiar. Por favor, selecciona y copia manualmente.' mod='raceregistrationmanager'}
        </div>
        <textarea id="prestashop-html" class="form-control" rows="10" readonly style="font-family: monospace; background-color: #f5f5f5;">{$exportable_prestashop_html|escape:'html'}</textarea>
        <p class="help-block">{l s='Este código está formateado con clases de Bootstrap para un mejor aspecto en PrestaShop.' mod='raceregistrationmanager'}</p>
        {else}
        <div class="alert alert-warning">
            <i class="icon-exclamation-triangle"></i> {l s='No hay inscripciones validadas para mostrar en esta carrera.' mod='raceregistrationmanager'}
        </div>
        {/if}
        
        <div class="panel-footer">
            <p><i class="icon-info-circle"></i> {l s='Consejo: El formato PrestaShop es el recomendado para usar en la página CMS configurada para esta carrera.' mod='raceregistrationmanager'}</p>
        </div>
    </div>
</div>