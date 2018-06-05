<?php

namespace InstaCA;

use \GuzzleHttp\Cookie\SetCookie;
use \GuzzleHttp\Cookie\CookieJar;
use \GuzzleHttp\Client;

class Location {

    private $cookies = [];

    private $client = null;

    private $insta = null;

    private $url = "https://i.instagram.com/api/v1/fbsearch/places/?timezone_offset=0" .
    "&count=%s&query=%s&exclude_list=[%s]&rank_token=%s";

    /**
     * @param cookies (array) Arreglo que debe contener las cookies devueltas
     * por la clase \InstaCA\InstaCA luego de haber iniciado sesión satisfactoriamente.
     */
    public function __construct($cookies = [])
    {
        $this->cookies = $cookies;
        $this->client = $this->getClient();
        $this->insta = new InstaCA();
    }

    /**
     * Crea el jarrón de cookies a partir de las cookies de la sesión
     * previamente abierta, que debieron pasarse a esta instancia a
     * través del constructor.
     * 
     * @return \GuzzleHttp\Cookie\CookieJar
     */
    private function getCookieJar() {
        $cookiesArray = array_reduce($this->cookies, function($acumArray, $cookie) {
            $acumArray[] = new SetCookie($cookie);
            return $acumArray;
        }, []);
        $jar = new CookieJar(false, $cookiesArray);
        return $jar;
    }

    /**
     * Crea el cliente Guzzle para hacer las peticiones al servidor.
     * Depende las cookies de la sesión previamente abierta, que
     * debieron pasarse a esta instancia a través del constructor.
     * 
     * @return \GuzzleHttp\Client
     */
    private function getClient() {
        $jar = $this->getCookieJar();
        $client = new Client([
            'cookies' => $jar,
        ]);
        return $client;
    }

    /**
     * Compone la url concatenando los parámetros requeridos para
     * la consulta que pedirá las geolocalizaciones.
     * 
     * @param location (string) Nombre o palabra clave que debe contener
     * cada geolocalización que de devolverá como resultado de la consulta.
     * @param count (int) Cantidad de resultados a devolver.
     * @param exclude_list (string) Una lista de identificadores separados
     * por coma, de todos los resultados que se desean excluir al paginar
     * los resultados. Estos identificadores son la propiedad facebook_places_id
     * de cada elemento devuelto en la consulta realizada.
     * @param rank_token (string) Identificador único generado a partir del
     * primer grupo de resultados devuelto por la búsqueda. Si se está paginando
     * o devolviendo más resultados, este parámetro es obligatorio suministrarlo
     * al servidor remoto en cada petición. En la primera petición, puede estar
     * vacío.
     */
    private function composeUri($location, $count, $exclude_list, $rank_token) {
        return sprintf($this->url, $count, $location, $exclude_list, $rank_token);
    }

    /**
     * Hace una petición a los servidores buscando geolocalizaciones que
     * contengan la palabra o el nombre correspondiente al parámetro
     * 'location'.
     * 
     * @return response (array) Un arreglo asociativo que contiene lo
     * siguiente: 'success' (bool) que indica si fue exitosa la petición;
     * 'cookies' (array) lista de cookies necesarias para ser enviadas
     * en cada petición al servidor, si no, se deniega el acceso requiriendo
     * que se inicie sesión en Instagram; 'ig' (array) que contiene los
     * datos devueltos por el servidor si la búsqueda no dio error. Este
     * último objeto 'ig' se compone de 'has_more', un bool que indica
     * que aún quedan objetos que corresponden a la palabra enviada en el
     * parámetro 'location'; 'rank_token', un string con el identificador
     * único generado por los servidores remotos; 'items', un arreglo de
     * objetos que es lo que más nos interesa, donde cada uno es una
     * geolocalización. En caso de un error, o si se llega al límite de
     * resultados permitidos, se devuelve false en el 'success', y la lista
     * de 'items' del índice 'ig', estará vacía. Además, 'has_more' será
     * falso, y el 'rank_token' vacío.
     * 
     * @param location (string) Nombre o palabra clave que debe contener
     * cada geolocalización que de devolverá como resultado de la consulta.
     * @param count (int) Cantidad de resultados a devolver.
     * @param exclude_list (string) Una lista de identificadores separados
     * por coma, de todos los resultados que se desean excluir al paginar
     * los resultados. Estos identificadores son la propiedad facebook_places_id
     * de cada elemento devuelto en la consulta realizada.
     * @param rank_token (string) Identificador único generado a partir del
     * primer grupo de resultados devuelto por la búsqueda. Si se está paginando
     * o devolviendo más resultados, este parámetro es obligatorio suministrarlo
     * al servidor remoto en cada petición. En la primera petición, puede estar
     * vacío.
     */
    public function search($location, $count = 10, $exclude_list = '', $rank_token = '') {
        try {
            $url = $this->composeUri($location, $count, $exclude_list, $rank_token);
            $locationResponse = $this->client->get($url, [
                'headers' => $this->insta->getHeaders(),
            ]);
            $data = json_decode((string) $locationResponse->getBody());
            $response = [
                'success' => true,
                'cookies' => $this->client->getConfig('cookies')->toArray(),
                'ig' => $data,
                'timestamp' => date('U'),
            ];
            return $response;
        } catch(\Exception $locSearchEx) {
            $message = sprintf('Unable to request location list matching %s. CAUSE: %s',
                $query, $locSearchEx->getMessage());
            $response = [
                'success' => false,
                'message' => $message,
                'cookies' => $this->client->getConfig('cookies')->toArray(),
                'ig' => [
                    'items' => [],
                    'rank_token' => '',
                    'has_more' => false
                ]
            ];
            return $response;
        }
    }
}
