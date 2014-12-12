<?php
use Nette\Utils\Json;
use Nette\Utils\Strings;
use NatureQuizzer\Tools\GoogleImageDownloader;

include_once __DIR__ . "/../app/bootstrap.php";

Tracy\Debugger::enable(\Tracy\Debugger::DEVELOPMENT);

$result = [];

$result = unserialize(file_get_contents(__DIR__ . '/../staging/testing/data.txt'));

$downloader = new GoogleImageDownloader(__DIR__ . '/../staging/testing/');
$i = 0;
foreach ($result as $latin => $czech) {
	echo "Processing: " . $latin;
	$result = $downloader->fetch($latin);
	if (!$result) echo "... skipped";
	echo "\n";
	/*if (((++$i) % 50) == 0) {
		echo "Waiting for 120 seconds: \n";
		sleep(120);
	}*/
}


