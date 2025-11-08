<?php

$Module = [
    'name' => 'Translate',
    'variable_params' => true,
];

$ViewList = [];

$ViewList['content'] = [
    'functions' => ['content'],
    'script' => 'content.php',
    'params' => ['Id', 'From', 'To'],
    'unordered_params' => [],
];

$ViewList['settings'] = [
    'functions' => ['settings'],
    'script' => 'settings.php',
    'params' => [],
    'unordered_params' => [],
];

$ViewList['set-mode'] = [
    'functions' => ['content'],
    'script' => 'set-mode.php',
    'params' => ['Action', 'Parameter'],
    'unordered_params' => [],
];

$ViewList['pending'] = [
    'functions' => ['settings'],
    'script' => 'pending.php',
    'params' => [],
    'unordered_params' => ["offset" => "Offset"],
];

$FunctionList['content'] = [];
$FunctionList['settings'] = [];
