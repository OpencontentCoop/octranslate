{ezpagedata_set('show_path', false())}
<div class="row mt-5">
    <div class="col col-md-6 offset-md-3">
        {if $error|ne(false())}
        <form method="post" action="{concat('translate/content/', $object.id, '/', $from_language.locale, '/', $to_language.locale)|ezurl(no)}">
            <div class="alert alert-warning">
                <p class="lead m-0">{$error|wash()}</p>
            </div>
            <div class="form-group text-center mt-3">
                <button type="submit" name="Cancel" class="btn btn-outline-primary">Annulla</button>
            </div>
        </form>
        {else}
        <form method="post" action="{concat('translate/content/', $object.id, '/', $from_language.locale, '/', $to_language.locale)|ezurl(no)}">
            {if $already_exists}
                <h3>{'A %locale% translation already exists for the selected content'|i18n( 'octranslate', , hash('%locale%', $to_language.name) )}</h3>
                <p class="lead">{$object.name|wash()}</p>
                <div class="form-check">
                    <input name="ModifyTranslation" value="manual" type="radio" id="ModifyTranslation" checked>
                    <label for="ModifyTranslation">{'Manually edit the current translation'|i18n( 'octranslate')}</label>
                </div>
                <div class="form-check">
                    <input name="ModifyTranslation" value="auto" type="radio" id="RegenerateTranslation">
                    <label for="RegenerateTranslation">{'Regenerate the translation automatically and edit the content for review'|i18n( 'octranslate')}</label>
                </div>
              <div class="form-check">
                <input name="ModifyTranslation" value="auto-publish" type="radio" id="RegenerateAndPublishTranslation">
                <label for="RegenerateAndPublishTranslation">{'Regenerate the translation automatically and publish it'|i18n( 'octranslate')}</label>
              </div>
            {else}
                <h3>{'Create a new %locale% translation for the selected content'|i18n( 'octranslate', , hash('%locale%', $to_language.name) )}</h3>
                <p class="lead">{$object.name|wash()}</p>
                <div class="form-check">
                    <input name="CreateTranslation" value="publish" type="radio" id="PublishTranslation" checked>
                    <label for="PublishTranslation">{'Automatically translate the content and publish it'|i18n( 'octranslate')}</label>
                </div>
                <div class="form-check">
                    <input name="CreateTranslation" value="draft" type="radio" id="CreateDraft">
                    <label for="CreateDraft">{'Automatically translate without publishing and open the content for review'|i18n( 'octranslate')}</label>
                </div>
            {/if}
            {if $can_translate_document}
                <div class="form-check">
                    <input name="TranslateDocuments" value="1" type="checkbox" id="TranslateDocuments">
                    <label for="TranslateDocuments">{'Translate attachments'|i18n( 'octranslate')}</label>
                </div>
            {/if}
            <div class="form-group text-center mt-3">
                <button type="submit" name="Cancel" class="btn btn-outline-primary">Annulla</button>
                <button type="submit" name="Translate" class="btn btn-primary">Conferma</button>
            </div>
        </form>
        {/if}
    </div>
</div>