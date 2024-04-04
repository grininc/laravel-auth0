<?php

declare(strict_types=1);

namespace Auth0\Laravel\Http\Middleware\Stateless;

use Auth0\Laravel\Middleware\{AuthorizeMiddlewareAbstract, AuthorizeMiddlewareContract};

/**
 * @deprecated 7.8.0 This middleware is no longer required. Please migrate to using Auth0\Laravel\Guards\AuthorizationGuard, and use Laravel's standard `auth` middleware instead.
 *
 * @api
 */
final class Authorize extends AuthorizeMiddlewareAbstract implements AuthorizeMiddlewareContract
{
}
