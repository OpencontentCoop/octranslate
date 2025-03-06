{ezscript_require(array(
    'handlebars.min.js',
    'moment-with-locales.min.js',
    'alpaca.js',
    'jquery.opendataform.js'
))}

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
    <style>
        [data-alpaca-field-name="_auto_class_list"] > .form-check.alpaca-control{
            display: inline-flex;
            width: 24%;
        }
    </style>
{/literal}