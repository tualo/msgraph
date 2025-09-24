<?php

namespace Tualo\Office\MSGraph\api;

use Microsoft\Kiota\Abstractions\Authentication\AccessTokenProvider;

use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Http\Promise\RejectedPromise;

interface TokenProvider extends AccessTokenProvider
{
    public function setAccessToken(string $token): void;

    public function getAccessToken(string $device_code = "");

    public function deviceLogin(): array;

    public function getAccessTokenByRefreshToken($refresh_token);
    /*
    public function clientSecretLogin(): void;

    public function getScopes(): string;

    public function getClientId(): string;

    public function getTenantId(): string;
    */
}
