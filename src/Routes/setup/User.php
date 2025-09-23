<?php

namespace Tualo\Office\MSGraph\Routes\Setup;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;
use Microsoft\Kiota\Abstractions\ApiException;

use Tualo\Office\MSGraph\API;
use Tualo\Office\MSGraph\api\MissedTokenException;

class UserRoute implements IRoute
{
    public static function register()
    {

        BasicRoute::add('/msgraph/setup/user', function ($matches) {
            try {
                $response = API::getMe();
                App::result('response', $response);
                App::result('success', true);
            } catch (MissedTokenException $e) {
                App::result('error', "no access token");
            } catch (ApiException $e) {
                App::result('ApiException', $e->getMessage());
            } catch (\Exception $e) {
                App::result('error', $e->getMessage());
            }
            App::contenttype('application/json');
        }, ['get'], true);

        BasicRoute::add('/msgraph/setup/user/devicecode', function ($matches) {
            try {
                $response = API::getDeviceCodeLogin();
                App::result('response', $response);
                App::result('success', true);
            } catch (ApiException $e) {
                App::result('ApiException', $e->getMessage());
            } catch (\Exception $e) {
                App::result('error', $e->getMessage());
            }
            App::contenttype('application/json');
        }, ['get'], true);

        BasicRoute::add('/msgraph/setup/user/checktoken', function ($matches) {
            try {
                $payload = json_decode(@file_get_contents('php://input'), true);
                $db = App::get('session')->getDB();

                if (isset($payload['device_code'])) {
                    $response = API::getTokenByDeviceCode($payload['device_code']);
                    App::result('response', $response);
                    App::result('success', true);


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
                } else {
                    throw new \Exception('  device_code missing');
                }
            } catch (ApiException $e) {
                App::result('ApiException', $e->getMessage());
            } catch (\Exception $e) {
                App::result('error', $e->getMessage());
            }
            App::contenttype('application/json');
        }, ['post'], true);
    }
}
