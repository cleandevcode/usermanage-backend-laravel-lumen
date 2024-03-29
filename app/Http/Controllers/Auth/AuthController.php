<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\User;
class AuthController extends Controller
{
    /**
     * Handle a login request to the application.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function postLogin( Request $request )
    {
        if($request->email == 'demo@demo.com') {
            $admin = 1;
        } else {
            $admin = 0;
        }
        $email = $request->email;
        $user = User::where("email", $email)->first();
        if($user['status'] == 0) {
            return new JsonResponse( [
                    'status_code' => 401,
                    'message'     => "Deactive User",
                ], Response::HTTP_UNAUTHORIZED );
        }

        try {
            $this->validate( $request, [
                'email'    => 'required|max:255',
                'password' => 'required',
            ] );
        }
        catch ( ValidationException $e )
        {
            return new JsonResponse( [
                'status_code' => 401,
                'message'     => $e->getResponse(),
            ], Response::HTTP_UNAUTHORIZED );
        }

        try {
            // Attempt to verify the credentials and create a token for the user
            if ( !$token = JWTAuth::attempt(
                $this->getCredentials( $request )
            ) )
            {
                return $this->onUnauthorized( $request );
            }
        }
        catch ( JWTException $e )
        {
            // Something went wrong whilst attempting to encode the token
            return $this->onJwtGenerationError();
        }

        // All good so return the token

        return $this->onAuthorized( $token, $admin );
    }

    /**
     * Handle a Registration.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function postRegister( Request $request )
    {
        try {
            $this->validate( $request, [
                'email'    => 'required|email|max:255',
                'password' => 'required',
                'name'     => 'required|max:10',
            ] );
        }
        catch ( ValidationException $e )
        {
            return $e->getResponse();
        }

        $user = \App\User::create( $request->all() );

        try {
            // Attempt to verify the credentials and create a token for the user
            if ( !$token = JWTAuth::attempt(
                $this->getCredentials( $request )
            ) )
            {
                return $this->onUnauthorized();
            }
        }
        catch ( JWTException $e )
        {
            // Something went wrong whilst attempting to encode the token
            return $this->onJwtGenerationError();
        }

        return $this->onAuthorized( $token, 0 );
    }

    /**
     * What response should be returned on invalid credentials.
     *
     * @return JsonResponse
     */
    protected function onUnauthorized()
    {
        return new JsonResponse( [
            'status_code' => 401,
            'message'     => "Email address & password doesn't matched.",
        ], Response::HTTP_UNAUTHORIZED );
    }

    /**
     * What response should be returned on error while generate JWT.
     *
     * @return JsonResponse
     */
    protected function onJwtGenerationError()
    {
        return new JsonResponse( [
            'message' => 'could_not_create_token',
        ], Response::HTTP_INTERNAL_SERVER_ERROR );
    }

    /**
     * What response should be returned on authorized.
     *
     * @return JsonResponse
     */
    protected function onAuthorized( $token, $admin )
    {
        return new JsonResponse( [
            'status_code' => 200,
            'message'     => 'token_generated',
            'admin'       => $admin,
            'data'        => [
                'token' => $token
            ],
        ] );
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    protected function getCredentials( Request $request )
    {
        return $request->only( 'email', 'password' );
    }

    /**
     * Invalidate a token.
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteInvalidate()
    {
        $token = JWTAuth::parseToken();

        $token->invalidate();

        return new JsonResponse( ['message' => 'token_invalidated'] );
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\Response
     */
    public function patchRefresh()
    {
        $token = JWTAuth::parseToken();
        $newToken = $token->refresh();

        return new JsonResponse( [
            'status_code' => 200,
            'message'     => 'token_refreshed',
            'data'        => [
                'token'  => $newToken
            ],
        ] );
    }

    /**
     * Get authenticated user.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUser()
    {
        return new JsonResponse( [
            'status_code' => 200,
            'message'     => 'authenticated_user',
            'data'        => JWTAuth::parseToken()->authenticate(),
        ] );
    }
}
