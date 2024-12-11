{def $is_auto_translated = fetch('translate', 'is_auto_translated', hash(
   'version', $node.object.current
))}
{if $is_auto_translated}
<div class="container">
    <div class="alert alert-warning">
        {'This content is translated with an automatic translation tool: the text may contain inaccurate information.'|i18n( 'octranslate')}
    </div>
</div>
{/if}