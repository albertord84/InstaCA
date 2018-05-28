<?php

require __DIR__ . '/../vendor/autoload.php';

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

/**
 * signed_body=72eb3ebafb52186434e0d6d8bb9667e5bff58474e8b7d6e3150fd29cf577ec5e.{"_csrftoken":null,"device_id":"9617f505-f5e5-45fe-9be2-c70584ab52e1"}
 * &ig_sig_key_version=4
 * 
 * signed_body=5a50bce76d77c9ba037d341ec915abac03534f5ad51ca3ffe7cb97ca666382b3.{"_csrftoken":"null","device_id":"87841ee1-1e85-4d29-b780-b729bf3621a3"}
 * &ig_sig_key_version=4
 */

/*$ig = new \InstagramAPI\Instagram(true);
$ig->login($username, $password, 5);
if (true) die();*/

$debugRequest = true;
$userAgent = 'Instagram 27.0.0.7.97 Android (23/6.0.1; 640dpi; 1440x2392; LGE/lge; RS988; h1; h1; en_US)';

$HEADERS = [
    'User-Agent' => $userAgent,
    'Connection' => 'Keep-Alive',
    'X-FB-HTTP-Engine' => 'Liger',
    'Accept' => '*/*',
    'Accept-Encoding' => 'gzip,deflate',
    'Accept-Language' => 'en-US',
    'X-IG-App-ID' => 567067343352427,
    'X-IG-Capabilities' => '3brTBw==',
    'X-IG-Connection-Type' => 'WIFI',
    'X-IG-Connection-Speed' => sprintf("%skbps", (int) mt_rand(750, 2048)),
    'X-IG-Bandwidth-Speed-KBPS' => '-1.000',
    'X-IG-Bandwidth-TotalBytes-B' => 0,
    'X-IG-Bandwidth-TotalTime-MS' => 0,
    'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
];

$baseUri = 'https://i.instagram.com';
$initialDataUrl = '/api/v1/accounts/read_msisdn_header/';
$loginUrl = '/api/v1/accounts/login/';
$syncDeviceUrl = '/api/v1/qe/sync/';

$prevCookies = [
    \GuzzleHttp\Cookie\SetCookie::fromString('ds_user=yordanoweb; expires=Sun, 26-Aug-2018 17:54:03 GMT; Max-Age=7776000; Path=/; Domain=i.instagram.com'),
    \GuzzleHttp\Cookie\SetCookie::fromString('shbid=12443; expires=Mon, 04-Jun-2018 17:54:03 GMT; Max-Age=604800; Path=/; Domain=i.instagram.com'),
    \GuzzleHttp\Cookie\SetCookie::fromString('shbts=1527530043.0920126; expires=Mon, 04-Jun-2018 17:54:03 GMT; Max-Age=604800; Path=/; Domain=i.instagram.com'),
    \GuzzleHttp\Cookie\SetCookie::fromString('rur=FTW; Path=/; Domain=i.instagram.com'),
    \GuzzleHttp\Cookie\SetCookie::fromString('csrftoken=wDWLk6IHQMY5oaZGpUlQ6Nk0HB2VQMoR; expires=Mon, 27-May-2019 17:54:03 GMT; Max-Age=31449600; Path=/; Secure; Domain=i.instagram.com'),
    \GuzzleHttp\Cookie\SetCookie::fromString('ds_user_id=3670825632; expires=Sun, 26-Aug-2018 17:54:03 GMT; Max-Age=7776000; Path=/; Domain=i.instagram.com'),
    \GuzzleHttp\Cookie\SetCookie::fromString('urlgen="{\"time\": 1527530042\054 \"169.158.137.122\": 10569}:1fNMLL:X36zcetnzKeI6fGHUrGJDNZXuwk"; Path=/; Domain=i.instagram.com'),
    \GuzzleHttp\Cookie\SetCookie::fromString('sessionid=IGSCd02251363613fadb87fc18b530dd37cc10525dc27ecdb74ff8ca1dbd39b472d5%3AZ0oQRMFt4PHb97VazEnpNPCHqk4XwXrO%3A%7B%22_auth_user_id%22%3A3670825632%2C%22_auth_user_backend%22%3A%22accounts.backends.CaseInsensitiveModelBackend%22%2C%22_auth_user_hash%22%3A%22%22%2C%22_platform%22%3A1%2C%22_token_ver%22%3A2%2C%22_token%22%3A%223670825632%3A7dlVNKM2Z33ZkSQMuVlsmJ0WD032F4RI%3A137ad4d2cd06b607b338b5c7f4dc171156a09e94ad04ccb2f8d98ac53a8637e4%22%2C%22last_refreshed%22%3A1527530043.0928833485%7D; expires=Sun, 26-Aug-2018 17:54:03 GMT; HttpOnly; Max-Age=7776000; Path=/; Secure; Domain=i.instagram.com'),

    // Cookies que tienen que ser añadidas a partir de cada
    // petición hecha posterior al login
    \GuzzleHttp\Cookie\SetCookie::fromString('igfl=yordanoweb; Path=/; Max-Age=86400; ' .
        'Expires=' . (int) (date('U') - (3600 * 24 * 2)) . '; Domain=i.instagram.com'),
    \GuzzleHttp\Cookie\SetCookie::fromString('ig_direct_region_hint=""; Expires=0; Max-Age=0; Path=/; Domain=i.instagram.com'),
    \GuzzleHttp\Cookie\SetCookie::fromString('is_starred_enabled=yes; Max-Age=630720000; ' .
        'Expires=' . (int) (date('U') - (3600 * 24 * 360 * 10)) . '; Path=/; Domain=i.instagram.com'),
];

$jar = new \GuzzleHttp\Cookie\CookieJar(false, $prevCookies);

