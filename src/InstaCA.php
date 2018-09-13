<?php

namespace InstaCA;

use \InstagramAPI\Signatures;
use \GuzzleHttp\Cookie\SetCookie;
use \GuzzleHttp\Cookie\CookieJar;

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
        'Host' => 'i.instagram.com',
        'Connection' => 'Keep-Alive',
        'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-FB-HTTP-Engine' => 'Liger',
        'Accept-Encoding' => 'gzip,deflate',
        'Accept-Language' => 'en-US',
        'X-IG-App-ID' => '567067343352427',
        'X-IG-Capabilities' => '3brTBw==',
        'X-IG-Connection-Type' => 'WIFI',
        'X-IG-Bandwidth-Speed-KBPS' => '-1.000',
        'X-IG-Bandwidth-TotalBytes-B' => '0',
        'X-IG-Bandwidth-TotalTime-MS' => '0',
    ];

    private $username = null;

    private $password = null;

    public $client = null;

    /**
     * Esto puede ser dinámico o variar de tiempo en tiempo.
     * Dejada como pública para hacer pruebas de acceso a miembros
     * de la clase cuando se esté creando una instancia.
     */
    public $userAgent = 'Instagram 27.0.0.7.97 ' .
        'Android (24/7.0; 640dpi; 1440x2560; ' .
        'HUAWEI; LON-L29; HWLON; hi3660; en_US)';

    /**
     * Simula el inicio de sesión como si fuera la aplicacion de Android,
     * pero sólo en dos pasos. En el primer paso (initialLoginData),
     * se obtiene el "csrftoken". En el segundo paso (syncDevice), se establece
     * que es un dispositivo Android quien está intentando iniciar sesión.
     * Si este segundo paso se obvia, los servidores devuelven un error
     * informando que se está usando una versión muy vieja de la app.
     * Por lo que es requerida la sincronización de dispositivo segundo paso).
     * Luego, se envían las credenciales de la cuenta de usuario. Si los
     * parámetros de usuario y contraseña fueran nulos (por defecto), se
     * intentarán obtener del POST o del GET de la petición. Si no se obtienen
     * de la petición, se buscará en los parámetros de línea de comando.
     * Si no se resuelven allí, entonces se emite una excepción.
     * 
     * @param username (string) Nombre del usuario.
     * @param password (string) Contraseña del usuario.
     * @return cookies (array) Arreglo que incluye las cookies de la sesión
     * iniciada. Con estas cookies se pueden seguir haciendo peticiones a
     * las distintas URLs que devuelven información, hipoteticamente, durante
     * varios meses.
     */
    public function login($username = null, $password = null) {
        try {
            if (\is_null($username) && \is_null($password)){
                throw new \Exception('You must supply the right credentials (username/password)', 500);
            }
            $this->username = $username;
            $this->password = $password;

            $initialRequestResult = $this->initialLoginData();
            $syncDeviceRequestResult = $this->syncDevice($initialRequestResult['uuid'],
                $initialRequestResult['cookies']);
            $loginResponse = $this->postLoginData($initialRequestResult['uuid'],
                $this->client->getConfig('cookies')->getCookieByName('csrftoken')->getValue(),
                $this->client->getConfig('cookies'));
            $sessionCookies = $this->withAdditionalCookies($loginResponse['cookies']);
            
            return [
                'body' => $loginResponse['body'],
                'cookies'=> $sessionCookies,
                'http_code' => $loginResponse['http_code']
            ];
        } catch (\Exception $loginEx) {
            $err = sprintf("Unable to log the user %s. CAUSE: %s",
                $this->username, $loginEx->getMessage());
            throw new \Exception($err, 500);
        }
    }

    /**
     * Devuelve o crea el cliente Guzzle para hacer peticiones a los
     * servidores remotos. Si se invoca por vez primera este método
     * entonces se crea la instancia. Si ya está creada, se devuelve.
     * 
     * @param _options (array) Opciones adicionales que se le pasarán
     * al cliente Guzzle que se ha de crear. Si ya se creó, entonces
     * es innecesario pasar parámetros, y se devolverá la instancia
     * previamente creada.
     */
    public function getGuzzleClient($_options = []) {
        if ($this->client !== null) return $this->client;
        $jar = new \GuzzleHttp\Cookie\CookieJar;
        $options = [
            'base_uri' => $this->baseUri,
            'cookies' => $jar,
        ];
        $optionsCombined = array_merge($options, $_options,
            $this->withProxy ? [ 'proxy' => $this->proxies ] : []);
        $client = new \GuzzleHttp\Client($optionsCombined);
        $this->client = $client;
        return $client;
    }

    public function setProxy($proxies) {
        $this->withProxy = true;
        $this->proxies = $proxies;
    }

    private function getUserAgent() {
        return $this->userAgent;
    }

    public function getHeaders() {
        return array_merge($this->HEADERS, [
            'User-Agent' => $this->getUserAgent(),
            'X-IG-Connection-Speed' => sprintf("%skbps", (int) mt_rand(750, 2048)),
        ]);
    }

    /**
     * Obtiene las credenciales de inicio de sesión ya sea
     * que se hayan enviado por POST o por GET. Si no están
     * allí, se intentará tomarlas desde la línea de comandos
     * porque pudiera ser que este script se invocó desde
     * consola o mediante otra aplicación que no es el servidor
     * web.
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
        if ($username === null || $password === null) {
            $username = trim($argv[1]);
            $password = trim($argv[2]);
        }
        if ($username === null || $password === null) {
            throw new \Exception("Invalid username/password", 500);
        }
        $this->username = $username;
        $this->password = $password;
        return [
            'username' => $username,
            'password' => $password
        ];
    }

    /**
     * Hace la petición inicial a los servidores remotos,
     * para lograr que en las cookies sea situado el parámetro
     * "csrftoken", necesario para las subsiguientes peticiones.
     * 
     * @return data (array) Un arreglo del que lo que más interesa
     * es la clave 'cookies' porque contiene una referencia al
     * objeto CookieJar donde se van almacenando las cookies con
     * que se construirá al final la sesión que permitirá hacer
     * peticiones posteriores sin tener que volver a autenticar.
     */
    private function initialLoginData() {
        $uuid = Signatures::generateUUID();
        $signedData = Signatures::signData([
            "_csrftoken" => 'null',
            "device_id" => $uuid,
        ]);
        $initialRequestBody = $this->composeRequestBody($signedData);
        try {
            $initialDataResponse = $this->getGuzzleClient()->post($this->initialDataUrl, [
                'debug' => $this->debugRequest,
                'body' => $initialRequestBody,
                'headers' => $this->getHeaders(),
            ]);
        } catch(\Exception $initEx) {
            $err = sprintf("Unable to establish initial login data connection. CAUSE: %s",
                $initEx->getMessage());
            throw new \Exception($err, 500);
        }
        $body = $initialDataResponse->getBody();
        return [
            'body' => $body, 'cookies' => $this->client->getConfig('cookies'),
            'uuid' => $uuid, 'http_code' => $initialDataResponse->getStatusCode()
        ];
    }

    /**
     * Convierte los datos firmados por la API original, al formato
     * que debe tener el cuerpo de la petición que se envía a los
     * servidores remotos.
     * 
     * @param signedData (array) Arreglo que contiene los datos firmados.
     * Este arreglo se compone de dos valores: (1) "signed_body" contiene
     * un hash calculado a partir de todos los datos pasados en un arreglo
     * al método \InstagramAPI\Signatures::signData de la API original, más
     * un JSON con los datos de dicho arreglo pasado al método de la API.
     * (2) "ig_sig_key_version", que es la versión de firmas que se está
     * usando actualmente según lo establecen los servidores.
     */
    private function composeRequestBody($signedData) {
        return sprintf("signed_body=%s&ig_sig_key_version=%s",
            $signedData['signed_body'], $signedData['ig_sig_key_version']);
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
     * Hace la segunda petición a los servidores remotos, donde
     * se envía un largo parámetro (experiments) que permite
     * informar a los servidores que el acceso se está haciendo
     * desde un dispositivo móvil.
     * 
     * @return data (array) Un arreglo del que lo que más interesa
     * es la clave 'cookies' porque contiene una referencia al
     * objeto CookieJar donde se van almacenando las cookies con
     * que se construirá al final la sesión que permitirá hacer
     * peticiones posteriores sin tener que volver a autenticar.
     */
    private function syncDevice($uuid, $cookies) {
        $signedData = Signatures::signData([
            'id' => $uuid,
            'device_id' => $uuid,
            'experiments' => $this->LOGIN_EXPERIMENTS,
        ]);
        $syncDeviceRequestBody = $this->composeRequestBody($signedData);
        try {
            $syncDeviceResponse = $this->client->post('api/v1/qe/sync/', [
                'debug' => $this->debugRequest,
                'body' => $syncDeviceRequestBody,
                'headers' => array_merge([
                    'User-Agent' => $this->userAgent,
                ], $this->getHeaders())
            ]);
        } catch(\Exception $syncEx) {
            $err = sprintf("Unable to sync device features. CAUSE: %s",
                $syncEx->getMessage());
            throw new \Exception($err, 500);
        }
        $body = $syncDeviceResponse->getBody();
        return [
            'body' => $body, 'cookies' => $this->client->getConfig('cookies'),
            'http_code' => $syncDeviceResponse->getStatusCode()
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
    private function postLoginData($uuid, $csrf_token, $cookies) {
        $signedData = Signatures::signData([
            'phone_id' => $uuid,
            '_csrftoken' => $csrf_token,
            'username' => $this->username,
            'password' => $this->password,
            'device_id' => Signatures::generateDeviceId(),
            'login_attempt_count' => '0',
            'adid' => Signatures::generateUUID(),
            'guid' => Signatures::generateUUID(),
        ]);
        $loginRequestBody = $this->composeRequestBody($signedData);
        try {
            $loginResponse = $this->client->post($this->loginUrl, [
                'debug' => $this->debugRequest,
                'body' => $loginRequestBody,
                'headers' => array_merge([
                    'User-Agent' => $this->userAgent,
                ], $this->getHeaders())
            ]);
        } catch (\Exception $loginEx) {
            $err = sprintf("Unable to post user %s credentials. CAUSE: %s",
                $this->username, $loginEx->getMessage());
            throw new \Exception($err, 500);
        }
        $body = $loginResponse->getBody();
        return [
            'body' => $body,
            'cookies' => $this->client->getConfig('cookies'),
            'http_code' => $loginResponse->getStatusCode(),
        ];
    }

    /**
     * Agrega a las cookies de las peticiones que se han hecho, tres
     * cookies que permiten a los servidores remotos identificar si
     * ya la sesión se abrió anteriormente. Si estas cookies no están
     * presentes en peticiones posteriores, serán rechazadas con un
     * mensaje de "login_required".
     * 
     * @return data (array) Un arreglo que contiene las cookies
     * acumuladas a través de cada petición, más las requeridas para
     * las peticiones posteriores al inicio de sesión. Este arreglo
     * luego se puede serializar, convertir a JSON, o guardar de cualquier
     * otra forma, para reusarlo todas las veces que se quiera sin tener
     * que iniciar sesión de nuevo.
     */
    private function withAdditionalCookies(CookieJar $currentCookies) {
        $igflCookie = SetCookie::fromString('igfl=' . $this->username .
            '; Path=/; Max-Age=86400; ' .
            // Expira dos días antes. No se la razón, pero funciona bien así...
            'Expires=' . (int) (date('U') - (3600 * 24 * 2)) . '; Domain=i.instagram.com');
        $igDirectRegionCookie = SetCookie::fromString('ig_direct_region_hint=""; ' .
            'Expires=0; Max-Age=0; Path=/; Domain=i.instagram.com');
        $starredCookie = SetCookie::fromString('is_starred_enabled=yes; Max-Age=630720000; ' .
            // Expira muy, muy lejos en el  futuro. Tampoco conozco la razón...
            'Expires=' . (int) (date('U') - (3600 * 24 * 360 * 10)) . '; Path=/; Domain=i.instagram.com');
        $additionalCookies = [
            $igflCookie->toArray(),
            $igDirectRegionCookie->toArray(),
            $starredCookie->toArray(),
        ];
        $cookiesArray = array_merge($currentCookies->toArray(), $additionalCookies);
        return $cookiesArray;
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

}
