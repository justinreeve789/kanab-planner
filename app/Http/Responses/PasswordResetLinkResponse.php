<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;

/**
 * Answers "send me a reset link" the same way whether or not the email has an account, so the form can't be used to
 * find out who has signed up. Only a throttled request (the same email asked again within a minute) is reported.
 */
class PasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse, SuccessfulPasswordResetLinkRequestResponse
{
    public const MESSAGE = 'If an account uses that email address, we\'ve sent it a link to reset the password.';

    public function __construct(private string $status)
    {
    }

    public function toResponse($request)
    {
        if ($this->status === Password::RESET_THROTTLED) {
            throw ValidationException::withMessages(['email' => [trans($this->status)]]);
        }

        return new JsonResponse(['message' => self::MESSAGE]);
    }
}
