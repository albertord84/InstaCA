<?php

namespace InstaCA;

class InstaCA {

    private $debugRequest = false;

    private $withProxy = false;

    private $proxies = [
        'http' => 'tcp://username:password@1.2.3.4:23128',
        'https' => 'tcp://username:password@1.2.3.4:23128'
    ];

    /**
     * Todas las peticiones se hacen a partir de esta URL
     */
    private $baseUri = 'https://i.instagram.com';
    /**
     * Al hacer la petición inicial a esta URL se obtiene el csrf_token
     * a la manera que lo obtienen los dispositivos móviles
     */
    private $initialDataUrl = 'api/v1/accounts/read_msisdn_header/';
    /**
     * Los dispositivos móviles hacen una petición a esta URL
     * luego de la primera petición. Si se quiere imitar su comportamiento
     * no está de más hacerlo nosotros también.
     */
    private $contactPointUrl = 'api/v1/accounts/contact_point_prefill/';
    /**
     * Esta es la URL de la tercera petición que se realiza antes
     * de completar el proceso de autenticación. Aquí los servidores
     * al parecer se informan con más detalle del dispositivo que
     * se está usando para iniciar sesión.
     */
    private $syncDeviceUrl = 'api/v1/qe/sync/';
    /**
     * Esta URL es nueva en la cadena de peticiones de inicio de sesión.
     * Pero al igual que la tercera petición, si se quiere imitar a un
     * dispositivo móvil entonces se debería seguir la cadena.
     */
    private $logAttributionUrl = 'api/v1/attribution/log_attribution/';
    /**
     * Lo mismo que las otras URL. Si se quiere imitar el comportamiento
     * de un móvil, hay que hacer todo lo que hace el móvil cuando inicia
     * sesión. En este caso, hay que verificar los últimos post.
     */
    private $timeLineFeedUrl = 'api/v1/feed/timeline/';
    /**
     * URL a la que se envían las credenciales del usuario.
     */
    private $loginUrl = 'api/v1/accounts/login/';

    private $LOGIN_EXPERIMENTS = 'ig_android_updated_copy_user_lookup_failed,ig_android_hsite_prefill_new_carrier,ig_android_me_profile_prefill_in_reg,ig_android_allow_phone_reg_selectable,ig_android_gmail_oauth_in_reg,ig_android_run_account_nux_on_server_cue_device,ig_android_universal_instagram_deep_links_universe,ig_android_make_sure_next_button_is_visible_in_reg,ig_android_report_nux_completed_device,ig_android_sim_info_upload,ig_android_reg_omnibox,ig_android_background_phone_confirmation_v2,ig_android_background_voice_phone_confirmation,ig_android_password_toggle_on_login_universe_v2,ig_android_skip_signup_from_one_tap_if_no_fb_sso,ig_android_refresh_onetap_nonce,ig_android_multi_tap_login,ig_android_onetaplogin_login_upsell,ig_android_jp_sms_code_extraction_fix, ig_challenge_kill_switch,ig_android_modularized_nux_universe_device,ig_android_run_device_verification,ig_android_remove_sms_password_reset_deep_link,ig_android_phone_id_email_prefill_in_reg,ig_android_typeahead_bug_fixes_universe,ig_restore_focus_on_reg_textbox_universe,ig_android_abandoned_reg_flow,ig_android_phoneid_sync_interval,ig_android_2fac_auto_fill_sms_universe,ig_android_family_apps_user_values_provider_universe,ig_android_security_intent_switchoff,ig_android_enter_to_login,ig_android_show_password_in_reg_universe,ig_android_access_redesign,ig_android_remove_icons_in_reg_v2,ig_android_ui_cleanup_in_reg_v2,ig_android_login_bad_password_autologin_universe,ig_android_editable_username_in_reg';

