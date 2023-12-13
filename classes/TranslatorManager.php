<?php

class TranslatorManager
{
    const PENDING_ACTION = 'octranslate';

    private static $instance;

    /**
     * @var TranslatorHandlerInterface
     */
    private $handler;

    private function __construct()
    {
        $this->handler = new DeeplTranslatorHandler();
    }

    public static function instance(): TranslatorManager
    {
        if (self::$instance === null) {
            self::$instance = new TranslatorManager();
        }

        return self::$instance;
    }

    public function getHandler(): TranslatorHandlerInterface
    {
        return $this->handler;
    }

    public function createTranslation(
        eZContentObject $object,
        string $sourceLanguage,
        string $targetLanguage
    ): eZContentObjectVersion {
        $translatedDataMap = TranslatorManager::instance()->translateDataMap(
            $object,
            $sourceLanguage,
            $targetLanguage
        );

        return self::createNewVersion($object, $targetLanguage, $translatedDataMap);
    }

    public function createAndPublishTranslation(
        eZContentObject $object,
        string $sourceLanguage,
        string $targetLanguage
    ): bool {
        $translatedDataMap = TranslatorManager::instance()->translateDataMap(
            $object,
            $sourceLanguage,
            $targetLanguage
        );

        $newVersion = self::createNewVersion($object, $targetLanguage, $translatedDataMap);
        $operationResult = eZOperationHandler::execute('content', 'publish', [
            'object_id' => $newVersion->attribute('contentobject_id'),
            'version' => $newVersion->attribute('version'),
        ]);

        if ($operationResult['status'] == eZModuleOperationInfo::STATUS_CONTINUE) {
            return true;
        }

        return false;
    }

    public function appendPendingAction(
        eZContentObject $object,
        string $sourceLanguage,
        string $targetLanguage
    ): ?eZPendingActions {
        if (!$this->isAllowedLanguage($targetLanguage)) {
            return null;
        }

        $params = json_encode([
            'id' => $object->attribute('id'),
            'from' => $sourceLanguage,
            'to' => $targetLanguage,
        ]);
        $pending = eZPendingActions::fetchObject(
            eZPendingActions::definition(),
            null,
            [
                'action' => self::PENDING_ACTION,
                'param' => $params,
            ]
        );

        if (!$pending instanceof eZPendingActions) {
            $pending = new eZPendingActions([
                'action' => self::PENDING_ACTION,
                'created' => time(),
                'param' => $params,
            ]);
            $pending->store();

            eZDebug::writeDebug(
                sprintf(
                    'Append pending action translation from %s to %s for object %s',
                    $sourceLanguage,
                    $targetLanguage,
                    $object->attribute('id')
                ),
                __METHOD__
            );
        }

        return $pending;
    }

    private function isAllowedLanguage($languageCode): bool
    {
        return $languageCode !== 'ita-PA' && $this->getHandler()->isAllowedLanguage($languageCode);
    }

    private static function createNewVersion(
        eZContentObject $object,
        string $languageCode,
        array $attributesData
    ): eZContentObjectVersion {
        $db = eZDB::instance();
        $db->begin();

        $newVersion = $object->createNewVersion(false, true, $languageCode);

        if (!$newVersion instanceof eZContentObjectVersion) {
            $db->rollback();
            throw new RuntimeException('Unable to create a new version for object ' . $object->attribute('id'));
        }

        $newVersion->setAttribute('modified', time());
        $newVersion->store();

        $attributeList = $newVersion->attribute('contentobject_attributes');
        foreach ($attributeList as $attribute) {
            $attributeIdentifier = $attribute->attribute('contentclass_attribute_identifier');
            if (array_key_exists($attributeIdentifier, $attributesData)) {
                $dataString = $attributesData[$attributeIdentifier];
                $attribute->fromString($dataString);
                $attribute->store();
            }
        }
        $db->commit();

        return $newVersion;
    }

