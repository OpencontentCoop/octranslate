<?php

/** @var bool $isQuiet */

/** @var eZCLI $cli */

$limit = 500;
$entries = eZPendingActions::fetchObjectList(
    eZPendingActions::definition(),
    null,
    ['action' => TranslatorManager::PENDING_ACTION],
    ['id' => 'asc'],
    ['offset' => 0, 'limit' => $limit]
);

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
