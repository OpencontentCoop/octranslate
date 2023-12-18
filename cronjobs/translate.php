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
        $params = $entry->attribute('param');
        $decodedParams = json_decode($params, true);

        try {
            $object = eZContentObject::fetch((int)$decodedParams['id']);
            $sourceLanguage = $decodedParams['from'];
            $targetLanguage = $decodedParams['to'];

            if ($object instanceof eZContentObject
                && eZContentLanguage::idByLocale($sourceLanguage)
                && eZContentLanguage::idByLocale($targetLanguage)) {
                if (!$isQuiet) {
                    $cli->output(
                        sprintf(
                            'Translate object %s from %s to %s',
                            $object->attribute('id'),
                            $sourceLanguage,
                            $targetLanguage
                        )
                    );
                }
                try {
                    TranslatorManager::instance()->createAndPublishTranslation(
                        $object,
                        $sourceLanguage,
                        $targetLanguage
                    );
                } catch (RuntimeException $e) {
                    eZDebug::writeError($e->getMessage(), __METHOD__);
                    if (!$isQuiet) {
                        $cli->error($e->getMessage());
                    }
                }
            } else {
                eZDebug::writeError('Invalid parameters', __METHOD__);
                if (!$isQuiet) {
                    $cli->error('Invalid parameters');
                }
            }
            eZPendingActions::removeByAction(
                TranslatorManager::PENDING_ACTION,
                ['param' => $params]
            );
        } catch (Throwable $e) {
            if (!$isQuiet) {
                $cli->error('Recoverable error: ' . $e->getMessage());
            }
        }
    }
}

if (!$isQuiet) {
    $cli->output("Done");
}
