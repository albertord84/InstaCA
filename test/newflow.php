<?php

require __DIR__ . '/../vendor/autoload.php';

use \InstagramAPI\Signatures;
use \InstagramAPI\Constants;
use \GuzzleHttp\Client;
use \GuzzleHttp\Cookie\SetCookie;
use \GuzzleHttp\Cookie\CookieJar;

$client = null;
$deviceId = Signatures::generateUUID();
$phoneId = Signatures::generateUUID();

$initUrl = '/api/v1/fb/show_continue_as/';

function isDebug($debug = true) {
  return $debug;
}

function getUserAgent($userAgent = '') {
  $_userAgent = "Instagram 41.0.0.13.92 Android (19/4.4.4; 120dpi; 360x684; innotek GmbH/Android-x86; x86; android_x86; en_US; 103516666)";
  return $userAgent === '' ? $_userAgent : $userAgent;
}

function getHeaders() {
  $headers = [
    'User-Agent' => getUserAgent(),
    'X-IG-Connection-Speed' => '-1kbps',
    'X-IG-Bandwidth-Speed-KBPS' => '-1.000',
    'X-IG-Bandwidth-TotalBytes-B' => '0',
    'X-IG-Bandwidth-TotalTime-MS' => '0',
    'X-IG-Connection-Type' => 'ETHERNET',
    'X-IG-Capabilities' => '3brTHw==',
    'X-IG-App-ID' => '567067343352427',
    'Accept-Language' => 'en-US',
    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
    'Connection' => 'Keep-Alive',
    'Accept-Encoding' => 'gzip',
  ];
  return $headers;
}

function createClient($_options = []) {
  $jar = new CookieJar;
  $options = [
    'base_uri' => 'https://b.i.instagram.com',
    'cookies' => $jar,
    'debug' => isDebug(),
    'headers' => getHeaders(),
  ];
  $combined = array_merge($options, $_options);
  $client = new Client($combined);
  return $client;
}

function generateRequestBody($data) {
  $signedData = Signatures::signData($data);
  $body = sprintf("signed_body=%s&ig_sig_key_version=%s",
    $signedData['signed_body'], $signedData['ig_sig_key_version']);
  return $body;
}

try {
  $cookies = new CookieJar;
  $data = [
    "phone_id" => $phoneId,
    "screen" => "landing",
    "device_id" => $deviceId,
  ];
  $client = createClient([
    'body' => generateRequestBody($data),
  ]);
  $response = $client->post($initUrl);
  $cookies = $client->getConfig('cookies');
  $body = $response->getBody();
  var_dump($cookies);
  echo $body;
} catch (\Exception $e) {
  echo "ERROR: " . $e->getMessage();
}
