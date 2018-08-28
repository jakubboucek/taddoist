<?php

namespace App\Model;

class Exception extends \RuntimeException
{
}



class AuthorizationException extends Exception
{
}



class InvalidStateException extends AuthorizationException
{
}



class TokenExchangeException extends AuthorizationException
{
}



class CsrfProtectionFailedException extends AuthorizationException
{
}

