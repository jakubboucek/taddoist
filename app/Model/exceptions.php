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



class ApiOperationException extends Exception
{
}



class ApiForbiddenException extends ApiOperationException
{
}



class CsrfProtectionFailedException extends AuthorizationException
{
}

