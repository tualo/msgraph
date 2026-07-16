<?php

namespace Tualo\Office\MSGraph\Routes\Setup;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;
use Tualo\Office\MicrosoftMail\GraphHelper;
use Microsoft\Graph\Generated\Models\User;
use Tualo\Office\MSGraph\API;



class APIConfig extends \Tualo\Office\Basic\RouteWrapper
{
    public static function register()
    {

        BasicRoute::add('/msgraph/setup/apiconfig', function ($matches) {
            try {
                $data = json_decode(file_get_contents('php://input'), true);

                $db = App::get('session')->getDB();
                $db->direct('replace into msgraph_setup (id,val) values ("clientId",{client_id})', [
                    'client_id' => $data['client_id'],

                ]);
                $db->direct('replace into msgraph_setup (id,val) values ("tenantId",{tenant_id})', [
                    'tenant_id' => $data['tenant_id'],

                ]);
                App::result('success',  true);
            } catch (\Exception $e) {
                App::result('error', $e->getMessage());
            }
            App::contenttype('application/json');
        }, ['post'], true);
    }
}
