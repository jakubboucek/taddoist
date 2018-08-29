<?php

namespace App\Model;

class Exception extends \RuntimeException
{
}



class UserRequiredLoggedInFirstException extends Exception
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



class AccessTokenNotFoundException extends AuthorizationException
{
}



class ApiOperationFailed extends Exception
{
}



class CsrfProtectionFailedException extends AuthorizationException
{
}