    private function translateDataMap(eZContentObject $object, string $sourceLanguage, string $targetLanguage): array
    {
        if (!$this->isAllowedLanguage($targetLanguage)) {
            throw new RuntimeException(sprintf('Translation in language %s is not supported', $targetLanguage));
        }

        eZDebug::writeDebug(
            sprintf(
                'Fetch translation from %s to %s for object %s',
                $sourceLanguage,
                $targetLanguage,
                $object->attribute('id')
            ),
            __METHOD__
        );

        $dataMap = $object->fetchDataMap($object->attribute('current_version'), $sourceLanguage);
        if (empty($dataMap)) {
            throw new InvalidArgumentException(sprintf('Missing data in %s source language', $sourceLanguage));
        }

        $toTranslate = [
            'string' => [],
            'xml' => [],
            'url' => [],
            'relation' => [],
        ];
        $untranslated = [];
        foreach ($dataMap as $identifier => $attribute) {
            if ($identifier === 'identifier') {
                continue;
            }
            if (!$attribute->hasContent()) {
                continue;
            }
            switch ($attribute->attribute('data_type_string')) {
                case 'ezstring':
                case 'eztext';
                    $toTranslate['string'][$identifier] = $attribute->toString();
                    break;

                case 'ezmatrix';
                case 'ezxmltext';
                    $toTranslate['xml'][$identifier] = $attribute->toString();
                    break;

                case 'ezurl':
                    $label = $attribute->attribute('data_text');
                    if (!empty($label)) {
                        $toTranslate['url'][$identifier] = $label;
                    }
                    break;

                case 'ezobjectrelation':
                case 'ezobjectrelationlist':
                    foreach (explode('-', $attribute->toString()) as $relationId) {
                        $toTranslate['relation'][$identifier][] = $relationId;
                        $toTranslate['relation'][$identifier] = array_unique($toTranslate['relation'][$identifier]);
                    }
                    break;

                case 'eztags':
                    $tags = eZTags::createFromAttribute($attribute, $targetLanguage);
                    $returnArray = [];
                    $returnArray[] = $tags->attribute('id_string');
                    $returnArray[] = $tags->attribute('keyword_string');
                    $returnArray[] = $tags->attribute('parent_string');
                    $returnArray[] = $tags->attribute('locale_string');
                    $untranslated[$identifier] = implode('|#', $returnArray);
                    break;

                default:
                    $untranslated[$identifier] = $attribute->toString();
            }
        }

        $translated = [];
        if (!empty($toTranslate['string'])) {
            $translated['string'] = $this->getHandler()->translate(
                array_values($toTranslate['string']),
                $sourceLanguage,
                $targetLanguage
            );
        }

        if (!empty($toTranslate['xml'])) {
            $translated['xml'] = $this->getHandler()->translate(
                array_values($toTranslate['xml']),
                $sourceLanguage,
                $targetLanguage,
                [TranslatorHandlerInterface::TRANSLATE_FROM_EZ_XML]
            );
        }
        if (!empty($toTranslate['url'])) {
            $translated['url'] = $this->getHandler()->translate(
                array_values($toTranslate['url']),
                $sourceLanguage,
                $targetLanguage
            );
        }

        $translated['string'][-1] = '?';
        $translated['xml'][-1] = '?';
        $translated['url'][-1] = '?';

        $translatedDataMap = [];
        foreach ($dataMap as $identifier => $attribute) {
            if ($identifier === 'identifier') {
                $translatedDataMap[$identifier] = $attribute->toString();
                continue;
            }
            if (!$attribute->hasContent()) {
                $translatedDataMap[$identifier] = '';
                continue;
            }
            switch ($attribute->attribute('data_type_string')) {
                case 'ezstring':
                case 'eztext';
                    $key = $this->getKeyIndex($identifier, $toTranslate['string']);
                    $translatedDataMap[$identifier] = (string)$translated['string'][$key];
                    break;

                case 'ezmatrix';
                case 'ezxmltext';
                    $key = $this->getKeyIndex($identifier, $toTranslate['xml']);
                    $translatedDataMap[$identifier] = (string)$translated['xml'][$key];
                    break;

                case 'ezurl':
                    $value = $attribute->toString();
                    $parts = explode('|', $value);
                    $label = '';
                    if (!empty($parts[1])) {
                        $key = $this->getKeyIndex($identifier, $toTranslate['url']);
                        $label = '|' . (string)$translated['url'][$key];
                    }
                    $translatedDataMap[$identifier] = $parts[0] . $label;
                    break;

                case 'ezobjectrelation':
                case 'ezobjectrelationlist':
                    $translatedDataMap[$identifier] = implode('-', $toTranslate['relation'][$identifier]);
                    break;

                default:
                    $translatedDataMap[$identifier] = $untranslated[$identifier];
            }
        }

        return $translatedDataMap;
    }

    private function getKeyIndex(string $needle, array $haystack)
    {
        $keys = array_keys($haystack);
        while (($name = current($keys)) !== false) {
            if ($name === $needle) {
                return key($keys);
            }
            next($keys);
        }

        return -1;
    }
}