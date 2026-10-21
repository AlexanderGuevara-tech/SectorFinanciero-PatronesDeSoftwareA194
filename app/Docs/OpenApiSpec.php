<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Banco Core',
    version: '1.0.0',
    description: 'Web-session endpoints for Banco Core. The transfer endpoint uses Laravel web middleware and is not a token-based API.',
)]
#[OA\Server(url: '/')]
#[OA\SecurityScheme(
    securityScheme: 'sessionCookie',
    type: 'apiKey',
    in: 'cookie',
    name: 'sistema-bancario-core-session',
    description: 'Authenticated Laravel web session cookie (the default derived from APP_NAME); it may be overridden by SESSION_COOKIE. The user must also have the manage-accounts permission.',
)]
class OpenApiSpec {}
