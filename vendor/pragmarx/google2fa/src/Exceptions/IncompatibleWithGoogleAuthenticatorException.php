<?php

declare(strict_types=1);

namespace PragmaRX\Google2FA\Exceptions;

use Throwable;
use PragmaRX\Google2FA\Exceptions\Contracts\Google2FA as Google2FAExceptionContract;
use PragmaRX\Google2FA\Exceptions\Contracts\IncompatibleWithGoogleAuthenticator as IncompatibleWithGoogleAuthenticatorExceptionContract;

class IncompatibleWithGoogleAuthenticatorException extends Google2FAException implements
    Google2FAExceptionContract,
    IncompatibleWithGoogleAuthenticatorExceptionContract
{
    public function __construct(int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('This secret key is not compatible with Google Authenticator.', $code, $previous);
    }
}
