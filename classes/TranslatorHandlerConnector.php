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
        return TranslatorManager::instance()->getHandler()->getSettings();
    }

    protected function getSchema()
    {
        return TranslatorManager::instance()->getHandler()->getSettingsSchema();
    }

    protected function getOptions()
    {
        $options = [
            'form' => [
                'attributes' => [
                    'action' => $this->getHelper()->getServiceUrl('action', $this->getHelper()->getParameters()),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data'
                ],
            ],
        ];
        return $options;
    }

    protected function getView()
    {
        return [
            'parent' => 'bootstrap-edit',
            'locale' => 'it_IT'
        ];
    }

    protected function submit()
    {
        TranslatorManager::instance()->getHandler()->storeSettings($_POST);
        return true;
    }

    protected function upload()
    {
        throw new BadMethodCallException();
    }

}