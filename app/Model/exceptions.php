<?php

namespace App\Model;

use RuntimeException;

class Exception extends RuntimeException
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

class ApiForbiddenException extends ApiOperationException
{
}

class CsrfProtectionFailedException extends AuthorizationException
{
}
