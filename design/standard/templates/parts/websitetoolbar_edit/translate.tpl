{if $edit_language|eq('ita-IT')}
<li>
    <div class="form-check form-check-group pb-0 my-0 border-0" style="box-shadow:none">
        <div class="toggles">
            <label for="AutoTranslationStatus" class="mb-0 font-weight-normal" style="max-width: 100px;">
                <input type="checkbox" data-id="{$content_object.id}" id="AutoTranslationStatus"
                       value="1"{if $content_object.state_identifier_array|contains('translation/manual')|not()} checked="checked"{/if}>
                <span class="lever" style="float:none;display: inline-block;margin-bottom: 5px;"></span>
                <span class="toolbar-label"
                      style="font-size: .65em;">{'Translate automatically'|i18n( 'bootstrapitalia' )}</span>
            </label>
        </div>
    </div>
</li>
<script>{literal}
  $(document).ready(function () {
    $('#AutoTranslationStatus').on('change', function (e) {
      let self = $(this)
      let id = self.data('id');
      let checked = self.is(':checked')
      let status = checked ? 'automatic' : 'manual'
      $.ajax({
        type: 'GET',
        url: '/translate/set-mode/' + status + '/' + id,
        data: {format: 'json'},
        success: function (response) {
          if (response.status === 'error') {
            self.prop('checked', !checked);
          }
        },
        error: function () {
          self.prop('checked', !checked);
        }
      });
    });
  });
{/literal}</script>
{/if}