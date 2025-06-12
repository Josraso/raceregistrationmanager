{if isset($adminOrderUrl)}
<script>
    var adminOrderUrl = "{$adminOrderUrl|escape:'javascript'}";
    var ajaxUrl = "{$ajax_url|escape:'javascript'}";
    var selectedProduct = {$selected_product|intval};
</script>
{/if}

{* Estilos para el cursor del acordeón y los iconos *}
<style>
    .accordion-toggle {
        cursor: pointer;
    }
    .accordion-toggle .icon-indicator {
        transition: transform 0.2s ease-in-out;
    }
    .accordion-toggle[aria-expanded="true"] .icon-indicator {
        transform: rotate(180deg);
    }
</style>

<div class="panel race-registration-panel">
    <div class="panel-heading">
        <i class="icon-motorcycle"></i> {l s='Gestión de Inscripciones' mod='raceregistrationmanager'}
        {if isset($race_stats)}
            - <strong>{$race_stats.race_name}</strong>
        {/if}
    </div>

    <div id="success-message" class="alert alert-success" style="display: none;"></div>
    <div id="error-message" class="alert alert-danger" style="display: none;"></div>

    {* Determinar si la configuración ya existe para decidir el estado inicial del acordeón *}
    {assign var="isConfigured" value=(
        (isset($category_field) && $category_field != '') ||
        (isset($list_field) && $list_field != '') ||
        (isset($cms_page_id) && $cms_page_id > 0) ||
        (isset($display_fields) && !empty($display_fields))
    )}

    <form method="post" class="form-horizontal">
        {* Panel para "Configuración para esta carrera" - Ahora un acordeón *}
        <div class="panel">
            <div class="panel-heading accordion-toggle" data-toggle="collapse" data-target="#configRaceAccordionBody" aria-expanded="{if !$isConfigured}true{else}false{/if}" aria-controls="configRaceAccordionBody">
                <i class="icon-cogs"></i> {l s='Configuración para esta carrera' mod='raceregistrationmanager'}
                <span class="pull-right"><i class="icon-chevron-down icon-indicator"></i></span>
            </div>
            
            {* Contenido plegable del acordeón. Se expande si no está configurado *}
            <div id="configRaceAccordionBody" class="panel-collapse collapse {if !$isConfigured}in{/if}">
                {* Envolvemos el contenido en panel-body para un padding adecuado *}
                <div class="panel-body">
                    <div class="form-group">
                        <label class="control-label col-lg-3">{l s='Campo para categoría' mod='raceregistrationmanager'}</label>
                        <div class="col-lg-9">
                            <select name="category_field" class="form-control">
                                {foreach from=$available_fields key=field_key item=field_name}
                                    <option value="{$field_key}" {if isset($category_field) && $category_field == $field_key}selected{/if}>
                                        {$field_name}
                                    </option>
                                {/foreach}
                            </select>
                            <p class="help-block">{l s='Selecciona el campo que se usará para agrupar las inscripciones' mod='raceregistrationmanager'}</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">{l s='Campo para mostrar en listado' mod='raceregistrationmanager'}</label>
                        <div class="col-lg-9">
                            <select name="list_field" class="form-control">
                                <option value="">-- {l s='Ninguno (mostrar cliente)' mod='raceregistrationmanager'} --</option>
                                {foreach from=$available_fields key=field_key item=field_name}
                                    <option value="{$field_key}" {if isset($list_field) && $list_field == $field_key}selected{/if}>
                                        {$field_name}
                                    </option>
                                {/foreach}
                            </select>
                            <p class="help-block">{l s='Selecciona el campo que se mostrará en el listado de pedidos' mod='raceregistrationmanager'}</p>
                        </div>
                    </div>
                    
                    
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">{l s='Campos a mostrar' mod='raceregistrationmanager'}</label>
                        <div class="col-lg-9">
                            <p class="help-block">{l s='Selecciona y ordena los campos que deseas mostrar. Usa las flechas para cambiar el orden.' mod='raceregistrationmanager'}</p>
                            <div class="field-selection">
                                <table class="table field-table" id="fields-table">
                                    <thead>
                                        <tr>
                                            <th width="10%">Orden</th>
                                            <th width="10%">Mostrar</th>
                                            <th width="65%">Campo</th>
                                            <th width="15%">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {foreach from=$available_fields key=field_key item=field_name name=fields}
                                            <tr class="field-row" data-field="{$field_key}">
                                                <td>
                                                    <span class="order-num badge">{if isset($display_fields[$field_key])}{$display_fields[$field_key]}{else}{$smarty.foreach.fields.iteration}{/if}</span>
                                                    <input type="hidden" name="field_order[{$field_key}]" value="{if isset($display_fields[$field_key])}{$display_fields[$field_key]}{else}{$smarty.foreach.fields.iteration}{/if}" class="order-input">
                                                </td>
                                                <td>
                                                    <input type="checkbox" name="display_fields[]" value="{$field_key}|{if isset($display_fields[$field_key])}{$display_fields[$field_key]}{else}{$smarty.foreach.fields.iteration}{/if}" {if isset($display_fields[$field_key])}checked{/if} class="field-checkbox" data-field="{$field_key}">
                                                </td>
                                                <td>{$field_name}</td>
                                                <td>
                                                    <button type="button" class="btn btn-default btn-xs move-up" title="Mover arriba"><i class="icon-caret-up"></i></button>
                                                    <button type="button" class="btn btn-default btn-xs move-down" title="Mover abajo"><i class="icon-caret-down"></i></button>
                                                </td>
                                            </tr>
                                        {/foreach}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="col-lg-offset-3 col-lg-9">
                            <button type="submit" name="submitSettings" class="btn btn-primary">
                                <i class="icon-save"></i> {l s='Guardar configuración' mod='raceregistrationmanager'}
                            </button>
                        </div>
                    </div>
                </div>
                {* Fin .panel-body *}
            </div>
            {* Fin #configRaceAccordionBody .panel-collapse *}
        </div>
        {* Fin .panel (acordeón de configuración) *}
    </form>

    <div class="panel">
        <div class="panel-heading">
            <i class="icon-list"></i> {l s='Listado de Pedidos' mod='raceregistrationmanager'}
        </div>
        
        {* Aquí continúa el resto de tu código para filtros y listado de pedidos *}
        <div class="panel">
            <form id="filter-form" method="post" class="form-inline">
                <input type="hidden" name="selected_product" value="{$selected_product}">
                <div class="row">
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label>{l s='Referencia:' mod='raceregistrationmanager'}</label>
                            <input type="text" class="form-control" name="filter_reference" value="{if isset($filters.reference)}{$filters.reference}{/if}">
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label>{l s='Estado:' mod='raceregistrationmanager'}</label>
                            <select name="filter_state" class="form-control">
                                <option value="0">-- {l s='Todos' mod='raceregistrationmanager'} --</option>
                                {foreach from=$order_states item=state}
                                    <option value="{$state.id_order_state}" {if isset($filters.state) && $filters.state == $state.id_order_state}selected{/if}>
                                        {$state.name}
                                    </option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label class="filter-status-label">{l s='Validado:' mod='raceregistrationmanager'}</label>
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-default {if !isset($filters.validated) || $filters.validated === ''}active{/if}">
                                    <input type="radio" name="filter_validated" value="" {if !isset($filters.validated) || $filters.validated === ''}checked{/if}> {l s='Todos' mod='raceregistrationmanager'}
                                </label>
                                <label class="btn btn-default {if isset($filters.validated) && $filters.validated === '1'}active{/if}">
                                    <input type="radio" name="filter_validated" value="1" {if isset($filters.validated) && $filters.validated === '1'}checked{/if}> {l s='Sí' mod='raceregistrationmanager'}
                                </label>
                                <label class="btn btn-default {if isset($filters.validated) && $filters.validated === '0'}active{/if}">
                                    <input type="radio" name="filter_validated" value="0" {if isset($filters.validated) && $filters.validated === '0'}checked{/if}> {l s='No' mod='raceregistrationmanager'}
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label class="filter-status-label">{l s='Publicado:' mod='raceregistrationmanager'}</label>
                            <div class="btn-group" data-toggle="buttons">
                                <label class="btn btn-default {if !isset($filters.published) || $filters.published === ''}active{/if}">
                                    <input type="radio" name="filter_published" value="" {if !isset($filters.published) || $filters.published === ''}checked{/if}> {l s='Todos' mod='raceregistrationmanager'}
                                </label>
                                <label class="btn btn-default {if isset($filters.published) && $filters.published === '1'}active{/if}">
                                    <input type="radio" name="filter_published" value="1" {if isset($filters.published) && $filters.published === '1'}checked{/if}> {l s='Sí' mod='raceregistrationmanager'}
                                </label>
                                <label class="btn btn-default {if isset($filters.published) && $filters.published === '0'}active{/if}">
                                    <input type="radio" name="filter_published" value="0" {if isset($filters.published) && $filters.published === '0'}checked{/if}> {l s='No' mod='raceregistrationmanager'}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row" style="margin-top: 10px;">
                    <div class="col-lg-2">
                        <div class="form-group">
                            <label>{l s='Desde:' mod='raceregistrationmanager'}</label>
                            <input type="date" class="form-control" name="filter_date_from" value="{if isset($filters.date_from)}{$filters.date_from}{/if}">
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="form-group">
                            <label>{l s='Hasta:' mod='raceregistrationmanager'}</label>
                            <input type="date" class="form-control" name="filter_date_to" value="{if isset($filters.date_to)}{$filters.date_to}{/if}">
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <button type="submit" name="submitFilter" id="filter-button" class="btn btn-default">
                                <i class="icon-search"></i> {l s='Filtrar' mod='raceregistrationmanager'}
                            </button>
                            <a href="{$action_url}&selected_product={$selected_product}" class="btn btn-link">
                                <i class="icon-remove"></i> {l s='Limpiar filtros' mod='raceregistrationmanager'}
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4 text-right items-per-page">
                        <div class="form-group">
                            <label>{l s='Items por página:' mod='raceregistrationmanager'}</label>
                            <select name="items_per_page" class="form-control" onchange="this.form.submit()">
                                <option value="10" {if isset($pagination.items_per_page) && $pagination.items_per_page == 10}selected{/if}>10</option>
                                <option value="15" {if isset($pagination.items_per_page) && $pagination.items_per_page == 15}selected{/if}>15</option>
                                <option value="25" {if isset($pagination.items_per_page) && $pagination.items_per_page == 25}selected{/if}>25</option>
                                <option value="50" {if isset($pagination.items_per_page) && $pagination.items_per_page == 50}selected{/if}>50</option>
                                <option value="100" {if isset($pagination.items_per_page) && $pagination.items_per_page == 100}selected{/if}>100</option>
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="table-responsive">
            {if isset($registrations) && $registrations|count > 0}
            <form method="post" id="order-selection-form">
                <input type="hidden" name="selected_product" value="{$selected_product}">
                <table class="table">
                    <thead>
                        <tr>
                            <th width="5%"><input type="checkbox" id="select-all"></th>
                            <th width="5%">ID</th>
                            <th width="8%">Pedido</th>
                            <th width="15%">
                                {if isset($list_field) && $list_field && isset($available_fields[$list_field])}
                                    {$available_fields[$list_field]|escape:'html'}
                                {else}
                                    Cliente
                                {/if}
                            </th>
                            <th width="8%">Estado</th>
                            <th width="13%">Fecha</th>
                            <th width="8%">Validado</th>
                            <th width="8%">Publicado</th>
                            <th width="10%">Inscrito</th>
                            <th width="20%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$registrations item=reg}
                            <tr id="order-row-{$reg.id_order}" class="{if isset($reg.validated) && $reg.validated}validated{/if} {if isset($reg.published) && $reg.published}published{/if} {if isset($reg.multiple_registrations) && $reg.multiple_registrations}multiple-registrations{/if}">
                                <td><input type="checkbox" class="order-checkbox" name="order_ids[]" value="{$reg.id_order}"></td>
                                <td>{$reg.id_order}</td>
                                <td>
                                    #{$reg.reference}
                                    {if isset($reg.registration_count) && $reg.registration_count > 1}
                                        <span class="registration-badge" title="{l s='Múltiples inscripciones' mod='raceregistrationmanager'}">{$reg.registration_count}</span>
                                    {/if}
                                </td>
                                <td>
                                    {if isset($list_field) && $list_field && isset($reg.field_data) && isset($reg.field_data[$list_field])}
                                        {$reg.field_data[$list_field]|escape:'html'}
                                    {else}
                                        {$reg.firstname|escape:'html'} {$reg.lastname|escape:'html'}
                                    {/if}
                                </td>
                                <td>{$reg.order_state}</td>
                                <td>{dateFormat date=$reg.date_add full=true}</td>
                                <td class="text-center validated-cell">
                                    {if isset($reg.validated) && $reg.validated}
                                        <i class="icon-check text-success"></i>
                                    {else}
                                        <i class="icon-remove text-danger"></i>
                                    {/if}
                                </td>
                                <td class="text-center published-cell">
                                    {if isset($reg.published) && $reg.published}
                                        <i class="icon-check text-success"></i>
                                    {else}
                                        <i class="icon-remove text-danger"></i>
                                    {/if}
                                </td>
                                <td class="inscrito-cell">
                                    {if isset($reg.published) && $reg.published}
                                        <span class="inscrito-badge inscrito">Inscrito</span>
                                    {else}
                                        <span class="inscrito-badge no-inscrito">No inscrito</span>
                                    {/if}
                                </td>
                                <td class="text-center actions">
                                    <a href="{$adminOrderUrl}&id_order={$reg.id_order}&vieworder" class="btn btn-xs btn-info" target="_blank">
                                        <i class="icon-eye"></i> {l s='Ver' mod='raceregistrationmanager'}
                                    </a>
                                    
                                    {if !isset($reg.validated) || !$reg.validated}
                                        <a href="javascript:void(0)" class="btn btn-xs btn-success process-order" data-id="{$reg.id_order}">
                                            <i class="icon-check"></i> {l s='Procesar y validar' mod='raceregistrationmanager'}
                                        </a>
                                    {elseif !isset($reg.published) || !$reg.published}
                                        <a href="javascript:void(0)" class="btn btn-xs btn-primary publish-order" data-id="{$reg.id_registration}">
                                            <i class="icon-upload"></i> {l s='Publicar' mod='raceregistrationmanager'}
                                        </a>
                                    {else}
                                        <a href="javascript:void(0)" class="btn btn-xs btn-danger unpublish-order" data-id="{$reg.id_registration}">
                                            <i class="icon-remove"></i> {l s='Anular publicación' mod='raceregistrationmanager'}
                                        </a>
                                        <a href="javascript:void(0)" class="btn btn-xs btn-danger unvalidate-order" data-id="{$reg.id_order}">
                                            <i class="icon-ban-circle"></i> {l s='Anular validación' mod='raceregistrationmanager'}
                                        </a>
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>

                <div class="panel-footer">
                    <div class="row">
                        <div class="col-lg-12">
                            <button type="submit" name="processValidateSelectedOrders" class="btn btn-success" id="process-selected">
                                <i class="icon-check"></i> {l s='Procesar y validar seleccionados' mod='raceregistrationmanager'}
                            </button>
                            
                            <button type="submit" name="publishSelectedOrders" class="btn btn-primary" id="publish-selected">
                                <i class="icon-upload"></i> {l s='Publicar seleccionados' mod='raceregistrationmanager'}
                            </button>
                            
                            <button type="submit" name="unpublishSelectedOrders" class="btn btn-danger" id="unpublish-selected">
                                <i class="icon-remove"></i> {l s='Anular publicación seleccionados' mod='raceregistrationmanager'}
                            </button>
                            
                            <button type="submit" name="unvalidateSelectedOrders" class="btn btn-danger" id="unvalidate-selected">
                                <i class="icon-ban-circle"></i> {l s='Anular validación seleccionados' mod='raceregistrationmanager'}
                            </button>
                        </div>
                    </div>
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-lg-12 text-right">
                            {if isset($pagination.pages) && $pagination.pages > 1}
                            <ul class="pagination">
                                {if $pagination.current > 1}
                                    <li>
                                        <a href="{$action_url}&selected_product={$selected_product}&page=1&items_per_page={$pagination.items_per_page}" title="{l s='Primera página' mod='raceregistrationmanager'}">
                                            <i class="icon-step-backward"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{$action_url}&selected_product={$selected_product}&page={$pagination.current - 1}&items_per_page={$pagination.items_per_page}" title="{l s='Página anterior' mod='raceregistrationmanager'}">
                                            <i class="icon-chevron-left"></i>
                                        </a>
                                    </li>
                                {/if}
                                
                                {assign var=start_page value=max(1, $pagination.current-2)}
                                {assign var=end_page value=min($pagination.pages, $pagination.current+2)}
                                
                                {for $page_num=$start_page to $end_page}
                                    <li {if $page_num == $pagination.current}class="active"{/if}>
                                        <a href="{$action_url}&selected_product={$selected_product}&page={$page_num}&items_per_page={$pagination.items_per_page}">{$page_num}</a>
                                    </li>
                                {/for}
                                
                                {if $pagination.current < $pagination.pages}
                                    <li>
                                        <a href="{$action_url}&selected_product={$selected_product}&page={$pagination.current + 1}&items_per_page={$pagination.items_per_page}" title="{l s='Página siguiente' mod='raceregistrationmanager'}">
                                            <i class="icon-chevron-right"></i>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{$action_url}&selected_product={$selected_product}&page={$pagination.pages}&items_per_page={$pagination.items_per_page}" title="{l s='Última página' mod='raceregistrationmanager'}">
                                            <i class="icon-step-forward"></i>
                                        </a>
                                    </li>
                                {/if}
                            </ul>
                            {/if}
                        </div>
                    </div>
                </div>
            </form>
            {else}
                <div class="alert alert-info">{l s='No hay pedidos para esta carrera que coincidan con los criterios' mod='raceregistrationmanager'}</div>
            {/if}
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Manejo del icono del acordeón
    // Este JS es opcional si la animación CSS con [aria-expanded="true"] ya maneja el icono visualmente.
    // Sin embargo, cambiar la clase explícitamente puede ser más robusto o si no se usa la rotación CSS.
    $('#configRaceAccordionBody').on('show.bs.collapse', function () {
        $('div.panel-heading[data-target="#configRaceAccordionBody"] .icon-indicator')
            .removeClass('icon-chevron-down')
            .addClass('icon-chevron-up');
    }).on('hide.bs.collapse', function () {
        $('div.panel-heading[data-target="#configRaceAccordionBody"] .icon-indicator')
            .removeClass('icon-chevron-up')
            .addClass('icon-chevron-down');
    });

    // Asegurar el estado inicial correcto del icono al cargar la página, si no se usa la rotación CSS por [aria-expanded]
    var configAccordion = $('#configRaceAccordionBody');
    var configAccordionIcon = $('div.panel-heading[data-target="#configRaceAccordionBody"] .icon-indicator');
    if (configAccordion.hasClass('in')) {
        if (configAccordionIcon.length) { // Asegurarse que el icono existe
            configAccordionIcon.removeClass('icon-chevron-down').addClass('icon-chevron-up');
        }
    } else {
        if (configAccordionIcon.length) { // Asegurarse que el icono existe
            configAccordionIcon.removeClass('icon-chevron-up').addClass('icon-chevron-down');
        }
    }
});
</script>