<?php

require 'autoload.php';

$script = eZScript::instance([
    'description' => ("\n\n"),
    'use-session' => false,
    'use-modules' => true,
    'use-extensions' => true,
]);

$script->startup();

$options = $script->getOptions(
    '[class:]',
    '',
    [
        'class' => 'Identificatore della classe',
        'subtree' => 'Nodo contenitore',
    ]
);
$script->initialize();
$script->setUseDebugAccumulators(true);

$cli = eZCLI::instance();

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

    $parentNodeId = (int)$options['subtree'];
    if ($parentNodeId === 0){
        $parentNodeId = 1;
    }

    /** @var eZContentObjectTreeNode[] $list */
    $list = eZContentObjectTreeNode::subTreeByNodeID([
        'ClassFilterType' => 'include',
        'ClassFilterArray' => [$classIdentifier],
        'MainNodeOnly' => true,
        'SortBy' => ['contentobject_id', true],
    ], $parentNodeId);

    $total = 0;
    $count = count($list);
    if ($count > 0) {
        $output = new ezcConsoleOutput();
        $progressBarOptions = ['emptyChar' => ' ', 'barChar' => '='];
        $progressBar = new ezcConsoleProgressbar($output, $count, $progressBarOptions);
        $progressBar->start();

        foreach ($list as $node) {
            $progressBar->advance();
            $object = $node->object();
            if ($object instanceof eZContentObject) {
                $partial = TranslatorManager::instance()->doEstimateCharactersCount($object);
                $total += $partial;
                eZContentObject::clearCache();
            }

        }
        $progressBar->finish();
        $cli->output();
    }

    $cli->output('Content count: ' . $count);
    $cli->warning('Estimate character count: ' . $total);

    $script->shutdown();
} catch (Exception $e) {
    $errCode = $e->getCode();
    $errCode = $errCode != 0 ? $errCode : 1; // If an error has occured, script must terminate with a status other than 0
    $script->shutdown($errCode, $e->getMessage());
}
