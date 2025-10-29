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

use Microsoft\Graph\Generated\Models;


use Microsoft\Graph\Generated\Users\Item\Messages\MessagesRequestBuilderGetRequestConfiguration;
use Microsoft\Graph\Generated\Applications\ApplicationsRequestBuilderGetRequestConfiguration;


class PushFileRoute extends \Tualo\Office\Basic\RouteWrapper
{
    public static function register()
    {
        BasicRoute::add('/msgraph/test', function ($matches) {
            try {
                $graphServiceClient = API::GraphClient();
                API::createSubscription("drives('default')/items('root')/children('01X23YR22C6YMJR36BOZA2WDX4274PAJQC')");
            } catch (ApiException $ex) {
                App::result('drives', []);
                App::result('drives_error', [
                    'code' => $ex->getResponseStatusCode(),
                    'message' => $ex->getError()->getMessage()
                ]);
            }
            try {
                $graphServiceClient = API::GraphClient();



                $children = API::getChildren();
                App::result('children', $children);
                $list = [];
                try {

                    $drives = $graphServiceClient->drives()->get()->wait();
                    if ($drives && $drives->getValue()) {
                        foreach ($drives->getValue() as $drive) {

                            $list[] = [
                                'id' => $drive->getId(),
                                'name' => $drive->getName(),
                                'type' => $drive->getDriveType(),
                                'description' => $drive->getDescription(),
                                'weburl' => $drive->getWebUrl()
                            ];
                        }
                    }
                    App::result('drives', $list);
                } catch (ApiException $ex) {
                    App::result('drives', []);
                    App::result('drives_error', [
                        'code' => $ex->getResponseStatusCode(),
                        'message' => $ex->getError()->getMessage()
                    ]);
                }

                if (count($list) == 0) throw new \Exception('no drives found');
                $driveId = $list[0]['id'];


                try {
                    $driveId = $list[0]['id'];
                    $items = $graphServiceClient->drives()->byDriveId($driveId)->items()->get()->wait();
                    $list = [];
                    if ($items && $items->getValue()) {
                        foreach ($items->getValue() as $item) {

                            $list[] = [
                                'id' => $item->getId(),
                                'name' => $item->getName(),
                                'size' => $item->getSize(),
                                'folder' => $item->getFolder() ? true : false,
                                'file' => $item->getFile() ? true : false,
                                'weburl' => $item->getWebUrl()
                            ];
                        }
                    }
                    App::result('drive_items', $list);
                } catch (ApiException $ex) {
                    App::result('drive_items', []);
                    App::result('drive_items_error', [
                        'code' => $ex->getResponseStatusCode(),
                        'message' => $ex->getError()->getMessage()
                    ]);
                }


                /*
                try {
                    $root = $graphServiceClient->drives()->byDriveId($driveId)->root()->get()->wait();
                    if ($root) {
                        echo "Root ID: {$root->getId()}<br>";
                        echo "Root Name: {$root->getName()}<br>";
                        echo "Folder Child Count: " . ($root->getFolder() ? $root->getFolder()->getChildCount() : 'N/A') . "<br>";
                        echo "Root: " . json_encode($root->getRoot()) . "<br>";
                        echo "Root Size: {$root->getSize()}<br>";
                    }
                } catch (ApiException $ex) {
                    echo "Error: " . $ex->getResponseStatusCode() . "\n";
                    echo "Error: " . $ex->getError()->getMessage();
                }
                */

                try {



                    $recent = $graphServiceClient->drives()->byDriveId($list[0]['id'])->recent()->get()->wait();
                    $list = [];
                    foreach ($recent as $item) {
                    }

                    App::result('recent', $recent);
                } catch (ApiException $ex) {
                    App::result('recent_error', [
                        'code' => $ex->getResponseStatusCode(),
                        'message' => $ex->getError()->getMessage()
                    ]);
                }


                $requestConfiguration = new ApplicationsRequestBuilderGetRequestConfiguration();
                $headers = [
                    'ConsistencyLevel' => 'eventual',
                ];
                $requestConfiguration->headers = $headers;

                $queryParameters = ApplicationsRequestBuilderGetRequestConfiguration::createQueryParameters();
                $queryParameters->count = true;
                $requestConfiguration->queryParameters = $queryParameters;


                $applications = $graphServiceClient->applications()->get(/*$requestConfiguration*/)->wait();
                $list = [];
                foreach ($applications as $application) {
                    $list[] = [
                        'id' => $application->getId(),
                        'name' => $application->getDisplayName()
                    ];
                }

                App::result('applications', $list);


                $licenseDetails = $graphServiceClient->me()->licenseDetails()->get()->wait();

                $list = [];
                foreach ($licenseDetails as $license) {
                    $list[] = [
                        'id' => $license->getId(),
                        'name' => $license->getSkuPartNumber()
                    ];
                }

                App::result('licenseDetails', $list);
                // var_dump($result);

                $requestConfiguration = new MessagesRequestBuilderGetRequestConfiguration();
                $queryParameters = MessagesRequestBuilderGetRequestConfiguration::createQueryParameters();
                $queryParameters->select = ["sender", "subject"];
                $requestConfiguration->queryParameters = $queryParameters;


                $result = $graphServiceClient->me()->messages()->get($requestConfiguration)->wait();

                $list = [];

                foreach ($result->getValue() as $item) {
                    // var_dump($item->getSubject());
                    // var_dump($item->getSender()->getEmailAddress()->getAddress());
                    $list[] = [
                        'subject' => $item->getSubject(),
                        'from' => $item->getSender()->getEmailAddress()->getAddress()
                    ];
                }

                App::result('test', $list);
                App::result('success', true);
            } catch (\Exception $e) {
                App::result('error', $e->getMessage());
            }
            App::contenttype('application/json');
        }, ['get'], true);

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
                $fileResponse = API::pushFileTo();
                App::result('pushFileTo', $fileResponse);
                $subscriptionResponse = API::createSubscription($fileResponse['id']);
                App::result('createSubscription', $subscriptionResponse);
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
                App::result('ApiException', $e->getError()->getMessage());
            } catch (\Exception $e) {
                App::result('error', $e->getMessage());
            }
            // 
            // GuzzleHttp\Exception\ServerException
            App::contenttype('application/json');
        }, ['get'], true);
    }
}
