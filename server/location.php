<?php

require __DIR__ . '/../vendor/autoload.php';

use \InstaCA\InstaCA;
use \InstagramAPI\Signatures;
use \InstagramAPI\Exception\RequestHeadersTooLargeException;
use \GuzzleHttp\Cookie\SetCookie;
use \GuzzleHttp\Cookie\CookieJar;

$requestData = json_decode(file_get_contents('php://input'), true);
$cookies = $requestData['cookies'];
$insta = new InstaCA();

if (trim($requestData['username']) === '' || trim($requestData['password']) === '') {
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
    $loginResp = $insta->login($requestData['username'], $requestData['password']);
    $cookies = $loginResp['cookies'];
}

$query = $requestData['location'];
$count = $requestData['count'];
$rank_token = $requestData['rank_token'];
$exclude_list = $requestData['exclude_list'];

$locationSearchUrl = "https://i.instagram.com/api/v1/fbsearch/places/?timezone_offset=0" .
        "&count=$count&query=$query&exclude_list=[$exclude_list]&rank_token=$rank_token";

$cookiesArray = array_reduce($cookies, function($array, $cookie) {
    $array[] = new SetCookie($cookie);
    return $array;
}, []);

$jar = new CookieJar(false, $cookiesArray);
$client = new \GuzzleHttp\Client([
    'cookies' => $jar,
]);

sleep(mt_rand(2, 3));
try {
    $locationResponse = $client->get($locationSearchUrl, [
        'headers' => $insta->getHeaders(),
    ]);
} catch(\Exception $locSearchEx) {
    header('Content-Type: text/json; charset=UTF-8', true);
    header('Status: 200', true, 200);
    $message = sprintf('Unable to request location list matching %s. CAUSE: %s',
        $query, $locSearchEx->getMessage());
    $response = [
        'message' => $message,
        'cookies' => $client->getConfig('cookies')->toArray(),
        'success' => false,
        'ig' => [
            'items' => [],
            'rank_token' => '',
            'has_more' => false
        ]
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
