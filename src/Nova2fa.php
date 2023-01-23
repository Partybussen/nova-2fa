<?php

namespace Partybussen\Nova2fa;

use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;
use PragmaRX\Google2FA\Google2FA as G2fa;
use PragmaRX\Recovery\Recovery;
use PragmaRX\Google2FA\Support\Url;
use Inertia\Inertia;

class Nova2fa extends Tool
{
    /**
     * Perform any tasks that need to happen when the tool is booted.
     *
     * @return void
     */
    public function boot()
    {
        Nova::script('nova-2fa', __DIR__.'/../dist/js/tool.js');
        Nova::style('nova-2fa', __DIR__.'/../dist/css/tool.css');
    }

    /**
     * Build the menu that renders the navigation links for the tool.
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function menu(Request $request)
    {
        //
    }

    private function is2FAValid(): bool
    {
        $secret = Request::get('secret');
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

    public function confirmRegistration() {
        if ($this->is2FAValid()) {
            auth()->user()->user2fa->google2fa_enable = 1;
            auth()->user()->user2fa->save();
            $authenticator = app(Google2FAAuthenticator::class);
            $authenticator->login();

            return Inertia::location(config('nova.path'));
        }

        return Inertia::location('/2fa/register');
    }

    public function authenticate(Request $request)
    {
        $data = [];

        if ($this->is2FAValid()) {
            $authenticator = app(Google2FAAuthenticator::class);
            $authenticator->login();

            return Inertia::location(config('nova.path'));
        }

        $data['error'] = __('One time password is invalid.');
        $data['recoveryUrl'] = '/2fa/recover';

        return view('google2fa::authenticate', $data);
    }

    private function checkRecovery(Request $request)
    {
        $data = [];

        $recover = Request::get('recover');

        if ($this->isRecoveryValid($recover, json_decode(auth()->user()->user2fa->recovery, true))) {
            // delete 2fa settings for this user
            $user2faModel::where('user_id', auth()->user()->id)->delete();

            // redirect to register page
            return Inertia::location('/2fa/register');
        }

        $data['error'] = __('Recovery code is invalid.');

        return view('google2fa::recover', $data);
    }

    public function showAuthenticate(Request $request)
    {
        $data = [
            'recoveryUrl' => '/2fa/recover'
        ];

        return response(view('google2fa::authenticate', $data));
    }

    public function showRecovery()
    {
        $data = [
            'authenticationUrl' => '/2fa/authenticate'
        ];

        return response(view('google2fa::recovery', $data));
    }

    public function showRegister(Request $request)
    {
        $google2fa = new G2fa();
        $recovery = new Recovery();

        $data = $request->session()->get('recovery_data');

        if (!$data) {
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

        $google2fa_url = Url::generateGoogleQRCodeUrl(
            'https://chart.googleapis.com/',
            'chart',
            'chs=200x200&chld=M|0&cht=qr&chl=',
            $google2fa->getQRCodeUrl(
                config('app.name'),
                auth()->user()->email,
                $data['secretKey']
            )
        );

        $data['google2fa_url'] = $google2fa_url;

        return response(view('google2fa::register', $data));
    }
}
