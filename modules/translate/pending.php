<?php

/** @var eZModule $Module */
$Module = $Params['Module'];
$Module->setExitStatus(eZModule::STATUS_IDLE);
$tpl = eZTemplate::factory();
$http = eZHTTPTool::instance();

if ($http->hasPostVariable('Remove')) {
    $removeEntries = array_filter($http->postVariable('Entry'), 'intval');
    if (!empty($removeEntries)) {
        eZPendingActions::removeObject(eZPendingActions::definition(), [
            'action' => TranslatorManager::PENDING_ACTION,
            'id' => [$removeEntries],
        ]);
    }
    $Module->redirectTo('/translate/pending');
    return;
}
$error = false;
if ($http->hasPostVariable('Translate')) {
    $entryId = (int)$http->postVariable('Translate');
    $entry = eZPendingActions::fetchObject(
        eZPendingActions::definition(),
        null,
        ['id' => $entryId, 'action' => TranslatorManager::PENDING_ACTION]
    );
    if ($entry instanceof eZPendingActions) {
        [$result, $error] = TranslatorManager::processPendingAction($entry);
        if ($result) {
            $Module->redirectTo('/translate/pending');
            return;
        }
    }
}

$offset = $Params['Offset'];
if (!is_numeric($offset)) {
    $offset = 0;
}
$viewParameters = ['offset' => $offset];
$limit = 50;
$filterConds = ['action' => TranslatorManager::PENDING_ACTION];
$entryCount = TranslatorManager::instance()->countPendingActions();
$entries = eZPendingActions::fetchObjectList(
    eZPendingActions::definition(),
    null,
    $filterConds,
    ['created' => 'desc'],
    [
        'limit' => $limit,
        'offset' => $offset,
    ],
    false
);
$decodedEntries = [];
foreach ($entries as $entry) {
    $data = json_decode($entry['param'], true);
    $object = eZContentObject::fetch((int)$data['id']);
    $decodedEntries[] = [
        'id' => $entry['id'],
        'created' => $entry['created'],
        'object' => $object,
        'from' => $data['from'],
        'to' => $data['to'],
    ];
}

$tpl->setVariable('view_parameters', $viewParameters);
$tpl->setVariable('entries', $decodedEntries);
$tpl->setVariable('entry_count', $entryCount);
$tpl->setVariable('error', $error);

$Result = [];
$Result['content'] = $tpl->fetch('design:translator/pending.tpl');
$Result['path'] = $Result['title_path'] = [
    [
        'text' => 'Translator pending',
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