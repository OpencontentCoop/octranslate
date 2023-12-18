<?php

use DeepL\TranslateTextOptions;
use DeepL\Translator;

class DeeplTranslatorHandler implements TranslatorHandlerInterface
{
    use SiteDataStorageTrait;

    private $translator;

    public function getSettingsSchema(): array
    {
        return [
            "title" => "DeepL",
            "type" => "object",
            "properties" => [
                "key" => [
                    "type" => "string",
                    "title" => "Auth key",
                    "required" => true,
                ],
//                "env" => [
//                    "type" => "string",
//                    "title" => "Api environment",
//                    "required" => true,
//                    "enum" => ['free', 'pro'],
//                    "default" => 'free'
//                ],
                "usage" => [
                    "type" => "string",
                    "title" => "Account usage",
                    "readonly" => true,
                ],
                "pending" => [
                    "type" => "string",
                    "title" => "Pending translations",
                    "readonly" => true,
                ],
            ],
        ];
    }

    public function getSettings(): array
    {
        $settings = (array)json_decode($this->getStorage('deepl_settings'), true);
        try {
            if ($settings['key']) {
                $settings['usage'] = (string)$this->getTranslator($settings['key'])->getUsage();
            }
        }catch (Throwable $e){
            $settings['usage'] = (string)$e->getMessage();
        }
        $settings['pending'] = TranslatorManager::instance()->countPendingActions();
        return $settings;
    }

    public function storeSettings(array $settings): void
    {
        $this->setStorage('deepl_settings', json_encode($settings));
    }

    public function translate(array $text, string $sourceLanguage, string $targetLanguage, array $options = []): array
    {
        $handlerOptions = [];
        if (in_array(TranslatorHandlerInterface::TRANSLATE_FROM_EZ_XML, $options)) {
            $handlerOptions = [
                TranslateTextOptions::TAG_HANDLING => 'xml',
                TranslateTextOptions::SPLITTING_TAGS => 'paragraph,c',
            ];
        }
        $translationResult = $this->getTranslator($this->getSettings()['key'])->translateText(
            $text,
            $this->mapLanguage($sourceLanguage),
            $this->mapLanguage($targetLanguage),
            $handlerOptions
        );

        return (array)$translationResult;
    }

    private function getTranslator($authKey): Translator
    {
        if ($this->translator === null) {
            $this->translator = new Translator($authKey);
        }

        return $this->translator;
    }

    public function isAllowedLanguage(string $languageCode): bool
    {
        try {
            $this->mapLanguage($languageCode);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function mapLanguage($languageCode): string
    {
        $map = [
//            '?' => 'bg',
            'cze-CZ' => 'cs',
//            '?' => 'da',
            'ger-DE' => 'de',
            'ell-GR' => 'el',
            'eng-GB' => 'en-GB',
            'esl-ES' => 'es',
//            '?' => 'et',
            'fin-FI' => 'fi',
            'fre-FR' => 'fr',
            'hun-HU' => 'hu',
            'ind-ID' => 'id',
            'ita-IT' => 'it',
            'jpn-JP' => 'ja',
//            '?' => 'ko',
//            '?' => 'lt',
//            '?' => 'lv',
            'nor-NO' => 'nb',
            'dut-NL' => 'nl',
            'pol-PL' => 'pl',
            'por-PT' => 'pt-PT',
            'por-BR' => 'pt-BR',
//            '?' => 'ro',
            'rus-RU' => 'ru',
            'slk-SK' => 'sk',
//            '?' => 'sl',
            'swe-SE' => 'sv',
            'tur-TR' => 'tr',
            'ukr-UA' => 'uk',
            'chi-CN' => 'zh',
        ];

        if (!isset($map[$languageCode])) {
            throw new RuntimeException("Language $languageCode not found in translator engine");
        }

        return $map[$languageCode];
    }
}