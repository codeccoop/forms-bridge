<?php

require_once '/home/orzo/.config/composer/vendor/tareq1988/wp-php-cs-fixer/loader.php';

$finder = (new PhpCsFixer\Finder())
    ->exclude('node_modules')
    ->exclude('vendor')
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
         '@PSR12' => true,
         '@PHP82Migration' => true,
     ])
    ->setFinder($finder);

// return (new PhpCsFixer\Config())
//     ->registerCustomFixers([
//         new WeDevs\Fixer\SpaceInsideParenthesisFixer(),
//         new WeDevs\Fixer\BlankLineAfterClassOpeningFixer(),
//     ])
//     ->setRules(WeDevs\Fixer\Fixer::rules())
//     ->setFinder($finder);
