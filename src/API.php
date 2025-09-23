<?php

namespace Tualo\Office\MSGraph;

use Tualo\Office\Basic\TualoApplication;
use Ramsey\Uuid\Uuid;
use GuzzleHttp\Client;
use Tualo\Office\Basic\TualoApplication as App;

class API
{

    private static $ENV = null;

    public static function addEnvrionment(string $id, string $val)
    {
        self::$ENV[$id] = $val;
        $db = TualoApplication::get('session')->getDB();
        try {
            if (!is_null($db)) {
                $db->direct('insert into msgraph_environments (id,val) values ({id},{val}) on duplicate key update val=values(val)', [
                    'id' => $id,
                    'val' => $val
                ]);
            }
        } catch (\Exception $e) {
        }
    }



    public static function replacer($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::replacer($value);
            }
            return $data;
        } else if (is_string($data)) {
            $env = self::getEnvironment();
            foreach ($env as $key => $value) {
                $data = str_replace('{{' . $key . '}}', $value, $data);
            }
            return $data;
        }
        return $data;
    }



    public static function getEnvironment(): array
    {
        if (is_null(self::$ENV)) {
            $db = TualoApplication::get('session')->getDB();
            try {

                $tenantId = $db->singleValue('select val from  msgraph_setup where id = "tenantId"', [], 'val');
                $tenantId = App::configuration('microsoft-mail', 'tenantId', $tenantId);
                $clientSecret = $db->singleValue('select val from  msgraph_setup where id = "clientSecret"', [], 'val');
                if (!$tenantId) {
                    throw new \Exception('no setup found!');
                }

                if (!is_null($db)) {
                    $data = $db->direct('select id,val from msgraph_environments');
                    if (count($data) == 0) {
                        throw new \Exception('no setup');
                    }
                    foreach ($data as $d) {
                        self::$ENV[$d['id']] = $d['val'];
                    }
                } else {
                    throw new \Exception('Database not found!');
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return self::$ENV;
    }

    public static function env($key)
    {
        $env = self::getEnvironment();
        if (isset($env[$key])) {
            return $env[$key];
        }
        throw new \Exception('Environment ' . $key . ' not found!');
    }


    private static function getClient()
    {
        $client = new Client(
            [
                'base_uri' => self::env('url'),
                'timeout'  => 2.0,
                'headers' => [
                    // 'apikey' => self::env('apikey')
                ]
            ]
        );
        return $client;
    }

    public static function authorize()
    {
        self::$ENV['url'] = "https://login.microsoftonline.com";

        $db = App::get('session')->getDB();
        $clientId = $db->singleValue('select val from  msgraph_setup where id = "clientId"', [], 'val');
        $tenantId = $db->singleValue('select val from  msgraph_setup where id = "tenantId"', [], 'val');
        self::$ENV['tenant'] = App::configuration('microsoft-mail', 'tenantId', $tenantId);
        self::$ENV['client_id'] = App::configuration('microsoft-mail', 'clientId', $clientId);
        $client = self::getClient();
        //echo self::replacer('/{{tenant}}/oauth2/v2.0/authorize?={{client_id}}&response_type=code'); exit();
        $response = $client->get(self::replacer('/{{tenant}}/oauth2/v2.0/authorize'), [
            'query' => [
                'client_id' => self::$ENV['client_id'],
                'response_type' => 'code',
                'response_mode' => 'json',
                'redirect_uri' => 'http://localhost/myapp/',
                'state' => Uuid::uuid4(),
                'scope' => 'offline_access user.read mail.read mail.send',
            ]
        ]);

        $code = $response->getStatusCode(); // 200
        $reason = $response->getReasonPhrase(); // OK

        if ($code != 200) {

            throw new \Exception($reason);
        }
        echo $response->getBody()->getContents();
        exit();
        $result = json_decode($response->getBody()->getContents(), true);
        var_dump($result);
        return $result;

        /*
        &redirect_uri=http%3A%2F%2Flocalhost%2Fmyapp%2F&response_mode=query&scope=offline_access%20user.read%20mail.read&state=12345', [
        ]);
        */
    }



    public static function getDateRange(int $start, int $stop, string $base_currency, array $currencies, string $accuracy = 'day')
    {
        $client = self::getClient();
        $response = $client->get('/v3/range', [
            'query' => [
                'datetime_start' => date('Y-m-d\TH:i:s\Z', $start),
                'datetime_end' => date('Y-m-d\TH:i:s\Z', $stop),
                'accuracy' => $accuracy,
                'base_currency' => $base_currency,
                'currencies' => implode(',', $currencies)
            ]
        ]);
        $code = $response->getStatusCode(); // 200
        $reason = $response->getReasonPhrase(); // OK

        if ($code != 200) {
            throw new \Exception($reason);
        }
        $result = json_decode($response->getBody()->getContents(), true);
        return $result;
    }


    public static function get_token($code = null)
    {
        try {
            $db = TualoApplication::get('session')->getDB();
            $clientId = $db->singleValue('select val from  msgraph_setup where id = "clientId"', [], 'val');
            $tenantId = $db->singleValue('select val from  msgraph_setup where id = "tenantId"', [], 'val');
            $clientSecret = $db->singleValue('select val from  msgraph_setup where id = "clientSecret"', [], 'val');
            $url_token = 'https://login.microsoftonline.com/' . $tenantId . "/oauth2/v2.0/token";
            $client = self::getClient();
            $scopes = [
                'Mail.ReadWrite',
                'Mail.Send',
                'User.Read.All',
                'Files.Read',
                'Files.Read.All',
                'Files.ReadWrite',
                'Files.ReadWrite.All',
                'Sites.Read.All',
                'Sites.ReadWrite.All',
            ];
            $request = $client->post($url_token, array(
                "form_params" => array(
                    "client_id"     => $clientId,
                    "client_secret" => $clientSecret,
                    "redirect_uri"  => "",
                    "scope"         => implode(" ", $scopes),
                    "grant_type"    => "authorization_code",
                    "code"          => $code,
                ),
            ))->getBody()->getContents();

            $result        = json_decode($request);
            $result->valid = true;

            return $result;
        } catch (\Exception $e) {
            print_r($e);
            return false;
        }
    }


    public static function getDate(int $date, string $base_currency, array $currencies, string $accuracy = 'day')
    {
        $client = self::getClient();
        $response = $client->get('/v3/historical', [
            'query' => [
                'date' => date('Y-m-d\TH:i:s\Z', $date),
                'accuracy' => $accuracy,
                'base_currency' => $base_currency,
                'currencies' => implode(',', $currencies)
            ]
        ]);
        $code = $response->getStatusCode(); // 200
        $reason = $response->getReasonPhrase(); // OK

        if ($code != 200) {
            throw new \Exception($reason);
        }
        $result = json_decode($response->getBody()->getContents(), true);
        return $result;
    }
}
