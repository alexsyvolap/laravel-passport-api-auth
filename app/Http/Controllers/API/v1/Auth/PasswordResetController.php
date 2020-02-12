<?php


namespace App\Http\Controllers\API\v1\Auth;


use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordCreateResetRequest;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use App\Entities\User;
use App\Entities\PasswordReset;
use App\Http\Requests\Auth\PasswordResetRequest as PWDResetRequest;

class PasswordResetController extends Controller
{
    /**
     * Create token password reset
     *
     * @param PasswordCreateResetRequest $request
     * @return JsonResponse [string] message
     */
    public function create(PasswordCreateResetRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user)
            return response()->json([
                'message' => "We can't find a user with that e-mail address."
            ], 404);
        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => str_random(60)
             ]
        );
        if ($user && $passwordReset)
            $user->notify(
                new PasswordResetRequest($passwordReset->token)
            );
        return response()->json([
            'message' => 'We have e-mailed your password reset link!'
        ]);
    }

    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return JsonResponse [string] message
     */
    public function find($token)
    {
        $passwordReset = PasswordReset::where('token', $token)
            ->first();
        if (!$passwordReset)
            return response()->json([
                'message' => 'This password reset token is invalid.'
            ], 404);
        if (Carbon::parse($passwordReset->updated_at)->addMinutes(720)->isPast()) {
            $passwordReset->delete();
            return response()->json([
                'message' => 'This password reset token is invalid.'
            ], 404);
        }
        return response()->json($passwordReset);
    }

    /**
     * Reset password
     *
     * @param PWDResetRequest $request
     * @return JsonResponse [string] message
     */
    public function reset(PWDResetRequest $request)
    {
        $passwordReset = PasswordReset::where([
            ['token', $request->token],
            ['email', $request->email]
        ])->first();
        if (!$passwordReset)
            return response()->json([
                'message' => 'This password reset token is invalid.'
            ], 404);
        $user = User::where('email', $passwordReset->email)->first();
        if (!$user)
            return response()->json([
                'message' => "We can't find a user with that e-mail address."
            ], 404);
        $user->password = bcrypt($request->password);
        $user->save();
        $passwordReset->delete();
        $user->notify(new PasswordResetSuccess($passwordReset));
        return response()->json($user);
    }
}
