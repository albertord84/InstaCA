<?php

require __DIR__ . '/../vendor/autoload.php';

use \InstaCA\InstaCA;
use \InstaCA\Location;
use \InstagramAPI\Signatures;
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

$location = new Location($cookies);

sleep(mt_rand(2, 3));
try {
    header('Content-Type: text/json; charset=UTF-8', true);
    header('Status: 200', true, 200);
    $response = $location->search($query, $count, $exclude_list, $rank_token);
    echo json_encode($response);
} catch(\Exception $locSearchEx) {
    header('Content-Type: text/json; charset=UTF-8', true);
    header('Status: 200', true, 200);
    $message = sprintf('Unable to request location list matching %s. CAUSE: %s',
        $query, $locSearchEx->getMessage());
    $response = [
        'message' => $message,
        'cookies' => $cookies,
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
