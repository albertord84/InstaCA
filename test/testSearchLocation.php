<?php

$username = trim($argv[1]);
$password = trim($argv[2]);
$location = trim($argv[3]);
$count = 10;
$query = urlencode($location);

set_time_limit(0);
date_default_timezone_set('UTC');
require __DIR__ . '/../server/vendor/autoload.php';
$debug = true;
$truncatedDebug = false;

$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);
$ig->login($username, $password, 21600);

$response = $ig->request('accounts/read_msisdn_header/')
                ->setNeedsAuth(false)
                ->addPost('device_id', \InstagramAPI\Signatures::generateUUID())
                ->addPost('_csrftoken', null)->getRawResponse();

$exclude_list = ''; $rank_token = '';
do {
    $url = "https://i.instagram.com/api/v1/fbsearch/places/?timezone_offset=0" .
            "&count=$count&query=$query&exclude_list=[$exclude_list]&rank_token=$rank_token";
    $request = $ig->request($url);

    echo $ig->client->getCookieJarAsJSON() . PHP_EOL;
    sleep(5);

    try {
        $response = \InstagramAPI\Client::api_body_decode($request->getRawResponse(), false);
    } catch (Exception $e) {
        $response->has_more = false;
        $response->items = [];
        $response->rank_token = $rank_token;
        $exclude_list = '';
    }

    printf("GET: %s\n\n", $url);
    foreach ($response->items as $item) {
        printf("%s: %s\n", $item->location->facebook_places_id, $item->location->name);
    }

    if ($response->has_more) {
        $rank_token = $response->rank_token;
        $exclude_list = array_reduce($response->items, function($carry, $item) {
            return $carry . ($carry !== '' ? ',' : '') . $item->location->facebook_places_id;
        }, $exclude_list);
        echo PHP_EOL . PHP_EOL;
    }

    sleep(mt_rand(3, 5));
} while ($response->has_more);

echo PHP_EOL;
