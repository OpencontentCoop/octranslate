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
        $settings['_auto_class_list'] = TranslatorManager::instance()->getAutoTranslatableClassList();
        return $settings;
    }

    protected function getSchema()
    {
        $schema = TranslatorManager::instance()->getHandler()->getSettingsSchema();
        $schema['properties']['_pending'] = [
            "type" => "string",
            "title" => "<a href=\"/translate/pending\">Pending translations</a>",
            "readonly" => true,
        ];
        $schema['properties']['_auto_class_list'] = [
            'type' => 'array',
            'title' => 'Auto translate class list',
            'enum' => array_keys($this->getClassHash()),
        ];
        return $schema;
    }

    private function getClassHash(): array
    {
        $classes = json_decode(
            json_encode((new \Opencontent\Opendata\Api\ClassRepository())->listAll()),
        true
        );

        return array_combine(
            array_column($classes, 'identifier'),
            array_map(function($item){
                return $item['nameList'][eZLocale::currentLocaleCode()] ?? $item['name'];
            }, $classes)
        );
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
        $options['fields']['_auto_class_list'] = [
            'multiple' => true,
            'type' => 'checkbox',
            'optionLabels' => array_values($this->getClassHash())
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
        $data = $_POST;
        unset($data['_pending']);
        TranslatorManager::instance()->setAutoTranslatableClassList($data['_auto_class_list'] ?? []);
        unset($data['_auto_class_list']);
        TranslatorManager::instance()->getHandler()->storeSettings($data);
        return true;
    }

    protected function upload()
    {
        throw new BadMethodCallException();
    }

}