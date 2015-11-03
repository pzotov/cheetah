<?
set_time_limit(0);
require_once '../netcat/modules/cheetah/cheetah.php';
header('Content-type: text/plain; charset=utf-8');

$start = microtime(true);
for($i=0; $i<10000000; $i++){
	$j = class_exists('Cheetah');
}
$time1 = microtime(true)-$start;
$start = microtime(true);
for($i=0; $i<10000000; $i++){
	$j = defined('Cheetah');
}
$time2 = microtime(true)-$start;

echo $time1."\n";
echo $time2."\n";