<?php
$username = trim(@$argv[1]); $password = trim(@$argv[2]); $hashtag = trim(@$argv[3]);
$count = 50; $name = urlencode($hashtag);

set_time_limit(0); date_default_timezone_set('UTC');
require __DIR__ . '/../server/vendor/autoload.php';
$debug = false;$truncatedDebug = true;

$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);
$ig->login($username, $password, 10800);


$exclude_list = ''; $rank_token = '';
do {
	$url = "https://i.instagram.com/api/v1/tags/search/?timezone_offset=0&" .
		"q=$name&count=$count&exclude_list=[$exclude_list]&rank_token=$rank_token";
	$request = $ig->request($url);

	try {
		$response = \InstagramAPI\Client::api_body_decode($request->getRawResponse(), false);
	} catch (Exception $e) {
        $response->has_more = false;
        $response->results = [];
        $response->rank_token = $rank_token;
        $exclude_list = '';
	}

	printf("GET: %s\n\n", $url);
	foreach ($response->results as $item) {
		printf("%s: %s\n", $item->id, $item->name);
	}

	if ($response->has_more) {
		$rank_token = $response->rank_token;
		$exclude_list = array_reduce($response->results, function($carry, $item) {
			return $carry . ($carry !== '' ? ',' : '') . $item->id;
		}, $exclude_list);
		echo PHP_EOL . PHP_EOL;
	}

	sleep(mt_rand(3,5));
} while ($response->has_more);

