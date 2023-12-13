<?php

/** @var eZModule $Module */
$Module = $Params['Module'];
$Module->setExitStatus(eZModule::STATUS_IDLE);
$tpl = eZTemplate::factory();
$http = eZHTTPTool::instance();

$tpl->setVariable('handler', TranslatorManager::instance()->getHandler());

$Result = [];
$Result['content'] = $tpl->fetch('design:translator/settings.tpl');
$Result['path'] = $Result['title_path'] = [
    [
        'text' => 'Translator settings',
        'url' => false,
        'url_alias' => false,
    ],
];
$contentInfoArray = [
    'node_id' => null,
    'class_identifier' => null,
];
$contentInfoArray['persistent_variable'] = [
    'show_path' => false,
];
if (is_array($tpl->variable('persistent_variable'))) {
    $contentInfoArray['persistent_variable'] = array_merge(
        $contentInfoArray['persistent_variable'],
        $tpl->variable('persistent_variable')
    );
}
$Result['content_info'] = $contentInfoArray;