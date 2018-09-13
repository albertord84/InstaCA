<?php

require __DIR__ . '/../vendor/autoload.php';

\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;

$req_data = json_decode(file_get_contents('php://input'), true);
$instagram = new \InstagramAPI\Instagram();

$login_resp = null;
try {
    $login_resp = $instagram->login($req_data['username'], $req_data['password'], 3600 * 5);
    echo json_encode($location_resp);
} catch (\Exception $e) {
    $data = [
        'items' => [],
        'error' => $e->getMessage(),
    ];
    echo json_encode($data);
}
