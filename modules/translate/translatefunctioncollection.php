<?php

class TranslateFunctionCollection
{
    public static function isAutoTranslated($version, $languageCode): array
    {
        if (!$version instanceof eZContentObjectVersion) {
            return [
                'error' => [
                    'error_type' => 'kernel',
                    'error_code' => eZError::KERNEL_NOT_FOUND,
                ],
            ];
        }

        if (empty($languageCode)){
            $languageCode = eZLocale::currentLocaleCode();
        }

        return [
            'result' => TranslatorManager::instance()->isAutoTranslated($version, $languageCode)
        ];
    }

    public static function isAutoTranslatable($object): array
    {
        if (!$object instanceof eZContentObject) {
            return [
                'error' => [
                    'error_type' => 'kernel',
                    'error_code' => eZError::KERNEL_NOT_FOUND,
                ],
            ];
        }
        return [
            'result' => TranslatorManager::instance()->isAutoTranslatable($object)
        ];
    }

}
