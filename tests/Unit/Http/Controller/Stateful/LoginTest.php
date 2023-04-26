<?php

declare(strict_types=1);

use Auth0\SDK\Configuration\SdkConfiguration;
use Illuminate\Support\Facades\Route;
use Auth0\Laravel\Http\Controller\Stateful\Login;

uses()->group('stateful', 'controller', 'controller.stateful', 'controller.stateful.login');

beforeEach(function (): void {
    $this->secret = uniqid();

    config([
        'auth0.strategy' => SdkConfiguration::STRATEGY_REGULAR,
        'auth0.domain' => uniqid() . '.auth0.com',
        'auth0.clientId' => uniqid(),
        'auth0.clientSecret' => $this->secret,
        'auth0.cookieSecret' => uniqid(),
        'auth0.routes.home' => '/' . uniqid(),
    ]);

    $this->laravel = app('auth0');
    $this->guard = auth('testGuard');
    $this->sdk = $this->laravel->getSdk();

    $this->validSession = [
        'auth0_session_user' => ['sub' => 'hello|world'],
        'auth0_session_idToken' => uniqid(),
        'auth0_session_accessToken' => uniqid(),
        'auth0_session_accessTokenScope' => [uniqid()],
        'auth0_session_accessTokenExpiration' => time() + 60,
    ];

    Route::get('/login', Login::class);
});

it('redirects to the home route if an incompatible guard is active', function (): void {
    config($config = [
        'auth.defaults.guard' => 'web',
        'auth.guards.testGuard' => null,
    ]);

    $this->get('/login')
         ->assertRedirect(config('auth0.routes.home'));
});

it('redirects to the home route when a user is already logged in', function (): void {
    config($config = [
        'auth0.routes.home' => '/' . uniqid()
    ]);

    $this->withSession($this->validSession)
         ->get('/login')
            ->assertRedirect(config('auth0.routes.home'));
});

it('redirects to the Universal Login Page', function (): void {
    $this->get('/login')
         ->assertRedirectContains('/authorize');
});
