<?php

namespace Tualo\Office\MSGraph\Routes\Webhook;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\IRoute;
use Microsoft\Kiota\Abstractions\ApiException;

use Tualo\Office\MSGraph\API;
use Tualo\Office\MSGraph\api\MissedTokenException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class FileRoute extends \Tualo\Office\Basic\RouteWrapper
{
    public static function store()
    {
        $db = App::get('session')->getDB();

        $sql = 'insert into msgraph_webhook (
            id,
            method,
            server,
            request,
            headers,
            data
        ) values (
            uuid(),
            {method},
            {server},
            {request},
            {headers},
            {data}
        )';
        $db->direct(
            $sql,
            [
                'method' => $_SERVER['REQUEST_METHOD'],
                'server' => json_encode($_SERVER),
                'request' => json_encode($_REQUEST),
                getallheaders(),
                file_get_contents('php://input')
            ]
        );
    }

    public static function register()
    {

        BasicRoute::add('/msgraph/webhook', function ($matches) {

            if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['validationToken'])) {
                // Validierung von Microsoft Graph
                echo $_GET['validationToken'];
                exit;
            }

            App::contenttype('application/json');
            try {
                self::store();
                App::result('success', true);
            } catch (\Exception $e) {
                App::result('error', $e->getMessage());
            }
        }, ['get'], true);

        BasicRoute::add('/msgraph/webhook', function ($matches) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validationToken'])) {
                // Validierung von Microsoft Graph
                echo $_GET['validationToken'];
                exit;
            }
            App::contenttype('application/json');
            try {
                self::store();
                App::result('success', true);
            } catch (\Exception $e) {
                App::result('error', $e->getMessage());
            }
        }, ['put'], true);

        BasicRoute::add('/msgraph/webhook', function ($matches) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validationToken'])) {
                // Validierung von Microsoft Graph
                echo $_GET['validationToken'];
                exit;
            }
            App::contenttype('application/json');
            try {
                self::store();
                App::result('success', true);
            } catch (\Exception $e) {
                App::result('error', $e->getMessage());
            }
        }, ['post'], true);
    }
}
