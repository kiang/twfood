<?php
define('DS', DIRECTORY_SEPARATOR);
$docL0040084Path = implode(DS, array(
    dirname(dirname(__FILE__)), 'doc', 'L0040084', 'original'
));
$txtL0040084Path = str_replace('original', 'txt', $docL0040084Path);
if(!file_exists($txtL0040084Path)) {
    mkdir($txtL0040084Path, 0755, true);
}

foreach(glob($docL0040084Path . DS . '*.PDF') AS $pdfFile) {
    $pdfFile = str_replace(array(' ', '(', ')'), array('\\ ', '\\(', '\\)'), $pdfFile);
    $targetFile = str_replace('original', 'txt', $pdfFile) . '.txt';
    system("/usr/bin/java -cp /usr/share/java/commons-logging.jar:/usr/share/java/fontbox.jar:/usr/share/java/pdfbox.jar org.apache.pdfbox.PDFBox ExtractText {$pdfFile} {$targetFile}"); //much better
}