<?php

require __DIR__ . '/../vendor/autoload.php';

use \InstagramAPI\Signatures;
use \GuzzleHttp\Cookie\SetCookie;
use \GuzzleHttp\Cookie\CookieJar;

if (isset($_REQUEST['username'])) {
    $username = $_REQUEST['username'];
    $password = $_REQUEST['password'];
} else {
    $jsonRequestData = json_decode(file_get_contents("php://input"), true);
    $username = $jsonRequestData['username'];
    $password = $jsonRequestData['password'];
    if (!isset($username) || $username === null) {
        $username = trim($argv[1]);
        $password = trim($argv[2]);
    }
}

$baseUri = 'https://i.instagram.com';
$initialDataUrl = '/api/v1/accounts/read_msisdn_header/';
$loginUrl = '/api/v1/accounts/login/';
$syncDeviceUrl = '/api/v1/qe/sync/';

$prevCookies = '[{"Name":"mid","Value":"Ww7kdQABAAG4YBE4HcBcWed0tZZU","Domain":"i.instagram.com","Path":"\/","Max-Age":"630720000","Expires":2158422645,"Secure":false,"Discard":false,"HttpOnly":false},{"Name":"csrftoken","Value":"8xGesEjflrS7NaM1OQnGFLelgA1mWQB0","Domain":"i.instagram.com","Path":"\/","Max-Age":"31449600","Expires":1559152252,"Secure":true,"Discard":false,"HttpOnly":false},{"Name":"ds_user","Value":"yordanoweb","Domain":".instagram.com","Path":"\/","Max-Age":"7776000","Expires":1535478655,"Secure":false,"Discard":false,"HttpOnly":false},{"Name":"shbid","Value":"12443","Domain":".instagram.com","Path":"\/","Max-Age":"604800","Expires":1528307455,"Secure":false,"Discard":false,"HttpOnly":false},{"Name":"shbts","Value":"1527702655.1344602","Domain":".instagram.com","Path":"\/","Max-Age":"604800","Expires":1528307455,"Secure":false,"Discard":false,"HttpOnly":false},{"Name":"rur","Value":"FTW","Domain":".instagram.com","Path":"\/","Max-Age":null,"Expires":null,"Secure":false,"Discard":false,"HttpOnly":false},{"Name":"csrftoken","Value":"bImLYAwsgnhQbUD52uXYI9rRPN1GJZMi","Domain":".instagram.com","Path":"\/","Max-Age":"31449600","Expires":1559152255,"Secure":true,"Discard":false,"HttpOnly":false},{"Name":"ds_user_id","Value":"3670825632","Domain":".instagram.com","Path":"\/","Max-Age":"7776000","Expires":1535478655,"Secure":false,"Discard":false,"HttpOnly":false},{"Name":"urlgen","Value":"\"{\\\"time\\\": 1527702655\\054 \\\"169.158.137.122\\\": 10569}:1fO5FP:zvV4zxi6uP01s7gfBulMU7rFQXM\"","Domain":".instagram.com","Path":"\/","Max-Age":null,"Expires":null,"Secure":false,"Discard":false,"HttpOnly":false},{"Name":"sessionid","Value":"IGSC5cee151cc4683d25f39c127a01e3f0b18080ea86a6da86f647cd746dceb4f052%3Aa3Jr8nCGxDqGpAv403SXZwpQ0rSgVVYf%3A%7B%22_auth_user_id%22%3A3670825632%2C%22_auth_user_backend%22%3A%22accounts.backends.CaseInsensitiveModelBackend%22%2C%22_auth_user_hash%22%3A%22%22%2C%22_platform%22%3A1%2C%22_token_ver%22%3A2%2C%22_token%22%3A%223670825632%3ABZMwXGzXSUo8pPHV7SoYznRyENHfNB0D%3Ae9122f286f417892bcd632d35cf74a866769408b26931cb797cd46037590bad4%22%2C%22last_refreshed%22%3A1527702655.1352510452%7D","Domain":".instagram.com","Path":"\/","Max-Age":"7776000","Expires":1535478655,"Secure":true,"Discard":false,"HttpOnly":true},{"Name":"mid","Value":"Ww7kdQABAAG4YBE4HcBcWed0tZZU","Domain":".instagram.com","Path":"\/","Max-Age":null,"Expires":null,"Secure":false,"Discard":false,"HttpOnly":false},{"Name":"mcd","Value":"3","Domain":".instagram.com","Path":"\/","Max-Age":null,"Expires":null,"Secure":false,"Discard":false,"HttpOnly":false},"yordanoweb","\"\"","yes"]';

$jar = new CookieJar(false, $prevCookies);

$uuid = Signatures::generateUUID();

$client = new \GuzzleHttp\Client([
    'base_uri' => $baseUri,
    'cookies' => $jar,
]);

if (!Signatures::isValidUUID($uuid)) {
    echo 'Not valid UUID';
    die();
}

$count = 15;
$query = 'yangtze';
$exclude_list = '';
$rank_token = '';
$locationTestUrl = "https://i.instagram.com/api/v1/fbsearch/places/?timezone_offset=0" .
        "&count=$count&query=$query&exclude_list=[$exclude_list]&rank_token=$rank_token";

sleep(mt_rand(1, 3));
try {
    $locationResponse = $client->get($locationTestUrl, [
        'debug' => $true,
        'headers' => $HEADERS
    ]);
} catch(\Exception $initEx) {
    printf("%s\n", $initEx->getMessage());
    die();
}

echo PHP_EOL . PHP_EOL . PHP_EOL;
echo $locationResponse->getBody() . PHP_EOL;
echo PHP_EOL . PHP_EOL . PHP_EOL . json_encode($client->getConfig('cookies')->toArray());
echo PHP_EOL . PHP_EOL . PHP_EOL;

if (true) die();
