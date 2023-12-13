<?php

interface TranslatorHandlerInterface
{
    const TRANSLATE_FROM_EZ_XML = 'xml';

    public function getSettingsSchema(): array;

    public function getSettings(): array;

    public function storeSettings(array $settings): void;

    public function translate(array $text, string $sourceLanguage, string $targetLanguage, array $options = []): array;

    public function isAllowedLanguage(string $languageCode): bool;
}