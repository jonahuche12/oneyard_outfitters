<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;

class GenericPasswordResetLinkResponse implements
    SuccessfulPasswordResetLinkRequestResponse,
    FailedPasswordResetLinkRequestResponse
{
    public function toResponse($request)
    {
        $message = __(
            'If an active staff account matches that email address, a password reset link will be sent.'
        );

        if ($request instanceof Request && $request->expectsJson()) {
            return new JsonResponse(['message' => $message], 200);
        }

        return back()->with('status', $message);
    }
}
