<?php
ini_set('memory_limit', '8192M');
include("ZDA.inc.php");
$zda = new ZDArchive();

// 压缩测试
$comp = $zda->compress(file_get_contents("TestString.txt"));
file_put_contents("TestString.zda", $comp);

// 解压缩测试
file_put_contents("TestString_decompress.txt", $zda->decompress(file_get_contents("TestString.zda")));

// 直接输出到文件
$zda->compress(file_get_contents("TestString.txt"), "TestString_out.zda");

// 压缩大文件（分段处理，不支持 GZIP 压缩）
$zda->compress(file_get_contents("TestData.big"), "TestData_out.zda", true);

// AES 加密（文件名留空则返回压缩后内容）
$aesc = $zda->compress(file_get_contents("TestString.txt"), "", false, "testAESKey");
file_put_contents("TestString_aes.zda", $aesc);