$uuid = \InstagramAPI\Signatures::generateUUID();

$client = new \GuzzleHttp\Client([
    'base_uri' => $baseUri,
    'cookies' => $jar,
]);

if (!\InstagramAPI\Signatures::isValidUUID($uuid)) {
    echo 'Not valid UUID';
    die();
}

/*$initialData = \InstagramAPI\Signatures::signData([
    "_csrftoken" => 'null',
    "device_id" => $uuid,
]);

$firstRequestBody = sprintf("signed_body=%s&ig_sig_key_version=%s",
        $initialData['signed_body'], $initialData['ig_sig_key_version']);
echo $firstRequestBody . PHP_EOL;

try {
    $initialDataResponse = $client->post($initialDataUrl, [
        'debug' => $debugRequest,
        'body' => $firstRequestBody,
        'headers' => $HEADERS,
    ]);
} catch(\Exception $initEx) {
    printf("%s\n", $initEx->getMessage());
    die();
}

echo PHP_EOL . PHP_EOL . PHP_EOL;
echo PHP_EOL . PHP_EOL . PHP_EOL . json_encode($client->getConfig('cookies')->toArray());
echo PHP_EOL . PHP_EOL . PHP_EOL;

$syncDeviceData = \InstagramAPI\Signatures::signData([
    'id' => $uuid,
    'device_id' => $uuid,
    'experiments' => 'ig_android_updated_copy_user_lookup_failed,ig_android_hsite_prefill_new_carrier,ig_android_me_profile_prefill_in_reg,ig_android_allow_phone_reg_selectable,ig_android_gmail_oauth_in_reg,ig_android_run_account_nux_on_server_cue_device,ig_android_universal_instagram_deep_links_universe,ig_android_make_sure_next_button_is_visible_in_reg,ig_android_report_nux_completed_device,ig_android_sim_info_upload,ig_android_reg_omnibox,ig_android_background_phone_confirmation_v2,ig_android_background_voice_phone_confirmation,ig_android_password_toggle_on_login_universe_v2,ig_android_skip_signup_from_one_tap_if_no_fb_sso,ig_android_refresh_onetap_nonce,ig_android_multi_tap_login,ig_android_onetaplogin_login_upsell,ig_android_jp_sms_code_extraction_fix, ig_challenge_kill_switch,ig_android_modularized_nux_universe_device,ig_android_run_device_verification,ig_android_remove_sms_password_reset_deep_link,ig_android_phone_id_email_prefill_in_reg,ig_android_typeahead_bug_fixes_universe,ig_restore_focus_on_reg_textbox_universe,ig_android_abandoned_reg_flow,ig_android_phoneid_sync_interval,ig_android_2fac_auto_fill_sms_universe,ig_android_family_apps_user_values_provider_universe,ig_android_security_intent_switchoff,ig_android_enter_to_login,ig_android_show_password_in_reg_universe,ig_android_access_redesign,ig_android_remove_icons_in_reg_v2,ig_android_ui_cleanup_in_reg_v2,ig_android_login_bad_password_autologin_universe,ig_android_editable_username_in_reg'
]);
$syncDeviceRequestBody = sprintf("signed_body=%s&ig_sig_key_version=%s",
    $syncDeviceData['signed_body'], $syncDeviceData['ig_sig_key_version']);
echo $syncDeviceRequestBody . PHP_EOL;

sleep(mt_rand(2, 5));
try {
    $syncDeviceResponse = $client->post($syncDeviceUrl, [
        'debug' => $debugRequest,
        'body' => $syncDeviceRequestBody,
        'headers' => $HEADERS,
    ]);
} catch(\Exception $syncEx) {
    printf("%s\n", $syncEx->getMessage());
    die();
}

echo PHP_EOL . PHP_EOL . PHP_EOL;
echo PHP_EOL . PHP_EOL . PHP_EOL . json_encode($client->getConfig('cookies')->toArray());
echo PHP_EOL . PHP_EOL . PHP_EOL;

$loginData = \InstagramAPI\Signatures::signData([
    'phone_id' => $uuid,
    '_csrftoken' => $jar->getCookieByName('csrftoken')->getValue(),
    'username' => $username,
    'password' => $password,
    'device_id' => \InstagramAPI\Signatures::generateDeviceId(),
    'login_attempt_count' => '0',
    'adid' => \InstagramAPI\Signatures::generateUUID(),
    'guid' => \InstagramAPI\Signatures::generateUUID(),
]);

$loginRequestBody = sprintf("signed_body=%s&ig_sig_key_version=%s",
        $loginData['signed_body'], $loginData['ig_sig_key_version']);
echo $loginRequestBody . PHP_EOL;

sleep(mt_rand(2, 5));
try {
    $loginResponse = $client->post($loginUrl, [
        'debug' => $debugRequest,
        'body' => $loginRequestBody,
        'headers' => $HEADERS,
    ]);
} catch(\Exception $loginEx) {
    printf("%s\n", $loginEx->getMessage());
    die();
}

echo PHP_EOL . PHP_EOL . PHP_EOL;
echo PHP_EOL . PHP_EOL . PHP_EOL . json_encode($client->getConfig('cookies')->toArray());
echo PHP_EOL . PHP_EOL . PHP_EOL;

echo $loginResponse->getBody() . PHP_EOL;*/

$count = 15;
$query = 'tokyo';
$exclude_list = '';
$rank_token = '';
$locationTestUrl = "https://i.instagram.com/api/v1/fbsearch/places/?timezone_offset=0" .
        "&count=$count&query=$query&exclude_list=[$exclude_list]&rank_token=$rank_token";

sleep(mt_rand(1, 3));
try {
    $locationResponse = $client->get($locationTestUrl, [
        'debug' => $debugRequest,
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
