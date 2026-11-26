<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class SwaggerController extends Controller
{
    /**
     * Render the interactive Swagger UI page for the application API, redirecting to login in production.
     */
    public function ui(): View|RedirectResponse
    {
        if (app()->isProduction()) {
            return to_route('login');
        }

        return view('swagger');
    }

    /**
     * Return the OpenAPI 3.0.3 JSON specification for the application API endpoints, redirecting to login in production.
     */
    public function spec(): JsonResponse|RedirectResponse
    {
        if (app()->isProduction()) {
            return to_route('login');
        }

        return response()->json([
            'openapi' => '3.0.3',
            'info' => [
                'title' => (string) config('app.name', 'Laravel').' API',
                'version' => '1.0.0',
                'description' => 'REST API documentation powered by Laravel Sanctum.',
            ],
            'servers' => [
                [
                    'url' => url('/'),
                    'description' => 'Current Server',
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Sanctum Token',
                    ],
                ],
            ],
            'paths' => [
                '/api/auth/register' => [
                    'post' => [
                        'tags' => ['Authentication'],
                        'summary' => 'Register a new user',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['name', 'email', 'password', 'password_confirmation'],
                                        'properties' => [
                                            'name' => ['type' => 'string', 'example' => 'John Doe'],
                                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                                            'password' => ['type' => 'string', 'format' => 'password', 'example' => 'password123'],
                                            'password_confirmation' => ['type' => 'string', 'format' => 'password', 'example' => 'password123'],
                                            'device_name' => ['type' => 'string', 'example' => 'mobile-app'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '201' => ['description' => 'Registration successful, returns Bearer token and user profile.'],
                            '422' => ['description' => 'Validation error.'],
                        ],
                    ],
                ],
                '/api/auth/login' => [
                    'post' => [
                        'tags' => ['Authentication'],
                        'summary' => 'Login and issue Sanctum Bearer token',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['email', 'password'],
                                        'properties' => [
                                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'test@example.com'],
                                            'password' => ['type' => 'string', 'format' => 'password', 'example' => 'password'],
                                            'device_name' => ['type' => 'string', 'example' => 'swagger-ui'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Login successful, returns Bearer token and user profile.'],
                            '422' => ['description' => 'Invalid credentials.'],
                        ],
                    ],
                ],
                '/api/auth/forgot-password' => [
                    'post' => [
                        'tags' => ['Authentication'],
                        'summary' => 'Send password reset link to email',
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['email'],
                                        'properties' => [
                                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'test@example.com'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Password reset link sent.'],
                            '422' => ['description' => 'Unable to send reset link or validation error.'],
                        ],
                    ],
                ],
                '/api/auth/me' => [
                    'get' => [
                        'tags' => ['Authentication'],
                        'summary' => 'Get currently authenticated user',
                        'security' => [['bearerAuth' => []]],
                        'responses' => [
                            '200' => ['description' => 'Authenticated user profile and roles.'],
                            '401' => ['description' => 'Unauthenticated.'],
                        ],
                    ],
                ],
                '/api/auth/logout' => [
                    'post' => [
                        'tags' => ['Authentication'],
                        'summary' => 'Revoke current Sanctum token',
                        'security' => [['bearerAuth' => []]],
                        'responses' => [
                            '200' => ['description' => 'Logged out successfully.'],
                            '401' => ['description' => 'Unauthenticated.'],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
