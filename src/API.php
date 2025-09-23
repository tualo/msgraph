<?php

namespace Tualo\Office\MSGraph;

use Tualo\Office\Basic\TualoApplication;
use Ramsey\Uuid\Uuid;
use GuzzleHttp\Client;
use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\MSGraph\api\MissedTokenException;

class API
{

    private static $ENV = null;
    private static $SCOPES = null;



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

                self::$ENV = [];
                $tenantId = $db->singleValue('select val from  msgraph_setup where id = "tenantId"', [], 'val');
                $tenantId = App::configuration('microsoft-mail', 'tenantId', $tenantId);

                $data = $db->direct('select id,val from  msgraph_setup  ', []);
                foreach ($data as $d) {
                    self::$ENV[$d['id']] = $d['val'];
                }

                if (!$tenantId) {
                    throw new \Exception('no setup found!');
                }

                $data = $db->singleValue('select val from msgraph_environments where id = concat("msgraph_",getSessionUser())', [], 'val');
                if ($data === false) {
                    throw new \Exception('no setup');
                }
                $json = json_decode($data, true);
                foreach ($json as $k => $d) {
                    self::$ENV[$k] = $d;
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return self::$ENV;
    }

    public static function getScopes(): array
    {
        if (is_null(self::$SCOPES)) {
            $db = TualoApplication::get('session')->getDB();
            try {
                $data = $db->direct('select id from msgraph_scope');
                if (count($data) == 0) {
                    throw new \Exception('no scope setup');
                }
                foreach ($data as $d) {
                    self::$SCOPES[] = $d['id'];
                }
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
        return self::$SCOPES;
    }

    public static function env($key)
    {
        $env = self::getEnvironment();

        if (isset($env[$key])) {
            return $env[$key];
        }

        if ($key == 'access_token') {
            throw new MissedTokenException();
        }

        throw new \Exception('Environment ' . $key . ' not found!');
    }


    private static function getClient(array $header = []): Client
    {
        $client = new Client(
            [
                // 'base_uri' => self::env('url'),
                'timeout'  => 2.0,
                'headers' => $header
            ]
        );
        return $client;
    }

    public static function getMe()
    {
        $tokenClient = self::getClient([
            'authorization' => 'Bearer ' . self::env('access_token'),
        ]);

        $url = 'https://graph.microsoft.com/v1.0/me';
        $response = json_decode($tokenClient->get($url)->getBody()->getContents(), true);
        return $response;
    }


    public static function pushFileTo()
    {
        // https://graph.microsoft.com/v1.0/me/drive/root:/FolderA/current.docx:/content
        $tokenClient = self::getClient([
            'authorization' => 'Bearer ' . self::env('access_token'),
        ]);
        $db = TualoApplication::get('session')->getDB();
        $file_id = 183145;
        $files = $db->direct("select * from fb_wvd.doc_binary where document_link = {id}", [
            'id' => $file_id
        ]);

        $url = 'https://graph.microsoft.com/v1.0/me/drive/root:/FolderA/' . $file_id . '.docx:/content';
        $response = json_decode($tokenClient->put($url, [
            'Content-Type' => 'application/msword',
            'body' => $files[0]['doc_data']
        ])->getBody()->getContents(), true);
        return $response;
    }


    /**
     * Refresh Access Token, if it is older than 10 minutes
     * @throws \Exception
     */
    public static function refreshAccessToken()
    {
        $scopes = self::getScopes();


        $tokenClient = self::getClient();
        $tenantId = self::env('tenantId');
        $clientId = self::env('clientId');
        $db = App::get('session')->getDB();


        $sql = 'select msgraph_environments.* from msgraph_environments where now()  > expires + interval - 600 second and login = getSessionUser()';
        $data = $db->direct($sql, []);
        foreach ($data as $d) {
            $json = json_decode($d['val'], true);
            if (!isset($json['refresh_token'])) {
                throw new \Exception('no refresh_token found');
            }
            $url = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/token';
            $response = json_decode($tokenClient->post($url, [
                'form_params' => [
                    'client_id' => $clientId,

                    'refresh_token' => $json['refresh_token'],
                    'grant_type' => 'refresh_token',
                    // 'client_secret' => self::env('client_secret'),
                    'scope' => implode(' ', $scopes)
                ]
            ])->getBody()->getContents(), true);

            if (!isset($response['access_token'])) {
                throw new \Exception('no access_token found');
            }
            if (!isset($response['refresh_token'])) {
                throw new \Exception('no refresh_token found');
            }
            if (!isset($response['expires_in'])) {
                throw new \Exception('no expires_in found');
            }

            $sql = '
                insert into msgraph_environments 
                    (id,val,login,updated,expires) 
                values 
                    (concat("msgraph_",getSessionUser()),{object},getSessionUser(),now(),now() + interval ' . $response['expires_in'] . ' second  )
                on duplicate key update 
                    val=values(val),
                    login=values(login),
                    updated=values(updated),
                    expires=values(expires)
            ';
            $db->direct($sql, [
                'object' => json_encode($response)
            ]);
        }


        return true;
    }

    public static function getDeviceCodeLogin()
    {

        $scopes = self::getScopes();


        $tokenClient = self::getClient();
        $tenantId = self::env('tenantId');
        $clientId = self::env('clientId');


        $deviceCodeRequestUrl = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/devicecode';
        $deviceCodeResponse = json_decode($tokenClient->post($deviceCodeRequestUrl, [
            'form_params' => [
                'client_id' => $clientId,
                'scope' => implode(' ', $scopes)
            ]
        ])->getBody()->getContents(), true);
        return $deviceCodeResponse;
    }

    public static function getTokenByDeviceCode(string $device_code)
    {
        $tokenClient = self::getClient();
        $tenantId = self::env('tenantId');
        $clientId = self::env('clientId');

        $tokenRequestUrl = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/v2.0/token';
        $tokenResponse = json_decode($tokenClient->post($tokenRequestUrl, [
            'form_params' => [
                'client_id' => $clientId,
                'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
                'device_code' => $device_code
            ]
        ])->getBody()->getContents(), true);
        return $tokenResponse;
    }

    /*
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
    }*/
}
