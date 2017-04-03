<?php

namespace Techscope\Finicity;

use Carbon\Carbon;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Client;
use GuzzleHttp\Post\PostBodyInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Crypt;
use Psr\Http\Message\ResponseInterface;


/**
 * Class Laraficity
 * @package Techscope\Finicity
 */
class Finicity
{
    protected $app_key;
    protected $partner_id;
    protected $partner_secret;
    protected $base_url;
    protected $http_accept;
    protected $http_content_type;
    protected $guzzle;
    protected $token_timeout; // how many minutes the token is active before it expires
    protected $token_file;
    protected $token_remaining_time_allotment; // what is the minimum amount of remaining time to allow before we retrieve a new token
    protected $token;
    protected $debug;
    protected $default_request_options;

    public function __construct()
    {
        // Set the configuration values
        $this->app_key = config('laraficity.app_key');
        $this->partner_id = config('laraficity.partner_id');
        $this->partner_secret = config('laraficity.partner_secret');
        $this->base_url = config('laraficity.base_url');
        $this->guzzle = new Guzzle(['base_uri' => $this->base_url]);
        $this->http_accept = 'application/' . config('laraficity.http_headers.accept');
        $this->http_content_type = 'application/' . config('laraficity.http_headers.content_type');
        $this->token_file = storage_path('app/laraficity.txt'); // TODO: Use config to set this value
        $this->token_timeout = 200; // TODO: Use config to set this value
        $this->token_remaining_time_allotment = 10; // TODO: Use config to set this value
        $this->debug = false; // TODO: Use config to set this value

        // Check to see if there is a valid session that we can use (this would be any session that has at least
        // 10 minutes remaining. The active token will also be set.
        $token = $this->getGlobalToken();
        if($token === false) { // meaning there is either no
            $new_token = $this->getToken();
            $this->setGlobalToken($new_token);
            $this->token = $new_token;
        } else {
            $this->token = $token;
        }

        // Set default guzzle request options
        $this->default_request_options = [
            'debug' => $this->debug,
            'headers' => [
                'Finicity-App-Key' => $this->app_key,
                'Finicity-App-Token' => $this->app_key,
                'Content-Type' => $this->http_content_type,
                'Accept' => $this->http_accept
            ],
            'protocols' => [
                'https'
            ]
        ];
    }

    /**
     * @param \SimpleXMLElement $xml
     * @return mixed
     *
     * Fincity API cannot have the XML declaration as part of the XML body. This method removes the XML declaration
     * from the SimpleXML Object and returns the XML as a string so that it can be used in the body of the API request
     */
    protected function getXmlStringWithoutDeclartion(\SimpleXMLElement $xml)
    {
        $string = $xml->asXML();
        $no_declaration = str_replace("<?xml version=\"1.0\"?>\n", '', $string);
        return $no_declaration;
    }

    /**
     * @param ResponseInterface $response
     * @return mixed
     *
     * Takes the XML response of the Finicity API call and returns the response as an array.
     */
    protected function getResponseAsArray(ResponseInterface $response)
    {
        $xml_string = (string) $response->getBody();
        $xml = simplexml_load_string($xml_string, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json,TRUE);
        return $array;
    }

    /**
     * @param $token
     *
     * Stores the token (as an encrypted string) and the datetime the token was received in file. The purpose of this
     * method is to prevent a new token from being issued for every single API call. The same token is reused for
     * the duration of it's lifetime.
     */
    public function setGlobalToken($token)
    {
        $current_time = Carbon::now()->toDateTimeString();
        $encrypted_token = Crypt::encrypt($token);

        $token_data = [
            "token_time" => $current_time,
            "token" => $encrypted_token
        ];

        $serialized_data = json_encode($token_data);
        $token_file = storage_path('app/laraficity.txt');
        file_put_contents($token_file, $serialized_data);
    }

    /**
     * @return bool|mixed
     *
     * First, checks to see if a usable token is still available. If the a usable token is available, it returns the
     * token
     */
    public function getGlobalToken()
    {
        if(file_exists($this->token_file) === false) {
            touch($this->token_file);
        }

        $serialized_data = file_get_contents($this->token_file);

        // Return false if the global token data is not in the proper format
        try {
            $token_data = json_decode($serialized_data, TRUE);
        } catch (\InvalidArgumentException $e) {
            // If the data in the file is not a valid JSON string then return false
            return false;
        }

        // return false if the token file is blank
        if(count($token_data) == 0) {
            return false;
        }

        // Also return false of the has only 10 minutes left
        $time_to_beat = $this->token_timeout - $this->token_remaining_time_allotment;
        $current_time = Carbon::now();
        $token_birth = Carbon::parse($token_data['token_time']);
        $token_age = $current_time->diffInMinutes($token_birth);
        if($token_age > $time_to_beat) {
            return false;
        }

        return Crypt::decrypt($token_data['token']);
    }

    /**
     * @return mixed
     * @throws \Exception
     *
     * Gets a session token from the Finicity API returns it as a string. This method is the same call as the
     * "Partner Authentication" operation in the Fincity API documentation.
     */
    public function getToken()
    {
        $xml = new \SimpleXMLElement('<credentials></credentials>');
        $xml->addChild('partnerId', $this->partner_id);
        $xml->addChild('partnerSecret', $this->partner_secret);
        $xml_string = $this->getXmlStringWithoutDeclartion($xml);

        $guzzle = new Client(['base_uri' => $this->base_url]);

        $response = $guzzle->request('POST', 'partners/authentication', [
            'debug' => $this->debug,
            'headers' => [
                'Finicity-App-Key' => $this->app_key,
                'Content-Type' => $this->http_content_type,
                'Accept' => $this->http_accept
            ],
            'body' => $xml_string,
            'protocols' => [
                'https'
            ]
        ]);

        $response_object = $this->getResponseAsArray($response);

        try {
            $token = $response_object['token'];
            return $token;
        } catch(\Exception $e) {
            throw new \Exception("No token was returned from the server");
        }
    }

    public function appendHeaderOption($key, $value)
    {

    }

    public function setHeaderOption($key, $value)
    {

    }

    public function removeHeaderOption($key, $value)
    {

    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    public static function getArray($array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return value($default);
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $value
     * @return array
     */
    public static function setArray(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * @return void
     */
    public static function forgetArray(&$array, $keys)
    {
        $original = &$array;

        $keys = (array) $keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            $parts = explode('.', $key);

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    $parts = [];
                }
            }

            unset($array[array_shift($parts)]);

            // clean up after each pass
            $array = &$original;
        }
    }

    // TODO: need some type of append to array function for the non-multidimensional array like protocols
    public function getInstitutions($search = '*', $start = 1, $limit = 25)
    {
        $options = $this->default_request_options;
        self::forgetArray($options, 'headers.Content-Type');
        self::setArray($options, 'query.search', $search);
        self::setArray($options, 'query.start', $start);
        self::setArray($options, 'query.limit', $limit);

        $request = $this->guzzle->request('GET', $options);


        return $options;
    }
}
