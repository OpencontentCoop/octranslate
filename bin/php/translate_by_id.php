<?php

require 'autoload.php';

$script = eZScript::instance([
    'description' => ("\n\n"),
    'use-session' => false,
    'use-modules' => true,
    'use-extensions' => true,
]);
$cli = eZCLI::instance();
$script->startup();

$options = $script->getOptions(
    '[id:][override]',
    '',
    [
        'id' => 'Id del contenuto',
        'override' => 'Sovrascrive traduzioni esistenti',
    ]
);
$script->initialize();
$script->setUseDebugAccumulators(true);

$override = $options['override'];

try {
    if (isset($options['id'])) {
        $contentId = $options['id'];
    } else {
        throw new Exception("Specificare l'id del contenuto");
    }

    $object = eZContentObject::fetch((int)$contentId);
    if (!$object instanceof eZContentObject) {
        $object = eZContentObject::fetchByRemoteID($contentId);
    }
    if (!$object instanceof eZContentObject) {
        throw new Exception("Contenuto $contentId non trovato");
    }

    $actions = TranslatorManager::instance()->addPendingTranslations($object, !$override);
    foreach ($actions as $action) {
        TranslatorManager::processPendingAction($action, $cli);
    }

    $script->shutdown();
} catch (Exception $e) {
    $errCode = $e->getCode();
    $errCode = $errCode != 0 ? $errCode : 1; // If an error has occured, script must terminate with a status other than 0
    $script->shutdown($errCode, $e->getMessage());
}
