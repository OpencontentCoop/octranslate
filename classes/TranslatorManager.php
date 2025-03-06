<?php

class TranslatorManager
{
    use SiteDataStorageTrait;

    const PENDING_ACTION = 'octranslate';

    const TRANSLATOR_USERNAME_PREFIX = 'octranslate-';

    private static $instance;

    /**
     * @var TranslatorHandlerInterface
     */
    private $handler;

    private $isDocumentTranslationEnabled;

    private $defaultLanguage;

    private $autoClassList;

    private function __construct()
    {
        $this->handler = new DeeplTranslatorHandler();
        $this->isDocumentTranslationEnabled = false;
        $this->defaultLanguage = 'ita-IT';
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

    public function setIsDocumentTranslationEnabled(bool $isDocumentTranslationEnabled): void
    {
        $this->isDocumentTranslationEnabled = $isDocumentTranslationEnabled;
    }

    public function createTranslation(
        eZContentObject $object,
        string $sourceLanguage,
        string $targetLanguage
    ): eZContentObjectVersion {
        $translatedDataMap = $this->translateDataMap(
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
        $translatedDataMap = $this->translateDataMap(
            $object,
            $sourceLanguage,
            $targetLanguage
        );

        $newVersion = self::createNewVersion(
            $object,
            $targetLanguage,
            $translatedDataMap
        );
        $operationResult = eZOperationHandler::execute('content', 'publish', [
            'object_id' => $newVersion->attribute('contentobject_id'),
            'version' => $newVersion->attribute('version'),
        ]);

        $this->storeVersionTranslatedHash($newVersion, $targetLanguage);

        if ($operationResult['status'] == eZModuleOperationInfo::STATUS_CONTINUE) {
            return true;
        }

        return false;
    }

    public function canAutoTranslate(eZContentObject $object): bool
    {
        return
            in_array('translation/automatic', $object->attribute('state_identifier_array'))
            && $this->isAutoTranslatable($object);
    }

    public function isAutoTranslatable(eZContentObject $object): bool
    {
        return in_array($object->attribute('class_identifier'), $this->getAutoTranslatableClassList());
    }

    public function getAutoTranslatableClassList(): array
    {
        if ($this->autoClassList === null) {
            $this->autoClassList = (array)json_decode($this->getStorage('octranslate_auto_class_list'), true);
        }

        return $this->autoClassList;
    }

    public function setAutoTranslatableClassList(array $classList): void
    {
        $this->setStorage('octranslate_auto_class_list', json_encode($classList));
        $this->autoClassList = null;
    }

    public function addPendingTranslations(eZContentObject $object, $skipAlreadyTranslated = true)
    {
        $initialLanguageID = $object->attribute('initial_language_id');
        $language = eZContentLanguage::fetch($initialLanguageID);
        if ($language) {
            $sourceLanguage = $language->attribute('locale');
            /** @var eZContentLanguage[] $languages */
            $languages = eZContentLanguage::fetchList();
            $availableLanguages = $object->availableLanguages();
            foreach ($languages as $language) {
                $targetLanguage = $language->attribute('locale');
                $translationAlreadyExits = in_array($targetLanguage, $availableLanguages);
                $skip = $skipAlreadyTranslated && $translationAlreadyExits;
                if ($sourceLanguage !== $targetLanguage && !$skip) {
                    $this->appendPendingAction(
                        $object,
                        $sourceLanguage,
                        $targetLanguage
                    );
                }
            }
        } else {
            eZDebug::writeError(sprintf('Language id %s not found', $initialLanguageID), __METHOD__);
        }
    }

    public function countPendingActions(): int
    {
        return (int)eZPendingActions::count(
            eZPendingActions::definition(),
            [
                'action' => self::PENDING_ACTION,
            ]
        );
    }

    private function appendPendingAction(
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
        } else {
            $pending->setAttribute('created', time());
            $pending->store();
        }

        return $pending;
    }

    private function isAllowedLanguage($languageCode): bool
    {
        return $languageCode !== 'ita-PA' && $this->getHandler()->isAllowedLanguage($languageCode);
    }

    /**
     * @throws Throwable
     */
    private static function createNewVersion(
        eZContentObject $object,
        string $languageCode,
        array $attributesData
    ): eZContentObjectVersion {
        $db = eZDB::instance();
        $db->begin();

        $newVersion = $object->createNewVersion(false, true, $languageCode);
        try {
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
        } catch (Throwable $e) {
            $db->rollback();
            throw $e;
        }
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
            'file' => [],
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
                case eZStringType::DATA_TYPE_STRING:
                case eZTextType::DATA_TYPE_STRING:
                    $toTranslate['string'][$identifier] = $attribute->toString();
                    break;

                case eZMatrixType::DATA_TYPE_STRING:
                case eZXMLTextType::DATA_TYPE_STRING:
                    $toTranslate['xml'][$identifier] = $attribute->toString();
                    break;

                case eZURLType::DATA_TYPE_STRING:
                    $label = $attribute->attribute('data_text');
                    if (!empty($label)) {
                        $toTranslate['url'][$identifier] = $label;
                    }
                    break;

                case eZObjectRelationType::DATA_TYPE_STRING:
                case eZObjectRelationListType::DATA_TYPE_STRING:
                    foreach (explode('-', $attribute->toString()) as $relationId) {
                        $toTranslate['relation'][$identifier][] = $relationId;
                        $toTranslate['relation'][$identifier] = array_unique($toTranslate['relation'][$identifier]);
                    }
                    break;

                case eZTagsType::DATA_TYPE_STRING:
                    $tags = eZTags::createFromAttribute($attribute, $targetLanguage);
                    $returnArray = [];
                    $returnArray[] = $tags->attribute('id_string');
                    $returnArray[] = $tags->attribute('keyword_string');
                    $returnArray[] = $tags->attribute('parent_string');
                    $returnArray[] = $tags->attribute('locale_string');
                    $untranslated[$identifier] = implode('|#', $returnArray);
                    break;

                case eZBinaryFileType::DATA_TYPE_STRING:
                    $file = $attribute->content();
                    if ($file instanceof eZBinaryFile) {
                        $toTranslate['file'][$identifier][] = $file;
                    }
                    break;

                case OCMultiBinaryType::DATA_TYPE_STRING:
                    $files = $attribute->content();
                    foreach ($files as $file) {
                        if ($file instanceof eZBinaryFile) {
                            $toTranslate['file'][$identifier][] = $file;
                        }
                    }
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
        if ($this->canTranslateDocument()) {
            $translated['file'] = $this->getHandler()->translateDocument(
                array_values($toTranslate['file']),
                $sourceLanguage,
                $targetLanguage
            );
        } else {
            $translated['file'] = $this->copyDocument(
                array_values($toTranslate['file']),
                $targetLanguage
            );
        }

        $translated['string'][-1] = '?';
        $translated['xml'][-1] = '?';
        $translated['url'][-1] = '?';
        $translated['file'][-1] = '?';

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
                case eZStringType::DATA_TYPE_STRING:
                case eZTextType::DATA_TYPE_STRING:
                    $key = $this->getKeyIndex($identifier, $toTranslate['string']);
                    $translatedDataMap[$identifier] = (string)$translated['string'][$key];
                    break;

                case eZMatrixType::DATA_TYPE_STRING:
                case eZXMLTextType::DATA_TYPE_STRING:
                    $key = $this->getKeyIndex($identifier, $toTranslate['xml']);
                    $translatedDataMap[$identifier] = (string)$translated['xml'][$key];
                    break;

                case eZURLType::DATA_TYPE_STRING:
                    $value = $attribute->toString();
                    $parts = explode('|', $value);
                    $label = '';
                    if (!empty($parts[1])) {
                        $key = $this->getKeyIndex($identifier, $toTranslate['url']);
                        $label = '|' . (string)$translated['url'][$key];
                    }
                    $translatedDataMap[$identifier] = $parts[0] . $label;
                    break;

                case eZObjectRelationType::DATA_TYPE_STRING:
                case eZObjectRelationListType::DATA_TYPE_STRING:
                    $translatedDataMap[$identifier] = implode('-', $toTranslate['relation'][$identifier]);
                    break;

                case eZBinaryFileType::DATA_TYPE_STRING:
                    $key = $this->getKeyIndex($identifier, $toTranslate['file']);
                    $translatedDataMap[$identifier] = (string)$translated['file'][$key][0];
                    break;

                case OCMultiBinaryType::DATA_TYPE_STRING:
                    $key = $this->getKeyIndex($identifier, $toTranslate['file']);
                    $translatedDataMap[$identifier] = implode('|', $translated['file'][$key]);
                    break;

                default:
                    $translatedDataMap[$identifier] = $untranslated[$identifier];
            }
        }

