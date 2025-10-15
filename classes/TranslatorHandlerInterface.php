<?php

interface TranslatorHandlerInterface
{
    const TRANSLATE_FROM_EZ_XML = 'xml';

    /**
     * Identificativo unico (e.g. my_awesome_handler)
     *
     * @return string
     */
    public function getIdentifier() :string;

    /**
     * Restituisce il JSONSchema delle eventuali configurazioni da esporre all'amministratore (e.g. secret, api-key, ...)
     *
     * @return array
     */
    public function getSettingsSchema(): array;

    /**
     * Restituisce il JSON delle configurazioni (conforme al JSONSchema dichiarato
     * @see TranslatorHandlerInterface::getSettingsSchema())
     *
     * @return array
     */
    public function getSettings(): array;

    /**
     * Si occupa di salvare le configurazioni fornite dall'amministratore in uno storage a discrezione del programmatore
     * @param array $settings
     * @return void
     */
    public function storeSettings(array $settings): void;

    /**
     * Rimuove le configurazioni salvate dallo storage
     * @return void
     */
    public function deleteSettings(): void;

    /**
     * Esegue la traduzione di un array di stringhe da $sourceLanguage a $targetLanguage
     * In $options si riceve un array di stringhe
     * attualmente è prevista solo l'opzione con TranslatorHandlerInterface::TRANSLATE_FROM_EZ_XML
     * per poter gestire la traduzione di campi xml
     * Il formato di $sourceLanguage a $targetLanguage è quello di eZContentLanguage::fetchLocaleList()
     *
     * @param array $text
     * @param string $sourceLanguage
     * @param string $targetLanguage
     * @param array $options
     * @return array
     */
    public function translate(array $text, string $sourceLanguage, string $targetLanguage, array $options = []): array;

    /**
     * Indica se è in grado di tradurre la lingua $languageCode
     * Il formato di $languageCode è quello di eZContentLanguage::fetchLocaleList()
     *
     * @param string $languageCode
     * @return bool
     */
    public function isAllowedLanguage(string $languageCode): bool;
}