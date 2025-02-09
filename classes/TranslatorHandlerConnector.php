<?php

use Opencontent\Ocopendata\Forms\Connectors\AbstractBaseConnector;

class TranslatorHandlerConnector extends AbstractBaseConnector
{
    public function runService($serviceIdentifier)
    {
        $access = eZUser::currentUser()->hasAccessTo('translate', 'settings');
        if ($access['accessWord'] !== 'yes') {
            throw new RuntimeException('User can not edit translator settings');
        }

        return parent::runService($serviceIdentifier);
    }

    protected function getData()
    {
        $settings = TranslatorManager::instance()->getHandler()->getSettings();
        $settings['_pending'] = TranslatorManager::instance()->countPendingActions();
        return $settings;
    }

    protected function getSchema()
    {
        $schema = TranslatorManager::instance()->getHandler()->getSettingsSchema();
        $schema['properties']['_pending'] = [
            "type" => "string",
            "title" => "Pending translations",
            "readonly" => true,
        ];
        return $schema;
    }

    protected function getOptions()
    {
        $options = [
            'form' => [
                'attributes' => [
                    'action' => $this->getHelper()->getServiceUrl('action', $this->getHelper()->getParameters()),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data',
                ],
            ],
        ];
        return $options;
    }

    protected function getView()
    {
        return [
            'parent' => 'bootstrap-edit',
            'locale' => 'it_IT',
        ];
    }

    protected function submit()
    {
        unset($_POST['_pending']);
        TranslatorManager::instance()->getHandler()->storeSettings($_POST);
        return true;
    }

    protected function upload()
    {
        throw new BadMethodCallException();
    }

}