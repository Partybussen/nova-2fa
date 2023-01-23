<?php

namespace Partybussen\Nova2fa\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Nova;
use PragmaRX\Google2FA\Google2FA as G2fa;
use PragmaRX\Google2FA\Support\Url;
use PragmaRX\Recovery\Recovery;
use Partybussen\Nova2fa\Google2FAAuthenticator;
use Symfony\Component\HttpFoundation\Response;

class Nova2faController extends Controller
{
    private function is2FAValid(string $secret): bool
    {
        if (empty($secret)) {
            return false;
        }

        $google2fa = new G2fa();

        return $google2fa->verifyKey(auth()->user()->user2fa->google2fa_secret, $secret);
    }

    private function isRecoveryValid($recover, $recoveryHashes): bool
    {
        foreach ($recoveryHashes as $recoveryHash) {
            if (password_verify($recover, $recoveryHash)) {
                return true;
            }
        }

        return false;
    }

    public function showAuthenticate(NovaRequest $request)
    {
         return Inertia::render('Nova2fa', [
             'recoveryEnabled' => config('nova2fa.recovery_codes.enabled')
         ]);
    }

    public function showRecovery(NovaRequest $request)
    {
        return Inertia::render('Nova2fa/Recover', []);
    }

    public function showRegister(NovaRequest $request)
    {
        $google2fa = new G2fa();
        $recovery = new Recovery();

        $recoveryEnabled = config('nova2fa.recovery_codes.enabled');

        $data = $request->session()->get('recovery_data');

        if (!$data && !$recoveryEnabled) {
            $secretKey = $google2fa->generateSecretKey();
            $data['secretKey'] = $secretKey;

            $request->session()->put('recovery_data', $data);

            $user2faModel = config('nova2fa.models.user2fa');
            $user2faModel::where('user_id', auth()->user()->id)->delete();

            $user2fa = new $user2faModel();
            $user2fa->user_id = auth()->user()->id;
            $user2fa->google2fa_secret = $secretKey;
            $user2fa->recovery = json_encode([]);
            $user2fa->save();
        }

        if (!$data && $recoveryEnabled) {
            $secretKey = $google2fa->generateSecretKey();

            $recoveryCodes = $recovery
                ->setCount(config('nova2fa.recovery_codes.count'))
                ->setBlocks(config('nova2fa.recovery_codes.blocks'))
                ->setChars(config('nova2fa.recovery_codes.chars_in_block'))
                ->toArray();

            $data = [
                'recoveryCodes' => $recoveryCodes,
                'secretKey' => $secretKey
            ];

            $request->session()->put('recovery_data', $data);

            $recoveryHashes = $recoveryCodes;

            array_walk($recoveryHashes, function (&$value) {
                $value = password_hash($value, config('nova2fa.recovery_codes.hashing_algorithm'));
            });

            $user2faModel = config('nova2fa.models.user2fa');
            $user2faModel::where('user_id', auth()->user()->id)->delete();

            $user2fa = new $user2faModel();
            $user2fa->user_id = auth()->user()->id;
            $user2fa->google2fa_secret = $secretKey;
            $user2fa->recovery = json_encode($recoveryHashes);
            $user2fa->save();
        }

        $qrCodeUrl = Url::generateGoogleQRCodeUrl(
            'https://chart.googleapis.com/',
            'chart',
            'chs=150x150&chld=M|0&cht=qr&chl=',
            $google2fa->getQRCodeUrl(
                config('app.name'),
                auth()->user()->email,
                $data['secretKey']
            )
        );

        $data['qrCodeUrl'] = $qrCodeUrl;
        $data['recoveryEnabled'] = $recoveryEnabled;

        return Inertia::render('Nova2fa/Register', $data);
    }

    public function verify(Request $request): \Illuminate\Http\JsonResponse {
        $request->validate([
            'code' => ['required', 'max:6', 'min:6'],
        ]);

        if ($this->is2FAValid($request->get('code'))) {
            $authenticator = app(Google2FAAuthenticator::class);
            $authenticator->login();

            return response()->json([
                'redirect' => Nova::url('/')
            ]);
        }

        return response()->json([
            'errors' => [
                'code' => [
                    __('Code is invalid')
                ]
            ]
        ])->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function checkRecovery(Request $request): \Illuminate\Http\JsonResponse {
        $recoveryLength = config('nova2fa.recovery_codes.blocks') *
            (config('nova2fa.recovery_codes.chars_in_block') + 1) - 1;

        $request->validate([
            'recovery_code' => ['required', 'max:' . $recoveryLength, 'min:' . $recoveryLength],
        ]);

        $recoveryCode = $request->get('recovery_code');
        $recoveryHashes = json_decode(auth()->user()->user2fa->recovery, true);

        if ($this->isRecoveryValid($recoveryCode, $recoveryHashes)) {
            $user2faModel = config('nova2fa.models.user2fa');

            // delete 2fa settings for this user
            $user2faModel::where('user_id', auth()->user()->id)->delete();

            return response()->json([
                'redirect' => Nova::url('/nova-2fa/register')
            ]);
        }

        return response()->json([
            'errors' => [
                'recovery_code' => [
                    __('Recovery code is invalid')
                ]
            ]
        ])->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function confirmRegistration(Request $request)
    {
        $request->validate([
            'code' => ['required', 'max:6', 'min:6'],
        ]);

        if ($this->is2FAValid($request->get('code'))) {
            auth()->user()->user2fa->google2fa_enable = 1;
            auth()->user()->user2fa->save();
            $authenticator = app(Google2FAAuthenticator::class);
            $authenticator->login();

            $request->session()->forget('recovery_data');

            return response()->json([
                'redirect' => Nova::url('/')
            ]);
        }

        return response()->json([
            'errors' => [
                'code' => [
                    __('Code is invalid')
                ]
            ]
        ])->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}