{ezscript_require( 'tools/ezjsselection.js' )}
<h3>Traduzioni automatiche schedulate</h3>
{if $error}
    <div class="alert">{$error|wash()}</div>
{/if}
{if count($entries)}
<form method="post" action="{'/translate/pending'|ezurl(no)}" name="pendingList">
    <table class="table table-hover table-sm">
        <thead>
        <tr>
            <th width="1">
                <img src={'toggle-button-16x16.gif'|ezimage} alt="{'Invert selection.'|i18n( 'design/admin/node/view/full' )}"
                     title="{'Invert selection.'|i18n( 'design/admin/node/view/full' )}"
                     onclick="ezjs_toggleCheckboxes( document.pendingList, 'Entry[]' ); return false;"/>
            </th>
            <th>Schedulata il</th>
            <th>Nome</th>
            <th>Tipologia</th>
            <th></th>
            <th width="1"></th>
        </tr>
        </thead>
        <tbody>
        {foreach $entries as $entry}
            <tr>
                <td width="1" class="align-middle">
                    <input name="Entry[]" type="checkbox" value="{$entry.id|wash()}" />
                </td>
                <td class="align-middle" style="white-space: nowrap">{$entry.created|l10n('shortdatetime')}</td>
                <td class="align-middle"><a target="_blank" rel="noopener"
                       href="{concat('openpa/object/', $entry.object.id)|ezurl(no)}">{$entry.object.name|wash}</a></td>
                <td class="align-middle" style="white-space: nowrap">{$entry.object.class_name|wash}</td>
                <td class="align-middle text-center" style="white-space: nowrap">
                    <img src="{$entry.from|flag_icon}" width="27" height="18" style="vertical-align: middle;" alt="{$entry.from|wash()}" title="{$entry.from|wash()}" />
                    <i class="fa fa-arrow-right"></i>
                    <img src="{$entry.to|flag_icon}" width="27" height="18" style="vertical-align: middle;" alt="{$entry.to|wash()}" title="{$entry.to|wash()}" />
                </td>
                <td class="align-middle"><button type="submit" name="Translate" value="{$entry.id|wash()}" class="btn btn-primary btn-xs">Traduci</button></td>
            </tr>
        {/foreach}
        </tbody>
        <tfoot>
        <tr>
            <td class="align-middle" colspan="6">
                <button type="submit" class="btn btn-danger btn-xs" name="Remove">Elimina selezionate</button>
            </td>
        </tr>
        </tfoot>
    </table>
</form>
{include name=Navigator
         uri='design:navigator/google.tpl'
         page_uri="/translate/pending"
         item_count=$entry_count
         view_parameters=$view_parameters
         item_limit=50}
{/if}

{if $fail_entry_count}
    <br />
    <h3>Errori di esecuzione</h3>
    <table class="table table-hover table-sm">
        <thead>
        <tr>
            <th>Eseguita il</th>
            <th>Nome</th>
            <th>Errore</th>
            <th></th>
            <th width="1"></th>
        </tr>
        </thead>
        <tbody>
        {foreach $fail_entries as $entry}
            <tr>
                <td class="align-middle" style="white-space: nowrap">{$entry.executed|l10n('shortdatetime')}</td>
                <td class="align-middle"><a target="_blank" rel="noopener"
                       href="{concat('openpa/object/', $entry.object.id)|ezurl(no)}">{$entry.object.name|wash}</a></td>
                <td class="align-middle" style="white-space: nowrap">{$entry.error|wash}</td>
                <td class="align-middle text-center" style="white-space: nowrap">
                    <img src="{$entry.from|flag_icon}" width="27" height="18" style="vertical-align: middle;" alt="{$entry.from|wash()}" title="{$entry.from|wash()}" />
                    <i class="fa fa-arrow-right"></i>
                    <img src="{$entry.to|flag_icon}" width="27" height="18" style="vertical-align: middle;" alt="{$entry.to|wash()}" title="{$entry.to|wash()}" />
                </td>
                <td class="align-middle"><button type="submit" name="Translate" value="{$entry.id|wash()}" class="btn btn-primary btn-xs">Traduci</button></td>
            </tr>
        {/foreach}
        </tbody>
        <tfoot>
        <tr>
            <td class="align-middle" colspan="6">
                <form method="post" action="{'/translate/pending'|ezurl(no)}" name="errorList">
                    <button type="submit" class="btn btn-danger btn-xs" name="EmptyError">Svuota registro errori</button>
                </form>
            </td>
        </tr>
        </tfoot>
    </table>
    {include name=Navigator
             uri='design:navigator/google.tpl'
             page_uri="/translate/pending"
             item_count=$entry_count
             view_parameters=$view_parameters
             item_limit=50}

{/if}