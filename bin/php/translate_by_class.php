<?php

require 'autoload.php';

$script = eZScript::instance([
    'description' => ("Reindicizza\n\n"),
    'use-session' => false,
    'use-modules' => true,
    'use-extensions' => true,
]);

$script->startup();

$options = $script->getOptions(
    '[class:]',
    '',
    ['class' => 'Identificatore della classe']
);
$script->initialize();
$script->setUseDebugAccumulators(true);


try {
    if (isset($options['class'])) {
        $classIdentifier = $options['class'];
    } else {
        throw new Exception("Specificare la classe");
    }

    $class = eZContentClass::fetchByIdentifier($classIdentifier);
    if (!$class instanceof eZContentClass) {
        throw new Exception("Classe $classIdentifier non trovata");
    }

    $objects = eZPersistentObject::fetchObjectList(
        eZContentObject::definition(),
        ['id'],
        ['contentclass_id' => $class->attribute('id')],
        null,
        null,
        false
    );

    $count = count($objects);
    if ($count > 0) {

        $output = new ezcConsoleOutput();
        $progressBarOptions = ['emptyChar' => ' ', 'barChar' => '='];
        $progressBar = new ezcConsoleProgressbar($output, $count, $progressBarOptions);
        $progressBar->start();

        foreach ($objects as $row) {
            $progressBar->advance();
            $object = eZContentObject::fetch((int)$row['id']);
            if ($object instanceof eZContentObject) {
                TranslatorManager::instance()->addPendingTranslations($object);
            }

        }
        $progressBar->finish();
    }

    $script->shutdown();
} catch (Exception $e) {
    $errCode = $e->getCode();
    $errCode = $errCode != 0 ? $errCode : 1; // If an error has occured, script must terminate with a status other than 0
    $script->shutdown($errCode, $e->getMessage());
}
