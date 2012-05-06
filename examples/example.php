<?php

/**
 * made for markosweb.com / SmartViper llc, something like google SafeSearch analogue
 */

require_once(__DIR__.'/../autoload.php');
require_once(__DIR__.'/functions.php');

define('DICTIONARY_POLICY', Noop\Bayes\Tokenizer\Html::POLICY_METAS);
define('MATCH_POLICY', Noop\Bayes\Tokenizer\Html::POLICY_TEXTS | Noop\Bayes\Tokenizer\Html::POLICY_METAS | Noop\Bayes\Tokenizer\Html::POLICY_HEADERS | Noop\Bayes\Tokenizer\Html::POLICY_LINKS);

printf('Loading dictionary');

$bayes_dic = xsites_get_dictionary();

printf('Matching now');

$tokenizer = new Noop\Bayes\Tokenizer\Html;
$tokenizer->setPolicy(MATCH_POLICY);

foreach (array_slice($dic, floor(count($dic) / 2)) as $site) {
    $contents = xsites_get_site($site);
    if ($contents == '' && strlen($contents) < 1000) {
        //printf('Site not responsible, skipping');
    } else {
        printf("\nMatching \"%s\"", $site);
        printf("\nProbability: %.6f".PHP_EOL, $bayes_dic->match($tokenizer->tokenize($contents)));
    }
}

printf("\nMatching common sites");

foreach (array('dailyporno.tumblr.com','blogcu.com', 'youtube.com', 'google.com', 'wikipedia.com') as $site) {
    $contents = xsites_get_site($site);
    if ($contents == '' && strlen($contents) < 1000) {
        //printf('Site not responsible, skipping');
    } else {
        printf("\nMatching '%s'", $site);
        printf("\nProbability: %.6f".PHP_EOL, $bayes_dic->match($tokenizer->tokenize($contents)));
    }
}