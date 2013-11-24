<?php
define('DS', DIRECTORY_SEPARATOR);
$docL0040084Path = implode(DS, array(
    dirname(dirname(__FILE__)), 'doc', 'L0040084', 'original'
));
$txtL0040084Path = str_replace('original', 'txt', $docL0040084Path);
if(!file_exists($txtL0040084Path)) {
    mkdir($txtL0040084Path, 0755, true);
}

foreach(glob($docL0040084Path . DS . '*.DOC') AS $docFile) {
    $docFile = str_replace(array(' ', '(', ')'), array('\\ ', '\\(', '\\)'), $docFile);
    $targetFile = str_replace('original', 'txt', $docFile) . '.txt';
    // system("/usr/bin/catdoc {$docFile} > {$targetFile}"); // not so good
    system("/usr/bin/antiword {$docFile} > {$targetFile}"); //much better
}