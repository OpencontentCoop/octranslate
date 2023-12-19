<?php

interface TranslatorHandlerDocumentCapable
{
    /**
     * @param array<int, eZBinaryFile[]> $inputFiles
     * @param ?string $sourceLanguage
     * @param string $targetLanguage
     * @param array $options
     * @return array<int, string[]>
     */
    public function translateDocument(
        array $inputFiles,
        ?string $sourceLanguage,
        string $targetLanguage,
        array $options = []
    ): array;
}