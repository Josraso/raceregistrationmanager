{if isset($race_products) && $race_products|count > 0}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-flag-checkered"></i> {l s='Selector de Carrera' mod='raceregistrationmanager'}
    </div>
    
    <div class="panel-body">
        <form method="post" class="form-horizontal">
            <div class="form-group">
                <label class="control-label col-lg-2">{l s='Carrera activa:' mod='raceregistrationmanager'}</label>
                <div class="col-lg-5">
                    <select name="race_selector" class="form-control" id="race-selector">
                        <option value="">-- {l s='Selecciona una carrera' mod='raceregistrationmanager'} --</option>
                        {foreach from=$race_products item=race}
                            {assign var="is_archived" value=($race.race_status == 'archived')}
                            <option value="{$race.id_product}" {if $selected_product == $race.id_product}selected{/if} 
                                    {if $is_archived}style="color: #999; font-style: italic;"{/if}>
                                {$race.name} 
                                {if $is_archived}
                                    [{l s='ARCHIVADA' mod='raceregistrationmanager'}]
                                {/if}
                                {if $race.registrations_count > 0}
                                    ({$race.registrations_count} {l s='inscripciones' mod='raceregistrationmanager'})
                                {/if}
                            </option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-lg-5">
                    <button type="submit" name="changeRaceBtn" class="btn btn-primary">
                        <i class="icon-refresh"></i> {l s='Cambiar a esta carrera' mod='raceregistrationmanager'}
                    </button>
                    <button type="button" class="btn btn-default" onclick="$('#archived-races-panel').toggle();">
                        <i class="icon-archive"></i> {l s='Ver archivadas' mod='raceregistrationmanager'}
                    </button>
                </div>
            </div>
        </form>
        
        {if isset($race_stats) && $race_stats}
        <div class="row" style="margin-top: 20px;">
            <div class="col-lg-12">
                <div class="alert alert-info">
                    <h4>{l s='Estadísticas de la carrera actual:' mod='raceregistrationmanager'} <strong>{$race_stats.race_name}</strong></h4>
                    <div class="row">
                        <div class="col-md-3">
                            <strong>{l s='Total inscripciones:' mod='raceregistrationmanager'}</strong> {$race_stats.total_registrations}
                        </div>
                        <div class="col-md-3">
                            <strong>{l s='Validadas:' mod='raceregistrationmanager'}</strong> {$race_stats.validated}
                        </div>
                        <div class="col-md-3">
                            <strong>{l s='Publicadas:' mod='raceregistrationmanager'}</strong> {$race_stats.published}
                        </div>
                        <div class="col-md-3">
                            <strong>{l s='Estado:' mod='raceregistrationmanager'}</strong> 
                            <span class="label {if $race_stats.race_status == 'active'}label-success{else}label-default{/if}">
                                {if $race_stats.race_status == 'active'}{l s='Activa' mod='raceregistrationmanager'}{else}{l s='Archivada' mod='raceregistrationmanager'}{/if}
                            </span>
                        </div>
                    </div>
                    {if $race_stats.race_status == 'archived' && $race_stats.archive_date}
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-md-12">
                            <strong>{l s='Fecha de archivo:' mod='raceregistrationmanager'}</strong> {dateFormat date=$race_stats.archive_date full=true}
                        </div>
                    </div>
                    {/if}
                </div>
            </div>
        </div>
        {/if}
        
        <div class="row">
            <div class="col-lg-12">
                {if isset($race_stats) && $race_stats.race_status == 'active'}
                <form method="post" class="form-inline" style="display: inline-block;">
                    <input type="hidden" name="archive_product" value="{$selected_product}">
                    <input type="hidden" name="selected_product" value="{$selected_product}">
                    <button type="submit" name="archiveRace" class="btn btn-warning" 
                            onclick="return confirm('{l s='¿Estás seguro de que quieres archivar esta carrera?' mod='raceregistrationmanager' js=1}');">
                        <i class="icon-archive"></i> {l s='Archivar carrera actual' mod='raceregistrationmanager'}
                    </button>
                </form>
                {elseif isset($race_stats) && $race_stats.race_status == 'archived'}
                <form method="post" class="form-inline" style="display: inline-block;">
                    <input type="hidden" name="unarchive_product" value="{$selected_product}">
                    <input type="hidden" name="selected_product" value="{$selected_product}">
                    <button type="submit" name="unarchiveRace" class="btn btn-success" 
                            onclick="return confirm('{l s='¿Estás seguro de que quieres desarchivar esta carrera?' mod='raceregistrationmanager' js=1}');">
                        <i class="icon-folder-open"></i> {l s='Desarchivar carrera' mod='raceregistrationmanager'}
                    </button>
                </form>
                {/if}
                <span class="help-block" style="display: inline-block; margin-left: 10px;">
                    {if isset($race_stats) && $race_stats.race_status == 'archived'}
                        {l s='Esta carrera está archivada. Puedes ver todos los datos pero no se pueden hacer nuevas inscripciones.' mod='raceregistrationmanager'}
                    {else}
                        {l s='Archivar una carrera la marca como finalizada pero mantiene todos los datos' mod='raceregistrationmanager'}
                    {/if}
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Panel de carreras archivadas -->
<div class="panel" id="archived-races-panel" style="display: none;">
    <div class="panel-heading">
        <i class="icon-archive"></i> {l s='Carreras Archivadas' mod='raceregistrationmanager'}
        <span class="badge pull-right">
            {assign var="archived_count" value=0}
            {foreach from=$race_products item=race}
                {if $race.race_status == 'archived'}
                    {assign var="archived_count" value=$archived_count+1}
                {/if}
            {/foreach}
            {$archived_count}
        </span>
    </div>
    <div class="panel-body">
        {if $archived_count > 0}
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>{l s='Carrera' mod='raceregistrationmanager'}</th>
                    <th>{l s='Inscripciones' mod='raceregistrationmanager'}</th>
                    <th>{l s='Referencia' mod='raceregistrationmanager'}</th>
                    <th>{l s='Acciones' mod='raceregistrationmanager'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$race_products item=race}
                    {if $race.race_status == 'archived'}
                    <tr>
                        <td><strong>{$race.name}</strong></td>
                        <td>
                            <span class="badge">{$race.registrations_count}</span>
                        </td>
                        <td>{$race.reference}</td>
                        <td>
                            <form method="post" style="display: inline-block;">
                                <input type="hidden" name="race_selector" value="{$race.id_product}">
                                <button type="submit" name="changeRaceBtn" class="btn btn-sm btn-info">
                                    <i class="icon-eye"></i> {l s='Ver detalles' mod='raceregistrationmanager'}
                                </button>
                            </form>
                            <form method="post" style="display: inline-block;">
                                <input type="hidden" name="unarchive_product" value="{$race.id_product}">
                                <input type="hidden" name="selected_product" value="{$race.id_product}">
                                <button type="submit" name="unarchiveRace" class="btn btn-sm btn-success"
                                        onclick="return confirm('{l s='¿Desarchivar esta carrera?' mod='raceregistrationmanager' js=1}');">
                                    <i class="icon-folder-open"></i> {l s='Desarchivar' mod='raceregistrationmanager'}
                                </button>
                            </form>
                        </td>
                    </tr>
                    {/if}
                {/foreach}
            </tbody>
        </table>
        {else}
        <div class="alert alert-info">
            {l s='No hay carreras archivadas actualmente.' mod='raceregistrationmanager'}
        </div>
        {/if}
    </div>
</div>

{else}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-warning"></i> {l s='Sin carreras disponibles' mod='raceregistrationmanager'}
    </div>
    <div class="panel-body">
        <p>{l s='No se han encontrado productos que parezcan ser carreras.' mod='raceregistrationmanager'}</p>
        <p>{l s='Crea productos con "carrera" o "race" en el nombre, o con referencia que empiece por "RACE".' mod='raceregistrationmanager'}</p>
    </div>
</div>
{/if}