<?php
$src = $argv[1];
$dst = $argv[2];
$s = file_get_contents($src);
$fixed = iconv('Windows-1252', 'UTF-8//IGNORE', $s);
file_put_contents($dst, $fixed);
