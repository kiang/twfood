<?php

define('DS', DIRECTORY_SEPARATOR);
$txtPath = implode(DS, array(
    dirname(dirname(__FILE__)), 'doc', 'L0040084', 'txt'
        ));
$blockPath = str_replace(DS . 'txt', DS . 'blocks', $txtPath);
if (!file_exists($blockPath)) {
    mkdir($blockPath, 0755, true);
}

foreach (glob($txtPath . DS . '附表二*.DOC.txt') AS $txtFile) {
    $targetFile = str_replace(array('.DOC.txt', 'txt' . DS), array('.csv', 'blocks' . DS), $txtFile);
    $content = file_get_contents($txtFile);
    $lines = explode("\n", $content);
    $targetBlocks = array();
    $currentBlock = array(
        '§' => '',
        'name' => '',
    );
    $lineCount = 0;
    $lastKey = '';
    foreach ($lines AS $line) {
        $line = trim($line);
        if (empty($line))
            continue;
        ++$lineCount;
        if (mb_substr($line, 0, 1, 'utf8') === '§') {
            if (!empty($currentBlock['name'])) {
                $targetBlocks[] = $currentBlock;
                $currentBlock = array(
                    '§' => '',
                    'name' => '',
                );
            }
            $lineCount = 1;
            $currentBlock['§'] = trim(mb_substr($line, 1, 100, 'utf8'));
        } elseif (substr($line, 0, 1) !== '|') {
            $currentBlock['name'] .= $line;
        } else {
            $columns = explode('|', $line);
            foreach ($columns AS $key => $val) {
                $columns[$key] = trim($val);
            }
            switch (count($columns)) {
                case 3:
                    break;
                case 4:
                case 6:
                    $columns[1] = explode('：', $columns[1]);
                    $currentBlock[trim($columns[1][0])] = trim($columns[1][1]);
                    $columns[2] = explode('：', $columns[2]);
                    $currentBlock[trim($columns[2][0])] = trim($columns[2][1]);
                    break;
                case 5:
                    if (!empty($columns[2])) {
                        $currentBlock[$columns[2]] = str_replace('：', '', $columns[3]);
                        $lastKey = $columns[2];
                    } else {
                        $currentBlock[$lastKey] .= $columns[3];
                    }
                    break;
                default:
                    print_r($columns);
                    exit();
            }
        }
    }
    $collectedKeys = array();
    foreach($targetBlocks AS $targetBlock) {
        foreach($targetBlock AS $key => $val) {
            $key = trim($key);
            $val = trim($val);
            if(empty($key) || empty($val)) {
                unset($targetBlock[$key]);
            } else {
                $collectedKeys[$key] = $key;
                $targetBlock[$key] = $val;
            }
        }
    }
    $fh = fopen($targetFile, 'w');
    fputcsv($fh, $collectedKeys);
    foreach($targetBlocks AS $targetBlock) {
        $values = array();
        foreach($collectedKeys AS $collectedKey) {
            $values[$collectedKey] = isset($targetBlock[$collectedKey]) ? $targetBlock[$collectedKey] : '';
        }
        fputcsv($fh, $values);
    }
    fclose($fh);
}
exit();
foreach (glob($txtPath . DS . '附表一*.DOC.txt') AS $txtFile) {
    $targetFile = str_replace(array('.DOC.txt', 'txt' . DS), array('.csv', 'blocks' . DS), $txtFile);
    $fh = fopen($targetFile, 'w');
    fputcsv($fh, array('編號', '公告日期', '品名', '使用食品範圍及限量標準', '使用限制'));
    $content = file_get_contents($txtFile);
    $content = preg_replace('/[ ]+/i', ' ', $content);
    $types = array(
        1 => '|編號 |
|品名 |
|使用食品範圍及限量 |
|使用限制 |',
        2 => '|編號 |品名 |使用食品範圍及限量 |使用限制 |',
        3 => '|編|品名 |使用食品範圍及限量 |使 |',
        4 => '|編 |品 名 |使用食品範圍及限量 |使用限制 |',
        5 => '|編|品名 |使用食品範圍及限量標準 |使用限制 |',
        6 => '|編 |品名 |使用食品範圍及限量 |使用限制 |',
        7 => '|公告 |編|品名 |使用食品範圍及限|使用限制 |',
        8 => '|公 |編|品名 |使用食品範圍及限 |使用限制 |',
        9 => '第（十五）類　 溶劑  ',
        10 => '|公告|編|品名 |使用食品範圍及限|使用限制 |',
        11 => '|公告 |編|品名 |使用食品範圍及限量|使 |',
    );
    $contentType = 0;
    $contentPos = false;
    foreach ($types AS $key => $type) {
        if (false === $contentPos) {
            $contentPos = strpos($content, $type);
            if (false !== $contentPos) {
                $contentType = $key;
                $contentPos += strlen($type);
            }
        }
    }
    switch ($contentType) {
        case 1:
            $content = substr($content, $contentPos);
            $lines = explode("\n", $content);
            $targetBlocks = array();
            $currentBlock = array();
            $lineCount = 0;
            foreach ($lines AS $line) {
                if (substr($line, 0, 1) === '|') {
                    ++$lineCount;
                    $columns = explode('|', $line);
                    if (preg_match('/^[0-9][0-9][0-9]/', $columns[1])) {
                        if (!empty($currentBlock)) {
                            $targetBlocks[] = $currentBlock;
                            $currentBlock = array(
                                'S/N #' => '',
                                'Name' => '',
                                'Description' => '',
                            );
                            $lineCount = 1;
                        }
                    }
                    switch ($lineCount) {
                        case 1:
                            $currentBlock['S/N #'] = trim($columns[1]);
                            break;
                        case 2:
                            $currentBlock['Name'] = trim($columns[1]);
                            break;
                        case 3:
                        case 4:
                            $columns[1] = str_replace(array('（', '）'), array('(', ')'), $columns[1]);
                            if (preg_match('/[a-z\\(]/i', substr($columns[1], 0, 1))) {
                                $currentBlock['Name'] .= ' ' . trim($columns[1]);
                            } else {
                                $currentBlock['Description'] .= trim($columns[1]);
                            }
                            break;
                        default:
                            $lineContent = trim($columns[1]);
                            if (!empty($lineContent)) {
                                $currentBlock['Description'] .= $lineContent;
                            }
                            break;
                    }
                }
            }
            if (!empty($currentBlock)) {
                $targetBlocks[] = $currentBlock;
                $currentBlock = array();
            }
            foreach ($targetBlocks AS $targetBlock) {
                if (empty($targetBlock['Name']) || empty($targetBlock['S/N #']))
                    continue;
                fputcsv($fh, array($targetBlock['S/N #'], '', $targetBlock['Name'], $targetBlock['Description'], ''));
            }
            break;
        case 2:
        case 4:
        case 6:
            $content = substr($content, $contentPos);
            $lines = explode("\n", $content);
            $targetBlocks = array();
            $currentBlock = array();
            foreach ($lines AS $line) {
                if (substr($line, 0, 1) === '|') {
                    $columns = explode('|', $line);
                    if (preg_match('/[0-9][0-9][0-9]/', $columns[1])) {
                        if (!empty($currentBlock)) {
                            $targetBlocks[] = $currentBlock;
                            $currentBlock = array();
                        }
                    }
                    foreach ($columns AS $key => $val) {
                        $currentBlock[$key] .= $val;
                    }
                }
            }
            if (!empty($currentBlock)) {
                $targetBlocks[] = $currentBlock;
                $currentBlock = array();
            }
            foreach ($targetBlocks AS $targetBlock) {
                foreach ($targetBlock AS $key => $val) {
                    $targetBlock[$key] = trim($val);
                }
                if (empty($targetBlock[2]) || empty($targetBlock[1]))
                    continue;
                fputcsv($fh, array($targetBlock[1], '', $targetBlock[2], $targetBlock[3], $targetBlock[4]));
            }
            break;
        case 3:
        case 5:
            $content = substr($content, $contentPos);
            $lines = explode("\n", $content);
            $targetBlocks = array();
            $currentBlock = array();
            foreach ($lines AS $line) {
                if (substr($line, 0, 1) === '|') {
                    $columns = explode('|', $line);
                    if (preg_match('/[0-9][0-9]/', $columns[1])) {
                        if (!empty($currentBlock)) {
                            $targetBlocks[] = $currentBlock;
                            $currentBlock = array();
                        }
                    }
                    foreach ($columns AS $key => $val) {
                        $currentBlock[$key] .= $val;
                    }
                }
            }
            if (!empty($currentBlock)) {
                $targetBlocks[] = $currentBlock;
                $currentBlock = array();
            }
            foreach ($targetBlocks AS $targetBlock) {
                foreach ($targetBlock AS $key => $val) {
                    $targetBlock[$key] = trim($val);
                }
                if (empty($targetBlock[2]) || empty($targetBlock[1]))
                    continue;
                fputcsv($fh, array($targetBlock[1], '', $targetBlock[2], $targetBlock[3], $targetBlock[4]));
            }
            break;
        case 7:
        case 8:
        case 9:
        case 10:
        case 11:
            $content = substr($content, $contentPos);
            $lines = explode("\n", $content);
            $targetBlocks = array();
            $currentBlock = array();
            foreach ($lines AS $line) {
                if (substr($line, 0, 1) === '|') {
                    $columns = explode('|', $line);
                    if (preg_match('/[0-9][0-9]/', $columns[2])) {
                        if (!empty($currentBlock)) {
                            $targetBlocks[] = $currentBlock;
                            $currentBlock = array();
                        }
                    }
                    foreach ($columns AS $key => $val) {
                        $currentBlock[$key] .= $val;
                    }
                }
            }
            if (!empty($currentBlock)) {
                $targetBlocks[] = $currentBlock;
                $currentBlock = array();
            }
            foreach ($targetBlocks AS $targetBlock) {
                foreach ($targetBlock AS $key => $val) {
                    $targetBlock[$key] = trim($val);
                }
                if (empty($targetBlock[3]) || empty($targetBlock[2]))
                    continue;
                fputcsv($fh, array($targetBlock[2], $targetBlock[1], $targetBlock[3], $targetBlock[4], $targetBlock[5]));
            }
            break;
        default:
            echo $txtFile . "\n";
            break;
    }
    fclose($fh);
}