    /**
     * Cabeceras de las peticiones HTTP que no varían
     */
    private $HEADERS = [
        'Connection' => 'Keep-Alive',
        'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-FB-HTTP-Engine' => 'Liger',
        'Accept-Encoding' => 'gzip,deflate',
        'Accept-Language' => 'en-US',
        'X-IG-App-ID' => '567067343352427',
        'X-IG-Capabilities' => '3brTBw==',
        'X-IG-Connection-Type' => 'WIFI',
        'X-IG-Connection-Speed' => '2014kbps',
        'X-IG-Bandwidth-Speed-KBPS' => '-1.000',
        'X-IG-Bandwidth-TotalBytes-B' => '0',
        'X-IG-Bandwidth-TotalTime-MS' => '0',
    ];

    /**
     * Esto puede ser dinámico o variar de tiempo en tiempo.
     * Dejada como pública para hacer pruebas de acceso a miembros
     * de la clase cuando se esté creando una instancia.
     */
    public $userAgent = 'Instagram 27.0.0.7.97 ' .
        'Android (24/7.0; 640dpi; 1440x2560; ' .
        'HUAWEI; LON-L29; HWLON; hi3660; en_US)';

    /**
     * Inicia sesión en tres pasos nada mas.
     */
    public function three_steps($username = null, $password = null) {
        try {
            if (\is_null($username) && \is_null($password)){
                $credentials = $this->getCredentials();
            }
            else {
                $credentials = [
                    'username' => $username,
                    'password' => $password
                ];
            }
            $retData = $this->initialLoginData();
            $uuid = $retData['uuid'];
            $cookies = $retData['cookies'];
            $retData = $this->syncDevice($uuid, $cookies);
            $cookiesArray = $retData['cookies']->toArray();
            $csrf_token = $this->getToken($cookiesArray);
            $retData = $this->postLoginData($credentials, $uuid, $csrf_token, $cookies);
            return $this->returnSuccess(true, [ 'cookies' => $retData['cookies']->toArray() ]);
        } catch (\Exception $ex) {
            return $this->returnError($ex->getMessage());
        }
    }

    /**
     * Simula el inicio de sesión como si fuera la aplicacion
     * de Android. Luego de loguearse chequea los ultimos cambios
     * del timeline del perfil.
     */
    public function guzzle($username = null, $password = null) {
        try {
            if (\is_null($username) && \is_null($password)){
                $credentials = $this->getCredentials();
            }
            else {
                $credentials = [
                    'username' => $username,
                    'password' => $password
                ];
            }

            $retData = $this->initialLoginData();
            
            $uuid = $retData['uuid'];
            $cookies = $retData['cookies'];
            $cookiesArray = $retData['cookies']->toArray();
            $csrf_token = $this->getToken($cookiesArray);

            $retData = array_merge($retData,
                $this->contactPointPrefill($uuid, $csrf_token, $cookies));
            $retData = array_merge($retData,
                $this->syncDevice($uuid, $cookies));
            $retData = array_merge($retData,
                $this->logAttribution($cookies));
            $retData = array_merge($retData,
                $this->postLoginData($credentials, $uuid, $csrf_token, $cookies));
            $retData = array_merge($retData,
                $this->getTimelineFeed($uuid, $csrf_token, $uuid, $cookies));

            return $this->returnSuccess(true, [
                'body' => $retData['body'],
                'cookies'=> $retData['cookies']->toArray(),
                'http_code' => $retData['http_code']
            ]);

        } catch (\Exception $ex) {
            return $this->returnError($ex->getMessage());
        }
    }

    /**
     * Inicia sesión usando solo CURL.
     * @return string JSON con el estado del timeline de Instagram.
     */
    public function curl($username = null, $password = null) {
        try {
            if (\is_null($username) && \is_null($password)){
                $credentials = $this->getCredentials();
            }
            else {
                $credentials = [
                    'username' => $username,
                    'password' => $password
                ];
            }

            $retData = $this->curlMsisdnHeader();

            $csrf_token = $this->tokenFromFile($retData['cookies']);
            $retData['csrftoken'] = $csrf_token;

            $retData = array_merge($retData, $this->curlPostLoginData($credentials, $retData['uuid'],
                $csrf_token, $retData['cookies_handle'], $retData['cookies']));

            $retData = array_merge($retData, $this->curlTimelineFeed($retData['uuid'],
                $csrf_token, $retData['uuid'], $retData['cookies_handle'],
                $retData['cookies']));

            $retData = array_merge($retData, [
                'cookies' => $this->cookiesFromFile($retData['cookies']),
                'ci_session' => $this->session->session_id
            ]);

            return json_encode($retData);

        } catch(\Exception $ex) {
            return $this->returnError($ex->getMessage());
        }
    }

