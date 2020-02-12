<?php


namespace App\Http\Controllers\API\v1\Auth;


use App\Enums\UserRoles;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\SignUpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Entities\User;
use App\Notifications\SignUpActivate;

class AuthController extends Controller
{

    /**
     * Create user
     *
     * @param SignUpRequest $request
     * @return JsonResponse [string] message
     */
    public function signUp(SignUpRequest $request)
    {
        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'activation_token' => str_random(60),
            'role' => UserRoles::Subscriber,
        ]);

        $user->save();
        $user->notify(new SignUpActivate($user));

        return response()->json([
            'message' => 'Successfully created user!'
        ], 201);
    }

    /**
     * Login user and create token
     *
     * @param LoginRequest $request
     * @return JsonResponse [string] access_token
     */
    public function login(LoginRequest $request)
    {
        $credentials = request(['email', 'password']);
        $credentials['active'] = 1;
        $credentials['deleted_at'] = null;

        if(!Auth::attempt($credentials))
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);

        $user = $request->user();

        $adminRole = UserRoles::getInstance(UserRoles::Administrator);
        $subscriberRole = UserRoles::getInstance(UserRoles::Subscriber);

        switch ($user->role) {
            case $adminRole:
                $tokenResult = $user->createToken('Administrator Access Token', [$adminRole->key]);
                break;
            case $subscriberRole:
                $tokenResult = $user->createToken('Subscriber Access Token', [$subscriberRole->key]);
                break;
        }

        $token = $tokenResult->token;

        if ($request->remember_me)
            $token->expires_at = Carbon::now()->addWeeks(1);

        $token->save();

        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);
    }

    /**
     * Logout user (Revoke the token)
     *
     * @param Request $request
     * @return JsonResponse [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @param Request $request
     * @return JsonResponse [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function signUpActivate($token)
    {
        $user = User::where('activation_token', $token)->first();

        if (!$user) {
            return response()->json([
                'message' => 'This activation token is invalid.'
            ], 404);
        }
        $user->active = true;
        $user->activation_token = '';
        $user->save();

        return $user;
    }

}
