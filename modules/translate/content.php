<?php

/** @var eZModule $module */
$module = $Params['Module'];
$http = eZHTTPTool::instance();
$tpl = eZTemplate::factory();
$http = eZHTTPTool::instance();

$sourceLanguage = $Params['From'];
$targetLanguage = $Params['To'];
$objectId = (int)$Params['Id'];

$sourceContentLanguage = eZContentLanguage::fetchByLocale($sourceLanguage);
if ($sourceContentLanguage === false) {
    return $module->handleError(eZError::KERNEL_NOT_AVAILABLE, 'kernel');
}

$targetContentLanguage = eZContentLanguage::fetchByLocale($targetLanguage);
if ($targetContentLanguage === false) {
    return $module->handleError(eZError::KERNEL_NOT_AVAILABLE, 'kernel');
}

$object = eZContentObject::fetch($objectId);
if (!$object instanceof eZContentObject) {
    return $module->handleError(eZError::KERNEL_NOT_FOUND, 'kernel');
}
if (!$object->canEdit() || !$object->canTranslate()) {
    return $module->handleError(eZError::KERNEL_ACCESS_DENIED, 'kernel');
}

if ($http->hasPostVariable('Cancel')) {
    /** @var eZContentObjectTreeNode $mainNode */
    $mainNode = $object->mainNode();
    $module->redirectTo($mainNode->attribute('url_alias'));
    return;
}

$translationAlreadyExits = in_array($targetLanguage, $object->availableLanguages());
$translator = TranslatorManager::instance();

$error = false;
try {
    if ($http->hasPostVariable('Translate')) {
        if ($http->hasPostVariable('TranslateDocuments')){
            $translator->setIsDocumentTranslationEnabled(true);
        }
        $createDraft = false;
        if ($http->hasPostVariable('ModifyTranslation') && $translationAlreadyExits) {
            $createDraft = $http->postVariable('ModifyTranslation') === 'auto';
            if (!$createDraft) {
                $module->redirectTo('content/edit/' . $object->attribute('id') . '/f/' . $targetLanguage);
                return;
            }
        } elseif ($http->hasPostVariable('CreateTranslation') && !$translationAlreadyExits) {
            $createDraft = $http->postVariable('CreateTranslation') === 'draft';
            if (!$createDraft) {
                if ($translator->createAndPublishTranslation(
                    $object,
                    $sourceLanguage,
                    $targetLanguage
                )) {
                    /** @var eZContentObjectTreeNode $mainNode */
                    $mainNode = $object->mainNode();
                    $module->redirectTo(TranslatorManager::getLocaleUrl($mainNode, $targetLanguage));
                    return;
                } else {
                    return $module->handleError(eZError::KERNEL_NOT_AVAILABLE, 'kernel');
                }
            }
        }
        if ($createDraft) {
            $version = $translator->createTranslation($object, $sourceLanguage, $targetLanguage);
            $module->redirectTo(
                'content/edit/' . $object->attribute('id')
                . '/' . $version->attribute('version')
                . '/' . $targetLanguage
                . '/' . $sourceLanguage
            );
            return;
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$tpl->setVariable('error', $error);
$tpl->setVariable('already_exists', $translationAlreadyExits);
$tpl->setVariable('from_language', $sourceContentLanguage);
$tpl->setVariable('to_language', $targetContentLanguage);
$tpl->setVariable('object', $object);
$tpl->setVariable('can_translate_document', $translator->getHandler() instanceof TranslatorHandlerDocumentCapable);

$Result = [];
$Result['content'] = $tpl->fetch('design:translator/translate_content.tpl');
$Result['path'] = $Result['title_path'] = [
    [
        'text' => 'Translate content',
        'url' => false,
        'url_alias' => false,
    ],
];
$contentInfoArray = [
    'node_id' => $object->mainNodeID(),
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