<?php

require __DIR__ . '/../vendor/autoload.php';

use \InstaCA\InstaCA;
use \InstagramAPI\Signatures;
use \GuzzleHttp\Cookie\SetCookie;
use \GuzzleHttp\Cookie\CookieJar;

$requestData = json_decode(file_get_contents('php://input'));
$cookies = $requestData->cookies;

if (trim($requestData->username) === '' || trim($requestData->password) === '') {
    header('Content-Type: text/json; charset=UTF-8', true);
    header('Status: 500', true, 500);
    $response = [
        'message' => 'Unable to request without the right credentials (username/password)',
        'success' => false
    ];
    echo json_encode($response);
    die();
}

if (count($cookies)===0) {
    // must login
    $insta = new InstaCA();
    $loginResp = $insta->login($requestData->username, $requestData->password);
    $cookies = $loginResp['cookies'];
}

$query = $requestData->location;
$count = $requestData->count;
$rank_token = $requestData->rank_token;
$exclude_list = join(',', $requestData->exclude_list);

$locationSearchUrl = "https://i.instagram.com/api/v1/fbsearch/places/?timezone_offset=0" .
        "&count=$count&query=$query&exclude_list=[$exclude_list]&rank_token=$rank_token";

$jar = new CookieJar(false, $cookies);
$client = new \GuzzleHttp\Client([
    'cookies' => $jar,
]);

try {
    $locationResponse = $client->get($locationSearchUrl, [
        // 'debug' => true,
        'headers' => $insta->getHeaders(),
    ]);
} catch(\Exception $initEx) {
    header('Content-Type: text/json; charset=UTF-8', true);
    header('Status: 500', true, 500);
    $response = [
        'message' => 'Unable to request location list matching ' . $query,
        'success' => false
    ];
    echo json_encode($response);
    die();
}

$data = json_decode((string) $locationResponse->getBody());
header('Content-Type: text/json; charset=UTF-8', true);
header('Status: 200', true, 200);
$response = [
    'success' => true,
    'cookies' => $client->getConfig('cookies')->toArray(),
    'ig' => $data,
];
echo json_encode($response);
die();
