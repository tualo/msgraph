<?php

namespace Tualo\Office\MSGraph\Routes\Setup;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;
use Microsoft\Kiota\Abstractions\ApiException;

use Tualo\Office\MSGraph\API;
use Tualo\Office\MSGraph\api\MissedTokenException;

class GetAccessTokenRoute extends \Tualo\Office\Basic\RouteWrapper
{
    public static function register()
    {

        BasicRoute::add('/msgraph/accesstoken', function () {
            try {
                $response = API::getAccessToken();
                App::result('response', $response);
                App::result('success', true);
            } catch (ApiException $e) {
                App::result('ApiException', $e->getMessage());
            } catch (\Exception $e) {
                App::result('error', $e->getMessage());
            }
            App::contenttype('application/json');
        }, ['get'], true);
    }
}
