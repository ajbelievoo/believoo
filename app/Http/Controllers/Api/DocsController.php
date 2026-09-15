<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DocsController extends Controller
{
    public function index()
    {
        return view('api.docs', ['specUrl' => route('api.docs.openapi')]);
    }

    public function openapi(): JsonResponse
    {
        return response()->json($this->buildSpec());
    }

    protected function buildSpec(): array
    {
        $apiUrl = url('/api');

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Believoo Public API',
                'description' => 'Production REST API for Believoo services. Authentication is via API key in the Authorization or X-API-Key header.',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'Believoo Support',
                    'email' => 'support@believoo.com',
                ],
            ],
            'servers' => [
                ['url' => $apiUrl, 'description' => 'Believoo API v1'],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'description' => 'API key token. Generate keys in the admin panel or client dashboard.',
                    ],
                    'ApiKeyHeader' => [
                        'type' => 'apiKey',
                        'in' => 'header',
                        'name' => 'X-API-Key',
                    ],
                ],
            ],
            'security' => [
                ['bearerAuth' => []],
                ['ApiKeyHeader' => []],
            ],
            'paths' => [
                '/v1/health' => [
                    'get' => [
                        'tags' => ['System'],
                        'summary' => 'Health check',
                        'description' => 'Public health endpoint.',
                        'security' => [],
                        'responses' => [
                            '200' => [
                                'description' => 'API is healthy',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'status' => 'healthy',
                                            'service' => 'BelieVoo API',
                                            'version' => '1.0.0',
                                            'timestamp' => now()->toIso8601String(),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                '/v1/user' => [
                    'get' => [
                        'tags' => ['User'],
                        'summary' => 'Get authenticated user profile',
                        'description' => 'Returns the user associated with the API key. Requires `user:read` scope.',
                        'responses' => [
                            '200' => [
                                'description' => 'User profile',
                                'content' => [
                                    'application/json' => [
                                        'example' => [
                                            'success' => true,
                                            'data' => [
                                                'id' => 1,
                                                'name' => 'Admin',
                                                'email' => 'admin@believoo.com',
                                                'phone' => '+919627521770',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            '401' => ['description' => 'Missing, invalid or insufficient API key'],
                            '403' => ['description' => 'Insufficient scope'],
                        ],
                    ],
                ],
                '/v1/servers' => [
                    'get' => [
                        'tags' => ['Servers'],
                        'summary' => 'List servers',
                        'description' => 'List all servers accessible to the API key owner. Requires `servers:read` scope.',
                        'responses' => [
                            '200' => ['description' => 'List of servers'],
                            '401' => ['description' => 'Unauthenticated'],
                            '403' => ['description' => 'Insufficient scope'],
                        ],
                    ],
                ],
                '/v1/servers/{id}' => [
                    'get' => [
                        'tags' => ['Servers'],
                        'summary' => 'Get server details',
                        'description' => 'Requires `servers:read` scope.',
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Server details'],
                            '404' => ['description' => 'Server not found'],
                        ],
                    ],
                ],
                '/v1/servers/{id}/start' => [
                    'post' => [
                        'tags' => ['Servers'],
                        'summary' => 'Start a server',
                        'description' => 'Requires `servers:control` scope.',
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Start initiated'],
                            '403' => ['description' => 'Insufficient scope'],
                        ],
                    ],
                ],
                '/v1/servers/{id}/stop' => [
                    'post' => [
                        'tags' => ['Servers'],
                        'summary' => 'Stop a server',
                        'description' => 'Requires `servers:control` scope.',
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ],
                        'responses' => ['200' => ['description' => 'Stop initiated']],
                    ],
                ],
                '/v1/servers/{id}/restart' => [
                    'post' => [
                        'tags' => ['Servers'],
                        'summary' => 'Restart a server',
                        'description' => 'Requires `servers:control` scope.',
                        'parameters' => [
                            ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ],
                        'responses' => ['200' => ['description' => 'Restart initiated']],
                    ],
                ],
                '/v1/tickets' => [
                    'get' => [
                        'tags' => ['Tickets'],
                        'summary' => 'List support tickets',
                        'description' => 'Requires `tickets:read` scope.',
                        'responses' => ['200' => ['description' => 'List of tickets']],
                    ],
                    'post' => [
                        'tags' => ['Tickets'],
                        'summary' => 'Create support ticket',
                        'description' => 'Requires `tickets:write` scope.',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'subject' => ['type' => 'string'],
                                            'message' => ['type' => 'string'],
                                            'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
                                        ],
                                        'required' => ['subject', 'message'],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => ['description' => 'Ticket created'],
                        ],
                    ],
                ],
                '/v1/tickets/{ticketId}' => [
                    'get' => [
                        'tags' => ['Tickets'],
                        'summary' => 'Get ticket details',
                        'description' => 'Requires `tickets:read` scope.',
                        'parameters' => [
                            ['name' => 'ticketId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ],
                        'responses' => ['200' => ['description' => 'Ticket details']],
                    ],
                ],
                '/v1/tickets/{ticketId}/messages' => [
                    'post' => [
                        'tags' => ['Tickets'],
                        'summary' => 'Add ticket message',
                        'description' => 'Requires `tickets:write` scope.',
                        'parameters' => [
                            ['name' => 'ticketId', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                        ],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'message' => ['type' => 'string'],
                                        ],
                                        'required' => ['message'],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => ['200' => ['description' => 'Message added']],
                    ],
                ],
                '/v1/currencies' => [
                    'get' => [
                        'tags' => ['Currencies'],
                        'summary' => 'List currencies',
                        'description' => 'Requires `currencies:read` scope.',
                        'responses' => ['200' => ['description' => 'List of currencies']],
                    ],
                ],
                '/v1/currencies/rates' => [
                    'get' => [
                        'tags' => ['Currencies'],
                        'summary' => 'Exchange rates',
                        'description' => 'Requires `currencies:read` scope.',
                        'responses' => ['200' => ['description' => 'Exchange rates']],
                    ],
                ],
                '/v1/currencies/convert' => [
                    'post' => [
                        'tags' => ['Currencies'],
                        'summary' => 'Convert price',
                        'description' => 'Requires `currencies:read` scope.',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'amount' => ['type' => 'number'],
                                            'from' => ['type' => 'string'],
                                            'to' => ['type' => 'string'],
                                        ],
                                        'required' => ['amount', 'from', 'to'],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => ['200' => ['description' => 'Converted amount']],
                    ],
                ],
                '/v1/licenses' => [
                    'get' => [
                        'tags' => ['Licenses'],
                        'summary' => 'List licenses',
                        'description' => 'Requires `licenses:read` scope.',
                        'responses' => ['200' => ['description' => 'List of licenses']],
                    ],
                ],
                '/v1/licenses/order' => [
                    'post' => [
                        'tags' => ['Licenses'],
                        'summary' => 'Order a license',
                        'description' => 'Requires `licenses:write` scope.',
                        'responses' => ['201' => ['description' => 'License ordered']],
                    ],
                ],
                '/v1/server-imports' => [
                    'get' => [
                        'tags' => ['Server Imports'],
                        'summary' => 'List server imports',
                        'description' => 'Requires `server-imports:read` scope.',
                        'responses' => ['200' => ['description' => 'List of imports']],
                    ],
                ],
                '/audio-mixer/health' => [
                    'get' => [
                        'tags' => ['Audio Mixer'],
                        'summary' => 'Audio mixer health',
                        'description' => 'Public health check.',
                        'security' => [],
                        'responses' => ['200' => ['description' => 'Health status']],
                    ],
                ],
                '/ghc-settings' => [
                    'get' => [
                        'tags' => ['GHC'],
                        'summary' => 'GHC public branding settings',
                        'description' => 'Public GHC branding values.',
                        'security' => [],
                        'responses' => ['200' => ['description' => 'GHC settings']],
                    ],
                ],
            ],
            'tags' => [
                ['name' => 'System', 'description' => 'Health and status endpoints'],
                ['name' => 'User', 'description' => 'User profile'],
                ['name' => 'Servers', 'description' => 'Server management and control'],
                ['name' => 'Tickets', 'description' => 'Support tickets'],
                ['name' => 'Currencies', 'description' => 'Currency and exchange rates'],
                ['name' => 'Licenses', 'description' => 'License management'],
                ['name' => 'Server Imports', 'description' => 'External server migration'],
                ['name' => 'Audio Mixer', 'description' => 'Audio mixer health'],
                ['name' => 'GHC', 'description' => 'GHC branding'],
            ],
        ];
    }
}
