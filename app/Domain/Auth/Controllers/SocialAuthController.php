<?php

declare(strict_types=1);

namespace App\Domain\Auth\Controllers;

use App\Domain\Auth\Services\SocialAuthService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Shared\Http\Traits\ApiResponsable;
use App\Shared\Http\Traits\HasAuthenticatedUserId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialAuthController extends Controller
{
    use ApiResponsable;
    use HasAuthenticatedUserId;

    public function __construct(
        private readonly SocialAuthService $socialAuthService,
    ) {}

    /**
     * 소셜 로그인 리다이렉트 URL 반환
     *
     * @OA\Get(
     *     path="/api/auth/{provider}/redirect",
     *     tags={"Auth"},
     *     summary="소셜 로그인 리다이렉트 URL",
     *
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="소셜 제공자 (github, naver, kakao)",
     *
     *         @OA\Schema(type="string", enum={"github", "naver", "kakao"})
     *     ),
     *
     *     @OA\Response(response=200, description="리다이렉트 URL 반환"),
     *     @OA\Response(response=400, description="지원하지 않는 제공자")
     * )
     */
    public function redirect(string $provider): JsonResponse
    {
        $redirectUrl = $this->socialAuthService->getRedirectUrl($provider);

        return $this->successResponse([
            'redirect_url' => $redirectUrl,
        ]);
    }

    /**
     * 소셜 로그인 콜백 처리
     *
     * @OA\Get(
     *     path="/api/auth/{provider}/callback",
     *     tags={"Auth"},
     *     summary="소셜 로그인 콜백",
     *
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="소셜 제공자 (github, naver, kakao)",
     *
     *         @OA\Schema(type="string", enum={"github", "naver", "kakao"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         required=true,
     *         description="OAuth 인증 코드",
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(response=200, description="로그인 성공"),
     *     @OA\Response(response=400, description="인증 실패")
     * )
     */
    public function callback(string $provider): JsonResponse
    {
        $tokenDTO = $this->socialAuthService->handleCallback($provider);

        return $this->successResponse($tokenDTO->toArray());
    }

    /**
     * 소셜 계정 연동 (로그인 상태 필요)
     *
     * @OA\Post(
     *     path="/api/auth/{provider}/link",
     *     tags={"Auth"},
     *     summary="소셜 계정 연동",
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
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="소셜 제공자 (github, naver, kakao)",
     *
     *         @OA\Schema(type="string", enum={"github", "naver", "kakao"})
     *     ),
     *
     *     @OA\Response(response=200, description="연동 성공"),
     *     @OA\Response(response=400, description="X-User-Id 헤더 누락"),
     *     @OA\Response(response=404, description="사용자 없음"),
     *     @OA\Response(response=409, description="이미 다른 계정에 연동됨")
     * )
     */
    public function link(Request $request, string $provider): JsonResponse
    {
        $userId = $this->requireAuthenticatedUserId($request);
        $user = User::find($userId);

        if (! $user) {
            return $this->notFoundResponse('사용자를 찾을 수 없습니다.');
        }

        $socialAccount = $this->socialAuthService->linkAccount($user, $provider);

        return $this->successResponse([
            'message' => '소셜 계정이 연동되었습니다.',
            'provider' => $provider,
            'linked_at' => $socialAccount->created_at->toIso8601String(),
        ]);
    }

    /**
     * 소셜 계정 연동 해제
     *
     * @OA\Delete(
     *     path="/api/auth/{provider}/unlink",
     *     tags={"Auth"},
     *     summary="소셜 계정 연동 해제",
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
     *     @OA\Parameter(
     *         name="provider",
     *         in="path",
     *         required=true,
     *         description="소셜 제공자 (github, naver, kakao)",
     *
     *         @OA\Schema(type="string", enum={"github", "naver", "kakao"})
     *     ),
     *
     *     @OA\Response(response=200, description="연동 해제 성공"),
     *     @OA\Response(response=400, description="X-User-Id 헤더 누락 또는 마지막 로그인 수단"),
     *     @OA\Response(response=404, description="사용자 없음")
     * )
     */
    public function unlink(Request $request, string $provider): JsonResponse
    {
        $userId = $this->requireAuthenticatedUserId($request);
        $user = User::find($userId);

        if (! $user) {
            return $this->notFoundResponse('사용자를 찾을 수 없습니다.');
        }

        $this->socialAuthService->unlinkAccount($user, $provider);

        return $this->successResponse([
            'message' => '소셜 계정 연동이 해제되었습니다.',
            'provider' => $provider,
        ]);
    }

    /**
     * 연동된 소셜 계정 목록 조회
     *
     * @OA\Get(
     *     path="/api/auth/social-accounts",
     *     tags={"Auth"},
     *     summary="연동된 소셜 계정 목록",
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
     *     @OA\Response(response=200, description="연동된 계정 목록"),
     *     @OA\Response(response=400, description="X-User-Id 헤더 누락"),
     *     @OA\Response(response=404, description="사용자 없음")
     * )
     */
    public function socialAccounts(Request $request): JsonResponse
    {
        $userId = $this->requireAuthenticatedUserId($request);
        $user = User::find($userId);

        if (! $user) {
            return $this->notFoundResponse('사용자를 찾을 수 없습니다.');
        }

        $accounts = $this->socialAuthService->getLinkedAccounts($user);

        return $this->successResponse($accounts);
    }
}