        return $translatedDataMap;
    }

    private function copyDocument(
        array $inputFiles,
        string $targetLanguage
    ): array {
        $data = [];
        foreach ($inputFiles as $index => $files) {
            foreach ($files as $file) {
                $filename = $file->attribute('original_filename');
                $inputFilePath = $file->filePath();
                eZClusterFileHandler::instance($inputFilePath)->fetch();
                $outputFilePath = self::tempDir($file, $targetLanguage) . $filename;
                if (!file_exists($outputFilePath)) {
                    eZFileHandler::copy($inputFilePath, $outputFilePath);
                }
                $data[$index][] = realpath($outputFilePath);
            }
        }
        return $data;
    }

    public static function tempDir(eZBinaryFile $file, $targetLang)
    {
        $path = eZDir::path([
            eZSys::cacheDirectory(),
            'tmp',
            $file->attribute('contentobject_attribute_id'),
            $file->attribute('version'),
            $targetLang,
        ], true);
        eZDir::mkdir($path, false, true);

        return $path;
    }

    public function canTranslateDocument(): bool
    {
        return $this->getHandler() instanceof TranslatorHandlerDocumentCapable && $this->isDocumentTranslationEnabled;
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

    public static function getLocaleUrl(eZContentObjectTreeNode $node, $language): string
    {
        $url = $node->attribute('url_alias') . '/(language)/' . $language;
        $ini = eZINI::instance();
        if ($ini->hasVariable('RegionalSettings', 'LanguageSwitcherClass')) {
            $className = $ini->variable('RegionalSettings', 'LanguageSwitcherClass');

            $saList = call_user_func([$className, 'setupTranslationSAList']);

            foreach ($saList as $destinationSiteAccess => $sa) {
                if ($sa['locale'] === $language) {
                    $handlerOptions = new ezpExtensionOptions();
                    $handlerOptions->iniFile = 'site.ini';
                    $handlerOptions->iniSection = 'RegionalSettings';
                    $handlerOptions->iniVariable = 'LanguageSwitcherClass';
                    $langSwitch = eZExtension::getHandlerClass($handlerOptions);
                    $langSwitch->setDestinationSiteAccess($destinationSiteAccess);
                    $langSwitch->process();
                    $destinationUrl = $langSwitch->destinationUrl();
                    $url = rtrim($destinationUrl, '/') . '/openpa/object/' . $node->attribute('contentobject_id');
                }
            }
        }

        return $url;
    }

    public function isAutoTranslated(eZContentObjectVersion $version, string $languageCode): bool
    {
        if ($languageCode === $this->defaultLanguage) {
            return false;
        }
        $hash = $this->createVersionHash($version, $languageCode);

        $count = (int)eZCollaborationItem::count(eZCollaborationItem::definition(), [
            'data_text1' => $languageCode,
            'data_text2' => $hash,
        ]);

        return $count > 0;
    }

    private function storeVersionTranslatedHash(eZContentObjectVersion $version, string $languageCode)
    {
        $collaborationItem = eZCollaborationItem::create('octranslate', (int)eZUser::currentUserID());
        $collaborationItem->setAttribute('data_int1', (int)$version->attribute('id'));
        $collaborationItem->setAttribute('data_text1', $languageCode);
        $collaborationItem->setAttribute('data_text2', $this->createVersionHash($version, $languageCode));
        $collaborationItem->setAttribute('data_text3', $this->getHandler()->getIdentifier());
        $collaborationItem->setAttribute('modified', time());
        $collaborationItem->store();
    }

    private function createVersionHash(eZContentObjectVersion $version, string $languageCode): string
    {
        $attributes = $version->contentObjectAttributes($languageCode);
        $serialized = [];
        foreach ($attributes as $attribute) {
            switch ($attribute->attribute('data_type_string')) {
                case eZStringType::DATA_TYPE_STRING:
                case eZTextType::DATA_TYPE_STRING:
                case eZMatrixType::DATA_TYPE_STRING:
                case eZXMLTextType::DATA_TYPE_STRING:
                case eZURLType::DATA_TYPE_STRING:
                    $serialized[$attribute->attribute('contentclass_attribute_identifier')] = trim(
                        (string)$attribute->attribute('data_text')
                    );
                    break;
            }
        }
        ksort($serialized);
        return md5(json_encode($serialized));
    }

    public static function processPendingAction(eZPendingActions $entry, eZCLI $cli = null): array
    {
        $result = false;
        $error = null;
        $params = $entry->attribute('param');
        $decodedParams = json_decode($params, true);
        try {
        $object = eZContentObject::fetch((int)$decodedParams['id']);
        $sourceLanguage = $decodedParams['from'];
        $targetLanguage = $decodedParams['to'];
        if ($object instanceof eZContentObject
            && eZContentLanguage::idByLocale($sourceLanguage)
            && eZContentLanguage::idByLocale($targetLanguage)) {
            if ($cli instanceof eZCLI) {
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
                $result = TranslatorManager::instance()->createAndPublishTranslation(
                    $object,
                    $sourceLanguage,
                    $targetLanguage
                );
            } catch (RuntimeException $e) {
                eZDebug::writeError($e->getMessage(), __METHOD__);
                if ($cli) {
                    $cli->error($e->getMessage());
                }
                $error = $e->getMessage();
            }
        } else {
            eZDebug::writeError('Invalid parameters', __METHOD__);
            if ($cli instanceof eZCLI) {
                $cli->error('Invalid parameters');
            }
            $error = 'Invalid parameters';
        }
        eZPendingActions::removeByAction(
            TranslatorManager::PENDING_ACTION,
            ['param' => $params]
        );
        } catch (Throwable $e) {
            if ($cli instanceof eZCLI) {
                $cli->error('Recoverable error: ' . $e->getMessage());
            }
            $error = $e->getMessage();
        }

        return ['result' => $result, 'error' => $error];
    }
}