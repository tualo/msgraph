<?php

namespace Tualo\Office\MSGraph;

use Microsoft\Graph\GraphRequestAdapter;
use Microsoft\Kiota\Abstractions\Authentication\BaseBearerTokenAuthenticationProvider;
use Microsoft\Graph\Generated\Models\Subscription;
use Microsoft\Kiota\Authentication\Cache\InMemoryAccessTokenCache;
use Microsoft\Graph\GraphServiceClient;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAccessTokenProvider;
use Microsoft\Graph\Core\Authentication\GraphPhpLeagueAuthenticationProvider;
use Microsoft\Kiota\Authentication\Oauth\AuthorizationCodeContext;

use Ramsey\Uuid\Uuid;
use GuzzleHttp\Client;
use League\OAuth2\Client\Token\AccessToken;


use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\MSGraph\api\MissedTokenException;
use Tualo\Office\MSGraph\api\DeviceCodeTokenProvider;

class API
{

    private static $ENV = null;
    private static $SCOPES = null;



    public static function addEnvrionment(string $id, string $val)
    {
        self::$ENV[$id] = $val;
        $db = App::get('session')->getDB();
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
            $db = App::get('session')->getDB();
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
            $db = App::get('session')->getDB();
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


    protected static function getClient(array $header = []): Client
    {
        $client = new Client(
            [
                // 'base_uri' => self::env('url'),
                'timeout'  => 8.0,
                'headers' => $header
            ]
        );
        return $client;
    }

    public static function graphURL(): string
    {
        return 'https://graph.microsoft.com/v1.0';
    }

    public static function getMe()
    {
        $tokenClient = self::getClient([
            'authorization' => 'Bearer ' . self::env('access_token')
        ]);

        $url = self::graphURL() . '/me';
        $clientResponse = $tokenClient->get($url, [
            'http_errors' => false,
            'exceptions' => false
        ]);
        $statusCode = $clientResponse->getStatusCode();
        $response = json_decode($clientResponse->getBody()->getContents(), true);
        $response['statusCode'] = $statusCode;
        return $response;
    }


    public static function pushFileTo()
    {
        // https://graph.microsoft.com/v1.0/me/drive/root:/FolderA/current.docx:/content
        $tokenClient = self::getClient([
            'authorization' => 'Bearer ' . self::env('access_token'),
        ]);
        $db = App::get('session')->getDB();
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


    public static function getChildren()
    {
        $tokenClient = self::getClient([
            'Authorization' => 'Bearer ' .  self::env('access_token'),
            'Content-Type' => 'application/json'
        ]);
        $tenantId = self::env('tenantId');
        $clientId = self::env('clientId');

        $tokenRequestUrl = 'https://graph.microsoft.com/v1.0/drives';
        $tokenResponse = json_decode($tokenClient->get($tokenRequestUrl)->getBody()->getContents(), true);
        return $tokenResponse;
    }



    public static function GraphClient()
    {
        $scopes = self::getScopes();
        $tokenProvider = new DeviceCodeTokenProvider(
            self::env('clientId'),
            self::env('tenantId'),
            implode(' ', $scopes),
            self::env('access_token')
        );
        $authProvider = new BaseBearerTokenAuthenticationProvider($tokenProvider);
        $adapter = new GraphRequestAdapter($authProvider);
        $graphServiceClient = GraphServiceClient::createWithRequestAdapter($adapter);
        return $graphServiceClient;
    }


    public static function createFolder()
    {
        // https://graph.microsoft.com/v1.0/me/drive/root/children
        // https://tualo-my.sharepoint.com/_api/v2.0/drives('default')/items('root')/children('01X23YR22C6YMJR36BOZA2WDX4274PAJQC')
        $graphServiceClient = self::GraphClient();
    }
    // https://graph.microsoft.com/v1.0/subscriptions
    // https://fb-wvd.tualo.io/server/~/d44209f7-e5b2-4719-ae85-f303d9ab0db6/msgraph/webhook
    public static function createSubscription(string $resource, string $changeType = 'created,updated,delete', int $lifetime = 43200)
    {

        $graphServiceClient = self::GraphClient();


        $requestBody = new Subscription();
        $requestBody->setChangeType('updated');
        $requestBody->setNotificationUrl('https://fb-wvd.tualo.io/server/~/d44209f7-e5b2-4719-ae85-f303d9ab0db6/msgraph/webhook');
        $requestBody->setResource($resource);
        $requestBody->setExpirationDateTime(new \DateTime('2025-09-24T18:23:45.9356913Z'));
        $requestBody->setClientState('secretClientValue');
        $requestBody->setLatestSupportedTlsVersion('v1_2');

        $result = $graphServiceClient->subscriptions()->post($requestBody)->wait();

        if (false) {
            $requestBody = new Subscription();
            $requestBody->setChangeType('created,updated,deleted');
            // $requestBody->setLifecycleNotificationUrl('https://webhook.azurewebsites.net/api/lifecycleNotifications');
            $requestBody->setResource($resource);
            $requestBody->setExpirationDateTime(new \DateTime('2025-09-24T11:00:00.0000000Z'));
            $requestBody->setClientState('SecretClientState');

            echo 1;
            $result = $graphServiceClient->subscriptions()->post($requestBody)->wait();

            echo 2;
        }
        /*

        $tokenClient = self::getClient();
        $tenantId = self::env('tenantId');
        $clientId = self::env('clientId');
        $url = 'https://graph.microsoft.com/v1.0/subscriptions';
        $response = $tokenClient->post($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $tokenClient->getAccessToken(),
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'changeType' => $changeType,
                'notificationUrl' => 'https://fb-wvd.tualo.io/server/~/d44209f7-e5b2-4719-ae85-f303d9ab0db6/msgraph/webhook',
                'resource' => $resource,
                // 'expirationDateTime' => (new \DateTime())->add(new \DateInterval('PT' . $lifetime . 'S'))->format(DATE_ISO8601),
                'clientState' => 'secretClientValue'
            ]
        ]);
        echo 1;
        return json_decode($response->getBody()->getContents(), true);
        */
        return $result;
    }
}
