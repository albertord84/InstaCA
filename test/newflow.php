<?php

require __DIR__ . '/../vendor/autoload.php';

use \InstagramAPI\Signatures;
use \InstagramAPI\Constants;

use \GuzzleHttp\Client;
use \GuzzleHttp\Cookie\SetCookie;
use \GuzzleHttp\Cookie\CookieJar;
use \GuzzleHttp\Psr7\Request;
use \GuzzleHttp\Psr7\Response;

$client = null;
$deviceId = Signatures::generateUUID();
$phoneId = Signatures::generateUUID();

$initUrl = '/api/v1/fb/show_continue_as/';
$secondUrl = '/api/v1/accounts/msisdn_header_bootstrap/';
$thirdUrl = '/api/v1/qe/sync/';
$fourthUrl = '/api/v1/attribution/log_attribution/';
$fifthUrl = '/api/v1/accounts/contact_point_prefill/';
$finalUrl = '/api/v1/accounts/login/';

function isDebug($debug = true) {
  return $debug;
}

function getUserAgent($userAgent = '') {
  $_userAgent = "Instagram 41.0.0.13.92 Android (19/4.4.4; 120dpi; 360x684; innotek GmbH/Android-x86; x86; android_x86; en_US; 103516666)";
  return $userAgent === '' ? $_userAgent : $userAgent;
}

function getHeaders($headers = []) {
  $_headers = [
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
  return array_merge($_headers, $headers);
}

function createClient($options = []) {
  $jar = new CookieJar;
  $_options = [
    'base_uri' => 'https://b.i.instagram.com',
    'cookies' => $jar,
    'debug' => isDebug()
  ];
  $combined = array_merge($_options, $options);
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
  $body = generateRequestBody([
    "phone_id" => $phoneId,
    "screen" => "landing",
    "device_id" => $deviceId,
  ]);
  $initialRequest = new Request('POST', $initUrl, getHeaders(), $body);
  $client = createClient();
  $response = $client->send($initialRequest);
  $data = json_decode($response->getBody()->getContents());
  if ($data->status!=='ok') {
    throw new \Exception('Something happened starting the conversation with remote endpoint');
  }
  sleep(2);
  $body = generateRequestBody([
    'mobile_subno_usage' => 'ig_select_app',
    'device_id' => $deviceId,
  ]);
  $secondRequest = new Request('POST', $secondUrl, getHeaders(), $body);
  $response = $client->send($secondRequest);
  $data = json_decode($response->getBody()->getContents());
  if ($data->status!=='ok') {
    throw new \Exception('Something happened at the stage two of the conversation with remote endpoint');
  }
  sleep(3);
  $body = generateRequestBody([
    'id' => $deviceId,
    'experiments' => Constants::LOGIN_EXPERIMENTS,
  ]);
  $thirdRequest = new Request('POST', $thirdUrl,
    getHeaders([ 'X-DEVICE-ID' => $deviceId ]),
    $body);
  $response = $client->send($thirdRequest);
  $data = json_decode($response->getBody()->getContents());
  if ($data->status!=='ok') {
    throw new \Exception('Something happened syncing device features with remote endpoint');
  }
  sleep(2);
  $adid = Signatures::generateUUID();
  $body = generateRequestBody([
    'adid' => $adid
  ]);
  $fourthRequest = new Request('POST', $fourthUrl, getHeaders(), $body);
  $response = $client->send($fourthRequest);
  $data = json_decode($response->getBody()->getContents());
  if ($data->status!=='ok') {
    throw new \Exception('Something happened requesting the log attribution');
  }
  sleep(3);
  $body = generateRequestBody([
    'phone_id' => $phoneId,
    'usage' => 'prefill',
  ]);
  $cookies = $client->getConfig('cookies');
  $fifthRequest = new Request('POST', $fifthUrl,
    getHeaders([
      'Cookie' => sprintf("rur=%s; mcd=%s; mid=%s",
        $cookies->getCookieValue('rur'),
        $cookies->getCookieValue('mcd'),
        $cookies->getCookieValue('mid'))
    ]),
    $body);
  $response = $client->send($fifthRequest);
  $data = json_decode($response->getBody()->getContents());
  if ($data->status!=='ok') {
    throw new \Exception('Something happened requesting the log attribution');
  }
  sleep(1);
  $username = 'fulanitoperez';
  $body = generateRequestBody([
    'phone_id' => $phoneId,
    'username' => $username,
    'adid' => $adid,
    'guid' => $deviceId,
    'device_id' => Signatures::generateDeviceId(),
    'password' => '*************',
    'login_attempt_count' => 0
  ]);
  $cookies = $client->getConfig('cookies');
  $finalRequest = new Request('POST', $finalUrl,
    getHeaders([
      'Cookie' => sprintf("rur=%s; mcd=%s; mid=%s",
        $cookies->getCookieValue('rur'),
        $cookies->getCookieValue('mcd'),
        $cookies->getCookieValue('mid'))
    ]),
    $body);
  $response = $client->send($finalRequest);
  $data = json_decode($response->getBody()->getContents());
  if ($data->status!=='ok') {
    throw new \Exception("Something happened logging the user $username");
  }
  var_dump($data);
} catch (\Exception $e) {
  echo date("G:i:s") . " - ERROR: " . $e->getMessage();
}
