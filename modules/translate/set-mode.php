<?php

/** @var eZModule $module */
$module = $Params['Module'];
$tpl = eZTemplate::factory();
$action = $Params['Action'];
$parameter = (int)$Params['Parameter'];
$asJson = eZHTTPTool::instance()->hasGetVariable('format')
    && eZHTTPTool::instance()->getVariable('format') === 'json';

$states = $stateId = false;
try {
    $states = OpenPABase::initStateGroup('translation', ['automatic', 'manual']);
} catch (Exception $e) {
    eZDebug::writeError($e->getMessage(), __METHOD__);
}
if ($states) {
    if ($action === 'automatic') {
        $stateId = $states['translation.automatic']->attribute('id');
    }
    if ($action === 'manual') {
        $stateId = $states['translation.manual']->attribute('id');
    }
}
$object = eZContentObject::fetch($parameter);
if ($object instanceof eZContentObject && $stateId) {
    if (eZOperationHandler::operationIsAvailable('content_updateobjectstate')) {
        $operationResult = eZOperationHandler::execute(
            'content', 'updateobjectstate',
            [
                'object_id' => $parameter,
                'state_id_list' => [$stateId],
            ]
        );
    } else {
        eZContentOperationCollection::updateObjectState($parameter, [$stateId]);
    }
    eZContentCacheManager::clearContentCache($parameter);
    if ($asJson) {
        header('HTTP/1.1 200 OK');
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        eZExecution::cleanExit();
    }
    $module->redirectTo($object->mainNode()->attribute('url_alias'));
} else {
    if ($asJson) {
        header('HTTP/1.1 200 OK');
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error']);
        eZExecution::cleanExit();
    }
    return $module->handleError(eZError::KERNEL_NOT_AVAILABLE, 'kernel');
}