    private function returnSuccess($textOrBool, $more = []) {
        $data = array_merge($more, [
            'success' => $textOrBool,
        ]);
        return json_encode($data);
    }

    private function returnError($text, $more = []) {
        $data = array_merge($more, [
            'error' => $text,
        ]);
        return json_encode($data);
    }

    private function cookiesFromFile($cookies_file) {
        $handle = fopen($cookies_file, "r");
        $cookies = [];
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (trim($line)==='' || preg_match('/^#/', $line) === 1) continue;
                $parts = preg_split("/\s+/", $line);
                $cookies[] = [
                    'name' => $parts[5],
                    'domain' => $parts[0],
                    'path' => $parts[2],
                    'value' => $parts[6],
                    'expire' => $parts[4],
                ];
            }
            fclose($handle);
        } else {
            echo 'error';
        }
        return $cookies;
    }

    private function curlTimelineFeed($uuid, $csrf_token, $phone_id,
        $cookies_handle, $cookies)
    {
        $data = [
            '_csrftoken' => $csrf_token,
            '_uuid' => $uuid,
            'is_prefetch' => '0',
            'phone_id' => $phone_id,
            'battery_level' => (string) (int) mt_rand(70, 100),
            'is_charging' => '1',
            'will_sound_on' => '1',
            'is_on_screen' => 'true',
            'timezone_offset' => date('Z'),
            'is_async_ads' => '0',
            'is_async_ads_double_request' => '0',
            'is_async_ads_rti' => '0'
        ];
        $signedData = $this->signedData($data);
        $headers = array_merge([
            'User-Agent' => $this->userAgent,
            'X-DEVICE-ID' => $uuid,
            'X-Ads-Opt-Out' => '0',
            'X-Google-AD-ID' => '0',
        ], $this->HEADERS);
        $ret = $this->curlRequest($this->timeLineFeedUrl, $headers, true,
            $signedData, $this->userAgent, $cookies_handle, $cookies);
        return $ret;
    }

    private function tokenFromFile($cookies_file) {
        $cookiesData = json_decode($this->parseCookiesFile($cookies_file), true);
        $csrf_token = array_reduce($cookiesData, function($acum, $cookie) {
            if ($cookie['name'] === 'csrftoken') {
                $acum = $cookie['value'];
            }
            return $acum;
        }, '');
        return $csrf_token;
    }

    private function curlMsisdnHeader() {
        $uuid = $this->generateUUID();
        $data = [
            "_csrftoken" => null,
            "device_id" => $uuid,
        ];
        $headers = $this->HEADERS;
        $signedData = $this->signedData($data);
        $ret = $this->curlRequest($this->initialDataUrl, $headers, true,
            $signedData, $this->userAgent);
        $ret['uuid'] = $uuid;
        return $ret;
    }

    private function curlSyncDevice($uuid, $cookies_handle, $cookies) {
        $data = [
            'id' => $uuid,
            'device_id' => $uuid,
            'experiments' => $this->LOGIN_EXPERIMENTS,
        ];
        $signedData = $this->signedData($data);
        $headers = $this->HEADERS;
        $signedData = $this->signedData($data);
        $ret = $this->curlRequest($this->syncDeviceUrl, $headers, true,
            $signedData, $this->userAgent, $cookies_handle, $cookies);
        return $ret;
    }

    private function curlPostLoginData($credentials, $uuid, $csrf_token, $cookies_handle, $cookies) {
        $data = [
            'phone_id' => $uuid,
            '_csrftoken' => $csrf_token,
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'device_id' => $this->deviceId(),
            'login_attempt_count' => '0',
            'adid' => $this->generateUUID(),
            'guid' => $this->generateUUID(),
        ];
        $signedData = $this->signedData($data);
        $headers = array_merge([
            'User-Agent' => $this->userAgent,
        ], $this->HEADERS);
        $ret = $this->curlRequest($this->loginUrl, $headers, true,
            $signedData, $this->userAgent, $cookies_handle, $cookies);
        $ret['uuid'] = $uuid;
        return $ret;
    }

    private function parseCookiesFile($cookies_file) {
        $awk_script = __DIR__ . '/../script/parse_cookies.awk';
        $cmd = "awk -v c=0 -f $awk_script $cookies_file";
        return trim(shell_exec($cmd));
    }

    private function getToken(array $cookies) {
        $_cookies = array_filter($cookies, function($cookie) {
            return $cookie['Name'] === 'csrftoken';
        });
        $cookie = current($_cookies);
        return $cookie['Value'];
    }

    /**
     * Obtiene las credenciales de inicio de sesión ya sea
     * que se hayan enviado por POST o por GET.
     */
    private function getCredentials() {
        $username = in_array('username', array_keys($_REQUEST)) ?
            $_REQUEST['username'] : null;
        $password = in_array('password', array_keys($_REQUEST)) ?
            $_REQUEST['password'] : null;
        if ($username === null || $password === null) {
            $array = json_decode(file_get_contents('php://input'), true);
            $username = trim($array['username']) === '' ? null : $array['username'];
            $password = trim($array['password']) === '' ? null : $array['password'];
        }
        return [
            'username' => $username,
            'password' => $password
        ];
    }

    /**
     * Esto fue tomado de la API de GitHub. Genera un identificador
     * único necesario como parámetro en algunas peticiones que se
     * hacen.
     */
    private function generateUUID() {
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
        return $uuid;
    }

    private function deviceId() {
        $megaRandomHash = md5(number_format(microtime(true), 7, '', ''));
        return 'android-'.substr($megaRandomHash, 16);
    }

    private function getGuzzleClient($_options = []) {
        $options = [
            'base_uri' => $this->baseUri
        ];
        if ($this->withProxy) {
            $options = array_merge($options, [
                'proxy' => $this->proxies
            ]);
        }
        $options = array_merge($options, $_options);
        $client = new \GuzzleHttp\Client($options);
        return $client;
    }

    /**
     * @return mixed initialData
     */
    private function initialLoginData() {
        $jar = new \GuzzleHttp\Cookie\CookieJar;
        $uuid = $this->generateUUID();
        $data = [
            "_csrftoken" => null,
            "device_id" => $uuid,
        ];
        $client = $this->getGuzzleClient([
            'cookies' => $jar,
            'base_uri' => $this->baseUri
        ]);
        $signedData = $this->signedData($data);
        $response = $client->post($this->initialDataUrl, [
            'debug' => $this->debugRequest,
            'body' => $signedData,
            'headers' => array_merge([
                'User-Agent' => $this->userAgent,
            ], $this->HEADERS)
        ]);
        $body = $response->getBody();
        return [
            'body' => $body, 'cookies' => $jar,
            'uuid' => $uuid, 'http_code' => $response->getStatusCode()
        ];
    }

    private function contactPointPrefill($uuid, $csrf_token, $cookies) {
        $data = [
            'phone_id' => $uuid,
            '_csrftoken' => $csrf_token,
            'usage' => 'prefill',
        ];
        $client = $this->getGuzzleClient([
            'cookies' => $cookies,
        ]);
        $signedData = $this->signedData($data);
        $response = $client->post($this->contactPointUrl, [
            'debug' => $this->debugRequest,
            'body' => $signedData,
            'headers' => array_merge([
                'User-Agent' => $this->userAgent,
            ], $this->HEADERS)
        ]);
        $body = $response->getBody();
        return [
            'body' => $body, 'cookies' => $cookies,
            'http_code' => $response->getStatusCode()
        ];
    }

    /**
     * @return mixed initialData
     */
    private function syncDevice($uuid, $cookies) {
        $data = [
            'id' => $uuid,
            'device_id' => $uuid,
            'experiments' => $this->LOGIN_EXPERIMENTS,
        ];
        $client = $this->getGuzzleClient([
            'cookies' => $cookies,
        ]);
        $signedData = $this->signedData($data);
        $response = $client->post('api/v1/qe/sync/', [
            'debug' => $this->debugRequest,
            'body' => $signedData,
            'headers' => array_merge([
                'User-Agent' => $this->userAgent,
            ], $this->HEADERS)
        ]);
        $body = $response->getBody();
        return [
            'body' => $body, 'cookies' => $cookies,
            'http_code' => $response->getStatusCode()
        ];
    }

    private function logAttribution($cookies) {
        $data = [
            'adid' => $this->generateUUID(),
        ];
        $client = $this->getGuzzleClient([
            'cookies' => $cookies,
        ]);
        $signedData = $this->signedData($data);
        $response = $client->post($this->logAttributionUrl, [
            'debug' => $this->debugRequest,
            'body' => $signedData,
            'headers' => array_merge([
                'User-Agent' => $this->userAgent,
            ], $this->HEADERS)
        ]);
        $body = $response->getBody();
        return [
            'body' => $body, 'cookies' => $cookies,
            'http_code' => $response->getStatusCode()
        ];
    }

    /**
     * @return mixed successOrFailureLoginData
     */
    private function postLoginData($credentials, $uuid, $csrf_token, $cookies) {
        $data = [
            'phone_id' => $uuid,
            '_csrftoken' => $csrf_token,
            'username' => $credentials['username'],
            'password' => $credentials['password'],
            'device_id' => $this->deviceId(),
            'login_attempt_count' => '0',
            'adid' => $this->generateUUID(),
            'guid' => $this->generateUUID(),
        ];
        $client = $this->getGuzzleClient([
            'cookies' => $cookies,
        ]);
        $signedData = $this->signedData($data);
        $response = $client->post($this->loginUrl, [
            'debug' => $this->debugRequest,
            'body' => $signedData,
            'headers' => array_merge([
                'User-Agent' => $this->userAgent,
            ], $this->HEADERS)
        ]);
        $body = $response->getBody();
        return [
            'http_code' => $response->getStatusCode(),
            'body' => $body, 'cookies' => $cookies,
        ];
    }

    private function getTimelineFeed($uuid, $csrf_token, $phone_id, $cookies) {
        $data = [
            '_csrftoken' => $csrf_token,
            '_uuid' => $uuid,
            'is_prefetch' => '0',
            'phone_id' => $phone_id,
            'battery_level' => (string) (int) mt_rand(70, 100),
            'is_charging' => '1',
            'will_sound_on' => '1',
            'is_on_screen' => 'true',
            'timezone_offset' => date('Z'),
            'is_async_ads' => '0',
            'is_async_ads_double_request' => '0',
            'is_async_ads_rti' => '0'
        ];
        $client = $this->getGuzzleClient([
            'cookies' => $cookies,
        ]);
        $signedData = $this->signedData($data);
        $response = $client->post($this->timeLineFeedUrl, [
            'debug' => $this->debugRequest,
            'body' => $signedData,
            'headers' => array_merge([
                'User-Agent' => $this->userAgent,
                'X-DEVICE-ID' => $uuid,
                'X-Ads-Opt-Out' => '0',
                'X-Google-AD-ID' => '0',
            ], $this->HEADERS)
        ]);
        $body = $response->getBody()->getContents();
        return [
            'http_code' => $response->getStatusCode(),
            'body' => $body, 'cookies' => $cookies,
        ];
    }

    private function generateSignature($data) {
        return hash_hmac('sha256', $data,
            // Constante tomada de la API de Constants.php
            '109513c04303341a7daf27bb41b268e633b30dcc65a3fe14503f743176113869');
    }

    /**
     * Tomado de la API en Utils.php.
     */
    private function hashCode(
        $string)
    {
        $result = 0;
        for ($i = 0, $len = strlen($string); $i < $len; ++$i) {
            $result = (-$result + ($result << 5) + ord($string[$i])) & 0xFFFFFFFF;
        }
        if (PHP_INT_SIZE > 4) {
            if ($result > 0x7FFFFFFF) {
                $result -= 0x100000000;
            } elseif ($result < -0x80000000) {
                $result += 0x100000000;
            }
        }

        return $result;
    }

    /**
     * Tomado de la API en Utils.php.
     */
    private function reorderByHashCode(
        array $data)
    {
        $hashCodes = [];
        foreach ($data as $key => $value) {
            $hashCodes[$key] = $this->hashCode($key);
        }

        uksort($data, function ($a, $b) use ($hashCodes) {
            $a = $hashCodes[$a];
            $b = $hashCodes[$b];
            if ($a < $b) {
                return -1;
            } elseif ($a > $b) {
                return 1;
            } else {
                return 0;
            }
        });

        return $data;
    }

    /**
     * Tomado de la API en Signatures.php.
     */
    private function signData(
        array $data,
        array $exclude = [])
    {
        $result = [];
        // Exclude some params from signed body.
        foreach ($exclude as $key) {
            if (isset($data[$key])) {
                $result[$key] = $data[$key];
                unset($data[$key]);
            }
        }
        // Typecast all scalar values to string.
        foreach ($data as &$value) {
            if (is_scalar($value)) {
                $value = (string) $value;
            }
        }
        unset($value); // Clear reference.
        $data = json_encode($this->reorderByHashCode($data));
        // Este valor es de una constante de la API: Constants::SIG_KEY_VERSION
        $result['ig_sig_key_version'] = 4;
        $result['signed_body'] = $this->generateSignature($data).'.'.$data;
        // Return value must be reordered.
        return $this->reorderByHashCode($result);
    }

    /**
     * Firma los datos según el formato que espera la API
     * de Instagram. Esto fue tomado de la API de GitHub.
     */
    private function signedData($data) {
        //if(true){ var_dump($data); die(); }
        $signed = $this->signData($data);
        $result = sprintf("signed_body=%s&ig_sig_key_version=%s",
            $signed['signed_body'], $signed['ig_sig_key_version']);
        return $result;
    }

    /**
     * Hace una petición CURL a la API a partir de la URI base
     * definida en $this->baseUri.
     * 
     * @param url String con la URL a la que se hará la petición.
     * @param headers Arreglo con las cabeceras que se enviarán en la petición.
     * @param post Verdadero si la petición será por POST, falso si será por GET.
     * @param user_agent String con el agente de usuario que se simulará como autor
     * de las peticiones.
     * @param cookies_handle Número que identifica el archivo único que se encuentra
     * en /tmp conteniendo las cookies de peticiones anteriores para así continuar
     * con el estado de la sesión previamente iniciada. Si es cero, entonces esta
     * es la primera petición que se hace.
     * @param cookies_file String con el nombre del archivo que contiene las cookies
     * creadas en peticiones anteriores.
     */
    private function curlRequest($url, $headers, $post, $post_data,
        $user_agent, $cookies_handle = 0, $cookies_file = null)
    {
        if ($cookies !== null) {
            $headers['Cookie'] = $cookies;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUri . "/$url");
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($post) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        $t = $cookies_handle === 0 ? date("U") : $cookies_handle;
        $cookies_file = "/tmp/cookies.$t.txt";
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookies_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookies_file);

        $response = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'http_code' => $http,
            'response' => (string) $response,
            'cookies' => file_exists($cookies_file) ? $cookies_file : null,
            'cookies_handle' => $t,
            'error' => $error,
        ];
    }
}
