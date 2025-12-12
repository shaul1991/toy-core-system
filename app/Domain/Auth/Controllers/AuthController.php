<?php

declare(strict_types=1);

namespace App\Domain\Auth\Controllers;

use App\Domain\Auth\Services\JwtService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Shared\Http\Traits\ApiResponsable;
use App\Shared\Http\Traits\HasAuthenticatedUserId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponsable;
    use HasAuthenticatedUserId;

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
     *
     *     @OA\Parameter(
     *         name="X-User-Id",
     *         in="header",
     *         required=true,
     *         description="BFF에서 JWT 검증 후 전달하는 사용자 ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="refresh_token", type="string", description="무효화할 Refresh Token")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="로그아웃 성공"),
     *     @OA\Response(response=400, description="X-User-Id 헤더 누락")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        // BFF에서 JWT 검증 후 전달된 사용자 ID 확인
        $this->requireAuthenticatedUserId($request);

        $refreshToken = $request->input('refresh_token');

        // Refresh Token 무효화 (Access Token은 BFF에서 관리)
        if ($refreshToken) {
            $this->jwtService->invalidateRefreshToken($refreshToken);
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
     *
     *     @OA\Parameter(
     *         name="X-User-Id",
     *         in="header",
     *         required=true,
     *         description="BFF에서 JWT 검증 후 전달하는 사용자 ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(response=200, description="전체 로그아웃 성공"),
     *     @OA\Response(response=400, description="X-User-Id 헤더 누락")
     * )
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $userId = $this->requireAuthenticatedUserId($request);
        $user = User::find($userId);

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
     *
     *     @OA\Parameter(
     *         name="X-User-Id",
     *         in="header",
     *         required=true,
     *         description="BFF에서 JWT 검증 후 전달하는 사용자 ID",
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(response=200, description="사용자 정보"),
     *     @OA\Response(response=400, description="X-User-Id 헤더 누락"),
     *     @OA\Response(response=404, description="사용자 없음")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        $userId = $this->requireAuthenticatedUserId($request);
        $user = User::with('socialAccounts')->find($userId);

        if (! $user) {
            return $this->notFoundResponse('사용자를 찾을 수 없습니다.');
        }

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
