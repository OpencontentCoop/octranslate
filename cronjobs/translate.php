<?php

/** @var bool $isQuiet */

/** @var eZCLI $cli */

$limit = 50;
$entries = eZPendingActions::fetchByAction(TranslatorManager::PENDING_ACTION);

if (!empty($entries)) {
    if (!$isQuiet) {
        $count = count($entries);
        $cli->output("Processing $count pending translation actions");
    }
    foreach ($entries as $entry) {
        TranslatorManager::processPendingAction($entry, $isQuiet ? null : $cli);
    }
}

if (!$isQuiet) {
    $cli->output("Done");
}
