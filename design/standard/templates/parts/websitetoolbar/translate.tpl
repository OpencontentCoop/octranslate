{if and($content_object.can_translate, fetch( 'user', 'has_access_to', hash( 'module', 'translate', 'function', 'content' ) ))}
    <li>
        <div class="dropdown">
            <button class="btn btn-dropdown dropdown-toggle toolbar-more" type="button" id="dropdownTranslate" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i aria-hidden="true" class="fa fa-language"></i>
                <span class="toolbar-label">{'Translate'|i18n('design/standard/content/edit')}</span>
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownTranslate">
                <div class="link-list-wrapper">
                    <ul class="link-list">
                        {foreach fetch( 'content', 'prioritized_languages' ) as $available_language}
                            {if ne( $available_language.locale, $content_object.current_language )}
                            <li>
                                <a class="list-item left-icon"
                                   title="{'Edit in <%language_name>.'|i18n( 'design/admin/node/view/full',, hash( '%language_name', $available_language.locale_object.intl_language_name ) )|wash}"
                                   href="{concat('translate/content/',$content_object.id, '/', $content_object.current_language, '/', $available_language.locale)|ezurl(no)}">
                                    <img src="{$available_language.locale|flag_icon}" width="18" height="12" alt="{$available_language.locale}" /> {$available_language.name}
                                </a>
                            </li>
                            {/if}
                        {/foreach}
                        {if fetch( 'user', 'has_access_to', hash( 'module', 'translate', 'function', 'settings' ) )}
                            <li><span class="divider"></span></li>
                            <li>
                                <a class="list-item left-icon" href="{'translate/settings'|ezurl(no)}">
                                    <i aria-hidden="true" class="fa fa-gears"></i> {'Settings'|i18n( 'design/standard/setup' )}
                                </a>
                            </li>
                        {/if}
                    </ul>
                </div>
            </div>
        </div>
    </li>
{/if}