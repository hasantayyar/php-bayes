<?php

$dic = file_get_contents(__DIR__.'/sites.txt');
$dic = explode(PHP_EOL, $dic);

function xsites_get_dictionary() {
    global $dic;

    $bayes_dic = new Noop\Bayes\Dictionary\Dictionary;
    $bayes_dic->loadStopwords(array('en'));
    $bayes_dic->setMinimalTokenCount(5);

    $tokenizer = new Noop\Bayes\Tokenizer\Html;
    $tokenizer->setPolicy(DICTIONARY_POLICY);

    // use half of sites
    foreach (array_slice($dic, 0, floor(count($dic) / 2)) as $site) {
        $contents = xsites_get_site($site);

        if($contents != '') {
            $bayes_dic->addTokens($tokenizer->tokenize($contents));
        }
    }

    return $bayes_dic;
}

function xsites_get_site($url) {
    $cache =  __DIR__.'/data/xsite-cache-'.trim($url);
    if (is_readable($cache)) {
        return file_get_contents($cache);
    } else {
        printf("\nCaching site \"%s\" to \"%s\"", $url, $cache);
        $context = stream_context_create(array(
            'http' => array(
                'timeout' => 1      // Timeout in seconds
            )
        ));
        ini_set('default_socket_timeout', 1);
        // we should strip html
        $contents = file_get_contents('http://'.$url, 0, $context);
        // if(strlen($contents)>0) will be better
        file_put_contents($cache, $contents);
        return $contents;
    }
}
