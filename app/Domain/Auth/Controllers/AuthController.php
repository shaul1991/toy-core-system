<?php

declare(strict_types=1);

namespace App\Domain\Auth\Controllers;

use App\Domain\Auth\Services\JwtService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Traits\ApiResponsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponsable;

    public function __construct(
        private readonly JwtService $jwtService,
    ) {}

    /**
     * JWT 토큰 갱신 (Refresh Token Rotation)
     *
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     tags={"Auth"},
     *     summary="JWT 토큰 갱신",
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="토큰 갱신 성공"),
     *     @OA\Response(response=401, description="Refresh Token 만료/무효")
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->validate([
            'refresh_token' => 'required|string',
        ]);

        $refreshToken = $request->input('refresh_token');

        $tokenDTO = $this->jwtService->refreshTokenPair($refreshToken);

        return $this->successResponse($tokenDTO->toArray());
    }

    /**
     * 로그아웃
     *
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Auth"},
     *     summary="로그아웃",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="로그아웃 성공")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->bearerToken();
        $refreshToken = $request->input('refresh_token');

        if ($accessToken) {
            $this->jwtService->logout($accessToken, $refreshToken);
        }

        return $this->successResponse([
            'message' => '로그아웃되었습니다.',
        ]);
    }

    /**
     * 전체 세션 로그아웃 (모든 디바이스)
     *
     * @OA\Post(
     *     path="/api/auth/logout-all",
     *     tags={"Auth"},
     *     summary="모든 디바이스에서 로그아웃",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="전체 로그아웃 성공")
     * )
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        if ($user) {
            $this->jwtService->logoutAll($user);
        }

        return $this->successResponse([
            'message' => '모든 디바이스에서 로그아웃되었습니다.',
        ]);
    }

    /**
     * 현재 사용자 정보 조회
     *
     * @OA\Get(
     *     path="/api/auth/me",
     *     tags={"Auth"},
     *     summary="현재 사용자 정보 조회",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="사용자 정보")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'social_accounts' => $user->socialAccounts->map(fn ($account) => [
                'provider' => $account->provider,
                'linked_at' => $account->created_at,
            ]),
        ]);
    }

    /**
     * 토큰 검증 (디버그/테스트용)
     *
     * @OA\Post(
     *     path="/api/auth/validate",
     *     tags={"Auth"},
     *     summary="토큰 유효성 검증",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="토큰 유효")
     * )
     */
    public function validate(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (! $token) {
            return $this->unauthorizedResponse('토큰이 필요합니다.');
        }

        $user = $this->jwtService->validateAccessToken($token);

        return $this->successResponse([
            'valid' => true,
            'user_id' => $user->id,
        ]);
    }
}
