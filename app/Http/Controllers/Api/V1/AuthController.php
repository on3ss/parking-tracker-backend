<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\AuthenticateUser;
use App\Actions\Auth\GetAuthenticatedUser;
use App\Actions\Auth\LogoutUser;
use App\Data\Auth\LoginData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends Controller
{
    public function login(
        LoginRequest $request,
        AuthenticateUser $authenticate,
    ): JsonResponse {
        $result = $authenticate->execute(
            new LoginData(
                email: $request->string('email')->toString(),
                password: $request->string('password')->toString(),
                deviceName: $request->string('device_name')->toString(),
            ),
        );

        return response()->json([
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ],
        ]);
    }

    public function me(
        Request $request,
        GetAuthenticatedUser $getUser,
    ): UserResource {
        return new UserResource(
            $getUser->execute($request->user()),
        );
    }

    public function logout(
        Request $request,
        LogoutUser $logout,
    ): JsonResponse {
        $logout->execute($request->user());

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
