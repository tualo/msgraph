<?php

namespace Tualo\Office\MSGraph\api;

class MissedTokenException extends \Exception
{
    protected $message = 'Access token is missing';
}
