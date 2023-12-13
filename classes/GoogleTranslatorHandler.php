<?php

class GoogleTranslatorHandler implements TranslatorHandlerInterface
{
    public function getSettingsSchema(): array
    {
        return [];
    }

    public function getSettings(): array
    {
        return [];
    }

    public function storeSettings(array $settings): void
    {
    }

    public function translate(array $text, string $sourceLanguage, string $targetLanguage, array $options = []): array
    {
        return [];
    }

    public function isAllowedLanguage(string $languageCode): bool
    {
        return false;
    }
}