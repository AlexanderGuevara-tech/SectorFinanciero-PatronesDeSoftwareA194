<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/accounts/transfer',
    operationId: 'transferAccountFunds',
    tags: ['Accounts'],
    summary: 'Transfer funds between accounts',
    description: "Browser-facing POST route in Laravel's web middleware group. It requires an authenticated session and the manage-accounts permission. Submit a valid CSRF token in X-CSRF-TOKEN; do not disable CSRF protection. Validation failures and transfer failures redirect back with session errors, while committed transfers redirect to the source account.",
    security: [['sessionCookie' => []]],
    parameters: [new OA\Parameter(
        name: 'X-CSRF-TOKEN',
        in: 'header',
        required: true,
        description: "CSRF token for the current web session. Obtain it from the application's rendered CSRF token and send it using the header expected by Laravel's web middleware.",
        schema: new OA\Schema(type: 'string'),
    )],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['source_account_id', 'destination_account_id', 'currency', 'amount', 'request_key'],
            properties: [
                new OA\Property(property: 'source_account_id', type: 'integer', example: 12, description: 'Existing source account ID.'),
                new OA\Property(property: 'destination_account_id', type: 'integer', example: 24, description: 'Existing destination account ID; must differ from the source.'),
                new OA\Property(property: 'currency', type: 'string', enum: ['COP', 'USD'], example: 'COP'),
                new OA\Property(property: 'amount', type: 'string', pattern: '^\\d+\\.\\d{2}$', example: '25.00', description: 'Decimal string formatted with exactly two fractional digits; business rules may reject the amount.'),
                new OA\Property(property: 'request_key', type: 'string', maxLength: 255, example: 'transfer-request-123', description: 'Idempotency request key.'),
            ],
        ),
    ),
    responses: [
        new OA\Response(response: 302, description: 'Browser redirect after a committed transfer, or back with validation/operation errors. Unauthenticated requests redirect to login.'),
        new OA\Response(response: 403, description: 'Authenticated session lacks manage-accounts permission.'),
        new OA\Response(response: 419, description: 'CSRF token is missing or invalid.'),
    ],
)]
class TransferEndpoint {}
