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
            TranslatorManager::instance()->addPendingTranslations($object);
        }

        return eZWorkflowType::STATUS_ACCEPTED;
    }
}

eZWorkflowEventType::registerEventType(TranslateType::WORKFLOW_TYPE_STRING, 'TranslateType');