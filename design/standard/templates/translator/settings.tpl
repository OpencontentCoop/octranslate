{ezscript_require(array(
    'handlebars.min.js',
    'moment-with-locales.min.js',
    'alpaca.js',
    'jquery.opendataform.js'
))}
{ezcss_require(array('alpaca-custom.css'))}

<div class="row">
    <div class="col-12">
        <h3>{'Translator settings'|i18n( 'octranslate' )}</h3>
    </div>
    <div class="col-12">
        <div id="form" class="clearfix"></div>
    </div>
</div>

{literal}
    <script>
      $(document).ready(function () {
        let loadForm = function () {
          $("#form").opendataForm({}, {
            'connector': 'translator-settings',
            'onSuccess': function () {
              loadForm()
            }
          });
        }
        loadForm();
      });
    </script>
{/literal}