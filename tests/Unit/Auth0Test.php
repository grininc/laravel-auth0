<?php

declare(strict_types=1);

use Auth0\Laravel\Auth0;
use Auth0\Laravel\Cache\LaravelCachePool;
use Auth0\Laravel\Store\LaravelSession;
use Auth0\SDK\Contract\Auth0Interface as SdkContract;
use Auth0\SDK\Auth0 as SDKAuth0;
use Auth0\SDK\Configuration\SdkConfiguration;
use Auth0\SDK\Contract\API\ManagementInterface;
use Auth0\SDK\Store\MemoryStore;
use Psr\Cache\CacheItemPoolInterface;

uses()->group('auth0');

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
    $this->config = $this->sdk->configuration();
    $this->session = $this->config->getSessionStorage();
});

it('returns a Management API class', function (): void {
    expect($this->laravel->management())->toBeInstanceOf(ManagementInterface::class);
});

it('can get/set the configuration', function (): void {
    expect($this->laravel->getConfiguration())->toBeInstanceOf(SdkConfiguration::class);

    $configuration = new SdkConfiguration(['strategy' => 'none', 'domain' => uniqid() . '.auth0.test']);
    $this->laravel->setConfiguration($configuration);
    expect($this->laravel->getConfiguration())->toBe($configuration);

    $domain = uniqid() . '.auth0.test';
    $configuration->setDomain($domain);
    expect($this->laravel->getConfiguration()->getDomain())->toBe($domain);

    $configuration = new SdkConfiguration(['strategy' => 'none', 'domain' => uniqid() . '.auth0.test']);
    $this->laravel->setConfiguration($configuration);
    expect($this->laravel->getConfiguration())->toBe($configuration);

    $sdk = $this->laravel->getSdk();
    $configuration = new SdkConfiguration(['strategy' => 'none', 'domain' => uniqid() . '.auth0.test']);
    $this->laravel->setConfiguration($configuration);
    expect($this->laravel->getConfiguration())->toBe($configuration);
    expect($sdk->configuration())->toBe($configuration);
});

it('can get the sdk credentials', function (): void {
    expect($this->laravel->getCredentials())
        ->toBeNull();

    $this->session->set('user', ['sub' => 'hello|world']);
    $this->session->set('idToken', uniqid());
    $this->session->set('accessToken', uniqid());
    $this->session->set('accessTokenScope', [uniqid()]);
    $this->session->set('accessTokenExpiration', time() - 1000);

    // As we manually set the session values, we need to refresh the SDK state to ensure it's in sync.
    $this->sdk->refreshState();

    expect($this->laravel->getCredentials())
        ->toBeObject()
        ->toHaveProperty('accessToken', $this->session->get('accessToken'))
        ->toHaveProperty('accessTokenScope', $this->session->get('accessTokenScope'))
        ->toHaveProperty('accessTokenExpiration', $this->session->get('accessTokenExpiration'))
        ->toHaveProperty('idToken', $this->session->get('idToken'))
        ->toHaveProperty('user', $this->session->get('user'));
});

it('can get/set the SDK', function (): void {
    expect($this->laravel->getSdk())->toBeInstanceOf(SdkContract::class);

    $sdk = new SDKAuth0(['strategy' => 'none']);
    $this->laravel->setSdk($sdk);
    expect($this->laravel->getSdk())->toBeInstanceOf(SdkContract::class);
});

it('can reset the internal static state', function (): void {
    $cache = spl_object_id($this->laravel->getSdk());

    unset($this->laravel); // Force the object to be destroyed. Static state will remain.

    $laravel = app('auth0');
    $updated = spl_object_id($laravel->getSdk());
    expect($cache)->toBe($updated);

    $laravel->reset(); // Reset the static state.

    $laravel = app('auth0');
    $updated = spl_object_id($laravel->getSdk());
    expect($cache)->not->toBe($updated);
});
