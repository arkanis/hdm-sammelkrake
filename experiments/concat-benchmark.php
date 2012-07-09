<?php

/**
 * A small test to determine if it is faster to concat many strings or store
 * them in an array and then join that array once.
 */

$test_data = file_get_contents('/dev/urandom', false, null, -1, 512 * 1024);

$result = '';
$start = microtime(true);
for($i = 0; $i < strlen($test_data); $i++)
	$result .= $test_data[$i];
$duration = microtime(true) - $start;
printf("dot operator to concat each char: %fs, equal: %d\n", $duration, $result == $test_data);

$result = '';
$chars = array();
$start = microtime(true);
for($i = 0; $i < strlen($test_data); $i++)
	$chars[] = $test_data[$i];
$result = implode('', $chars);
$duration = microtime(true) - $start;
printf("store in array and then implode: %fs, equal: %d\n", $duration, $result == $test_data);


?>