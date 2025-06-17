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
    '[limit:][offset:]'
);
$script->initialize();
$script->setUseDebugAccumulators(true);

$admin = eZUser::fetchByName('admin');
eZUser::setCurrentlyLoggedInUser($admin, $admin->attribute('contentobject_id'));

eZINI::instance()->setVariable('ContentSettings', 'ViewCaching', 'disabled');
eZINI::instance()->setVariable('SearchSettings', 'DelayedIndexing', 'enabled');

try {
    $limit = (int)$options['limit'];
    $offset = (int)$options['offset'];
    $entries = eZPendingActions::fetchObjectList(
        eZPendingActions::definition(),
        null,
        ['action' => TranslatorManager::PENDING_ACTION],
        ['id' => 'asc'],
        ['offset' => $offset, 'limit' => $limit]
    );

    if (!empty($entries)) {
        $count = count($entries);
        $cli->output("Processing $count pending translation actions");
        foreach ($entries as $entry) {
            TranslatorManager::processPendingAction($entry, $cli);
        }
    }

    $script->shutdown();
} catch (Exception $e) {
    $errCode = $e->getCode();
    $errCode = $errCode != 0 ? $errCode : 1; // If an error has occured, script must terminate with a status other than 0
    slack('CIDPAT TRANSLATE Errore ' . $e->getMessage());
    $script->shutdown($errCode, $e->getMessage());
}
