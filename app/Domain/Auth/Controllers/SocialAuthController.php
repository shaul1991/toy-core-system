<?php

declare(strict_types=1);

namespace App\Domain\Auth\Controllers;

use App\Domain\Auth\Services\SocialAuthService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Traits\ApiResponsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialAuthController extends Controller
{
    use ApiResponsable;

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
     *     security={{"bearerAuth":{}}},
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
     *     @OA\Response(response=409, description="이미 다른 계정에 연동됨")
     * )
     */
    public function link(Request $request, string $provider): JsonResponse
    {
        $user = $request->user('api');

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
     *     security={{"bearerAuth":{}}},
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
     *     @OA\Response(response=400, description="마지막 로그인 수단은 해제 불가")
     * )
     */
    public function unlink(Request $request, string $provider): JsonResponse
    {
        $user = $request->user('api');

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
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="연동된 계정 목록")
     * )
     */
    public function socialAccounts(Request $request): JsonResponse
    {
        $user = $request->user('api');

        $accounts = $this->socialAuthService->getLinkedAccounts($user);

        return $this->successResponse($accounts);
    }
}
