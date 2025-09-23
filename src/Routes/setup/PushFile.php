<?php

namespace Tualo\Office\MSGraph\Routes\Setup;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;
use Microsoft\Kiota\Abstractions\ApiException;

use Tualo\Office\MSGraph\API;
use Tualo\Office\MSGraph\api\MissedTokenException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class PushFileRoute implements IRoute
{
    public static function register()
    {

        BasicRoute::add('/msgraph/refresh', function ($matches) {
            try {
                App::result('refresh', API::refreshAccessToken());
                App::result('success', true);
            } catch (MissedTokenException $e) {
                App::result('error', "no access token");
            } catch (ClientException $e) {
                App::result('error', json_decode($e->getResponse()->getBody()->getContents(), true));
            } catch (ServerException $e) {
                App::result('error', json_decode($e->getResponse()->getBody()->getContents(), true));
            } catch (ApiException $e) {
                App::result('ApiException', $e->getMessage());
            } catch (\Exception $e) {
                App::result('error', $e->getMessage());
            }
            // 
            // GuzzleHttp\Exception\ServerException
            App::contenttype('application/json');
        }, ['get'], true);

        BasicRoute::add('/msgraph/push/file', function ($matches) {
            try {
                App::result('refresh', API::refreshAccessToken());
                App::result('pushFileTo', API::pushFileTo());
                App::result('success', true);
            } catch (MissedTokenException $e) {
                App::result('error', "no access token");
            } catch (ClientException $e) {
                $error = json_decode($e->getResponse()->getBody()->getContents(), true);
                // if (isset($error['error']) && isset($error['error']['code']) && ($error['error']['code'] == 'InvalidAuthenticationToken' || $error['error']['code'] == 'TokenExpired')) {
                App::result('error', $error['error']['message']);
            } catch (ServerException $e) {
                App::result('error', json_decode($e->getResponse()->getBody()->getContents(), true));
            } catch (ApiException $e) {
                App::result('ApiException', $e->getMessage());
            } catch (\Exception $e) {
                App::result('error', $e->getMessage());
            }
            // 
            // GuzzleHttp\Exception\ServerException
            App::contenttype('application/json');
        }, ['get'], true);
    }
}
