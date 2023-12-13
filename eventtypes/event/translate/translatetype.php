<?php

class TranslateType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = "translate";

    public function __construct()
    {
        parent::__construct(
            TranslateType::WORKFLOW_TYPE_STRING,
            'Traduce il contenuto nelle lingue disponibili'
        );
    }

    public function execute($process, $event)
    {
        $parameters = $process->attribute('parameter_list');
        $objectID = $parameters['object_id'];
        $object = eZContentObject::fetch($objectID);
        if ($object instanceof eZContentObject) {
            $initialLanguageID = $object->attribute('initial_language_id');
            $language = eZContentLanguage::fetch($initialLanguageID);
            if ($language) {
                $sourceLanguage = $language->attribute('locale');
                /** @var eZContentLanguage[] $languages */
                $languages = eZContentLanguage::fetchList();
                $availableLanguages = $object->availableLanguages();
                foreach ($languages as $language) {
                    $targetLanguage = $language->attribute('locale');
                    if ($sourceLanguage !== $targetLanguage) {
                        $translationAlreadyExits = in_array($targetLanguage, $availableLanguages);
                        if (!$translationAlreadyExits) {
                            TranslatorManager::instance()->appendPendingAction(
                                $object,
                                $sourceLanguage,
                                $targetLanguage
                            );
                        }
                    }
                }
            } else {
                eZDebug::writeError(sprintf('Language id %s not found', $initialLanguageID), __METHOD__);
            }
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType(TranslateType::WORKFLOW_TYPE_STRING, 'TranslateType');