<?php

namespace App\Exceptions;

use App\Traits\ApiResponses;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use function Pest\Laravel\instance;

class ApiExceptionHandler
{
    use ApiResponses;

    protected $handlers = [
        AuthenticationException::class => 'handleAuthentication',
        AccessDeniedHttpException::class => 'handleAuthentication',
        AuthorizationException::class => 'handleAuthorization',
        ValidationException::class => 'handleValidation',
        ModelNotFoundException::class => 'handleNotFound',
        NotFoundHttpException::class => 'handleNotFound',
        MethodNotAllowedHttpException::class => 'handleMethodNotAllowed',
        HttpException::class => 'handleHttp',
        QueryException::class => 'handleQuery',
    ];

    private function handleAuthentication(AuthenticationException|AccessDeniedHttpException $authenticationException)
    {
        $this->logException($authenticationException, 'Authentication failed');

        return [
            [
                'type' => $this->getExceptionType($authenticationException),
                'status' => 401,
                'message' => 'Authentication required. Please provide valid credentials.',
                'timestamp' => now()->toISOString(),
            ]
        ];
    }

    // Alternative handling for notAuthorize method in ApiResponses.php file
    private function handleAuthorization(AuthorizationException $authorizationException)
    {
        $this->logException($authorizationException, 'Authorization failed');

        return [
            [
                'type' => $this->getExceptionType($authorizationException),
                'status' => 403,
                'message' => 'You do not have permission to perform this action.',
                'timestamp' => now()->toISOString(),
            ]
        ];
    }

    private function handleValidation(ValidationException $validationException)
    {
        $errors = [];

        foreach ($validationException->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $errors[] = [
                    'field' => $field,
                    'message' => $message,
                ];
            }
        }

        $this->logException($validationException, 'Validation failed', ['errors' => $errors]);

        return [
            [
                'type' => $this->getExceptionType($validationException),
                'status' => 422,
                'message' => 'The provided data is invalid.',
                'timestamp' => now()->toISOString(),
                'validation_errors' => $errors,
            ]
        ];
    }

    private function handleNotFound(ModelNotFoundException|NotFoundHttpException $notFoundException, Request $request)
    {
        $message = $notFoundException instanceof ModelNotFoundException
            ? 'The requested resource was not found.'
            : "The requested endpoint '{$request->getRequestUri()}' was not found.";

        $source = $notFoundException instanceof ModelNotFoundException ? $notFoundException->getModel() : 'Not Found';

        return [
            [
                'type' => $this->getExceptionType($notFoundException),
                'status' => 404,
                'message' => $message,
                'timestamp' => now()->toISOString(),
                'source' => $source
            ]
        ];
    }

    private function handleMethodNotAllowed(MethodNotAllowedHttpException $methodNotAllowedHttpException, Request $request)
    {
        $this->logException($methodNotAllowedHttpException, 'Method not allowed');

        return [
            [
                'type' => $this->getExceptionType($methodNotAllowedHttpException),
                'status' => 405,
                'message' => "The {$request->method()} method is not allowed for this endpoint.",
                'timestamp' => now()->toISOString(),
                'allowed_methods' => $methodNotAllowedHttpException->getHeaders()['Allow'] ?? 'Unknown',
            ]
        ];
    }

    private function handleHttp(HttpException $httpException)
    {
        $this->logException($httpException, 'HTTP exception occured');

        return [
            [
                'type' => $this->getExceptionType($httpException),
                'status' => $httpException->getStatusCode(),
                'message' => $httpException->getMessage() ?: 'An HTTP error occurred.',
                'timestamp' => now()->toISOString(),
            ]
        ];
    }

    private function handleQuery(QueryException $queryException)
    {
        $this->logException($queryException, 'Database query failed', ['sql' => $queryException->getSql()]);

        // Handle specific database constraint violations
        $errorCode = $queryException->errorInfo[1] ?? null;

        switch ($errorCode) {
            case 1451: // Foreign key constraint violation
                return [
                    [
                        'type' => $this->getExceptionType($queryException),
                        'status' => 409,
                        'message' => 'Cannot delete this resource because it is referenced by other records.',
                        'timestamp' => now()->toISOString(),
                    ]
                ];

            case 1052: // Duplicate entry
                return [
                    [
                        'type' => $this->getExceptionType($queryException),
                        'status' => 409,
                        'message' => 'A record with this information already exists.',
                        'timestamp' => now()->toISOString(),
                    ]
                ];

            default:
                return [
                    [
                        'type' => $this->getExceptionType($queryException),
                        'status' => 500,
                        'message' => 'A database error occurred. Please try again later.',
                        'timestamp' => now()->toISOString(),
                    ]
                ];
        }
    }

    public function globalExceptionResponses(Throwable $throwable, Request $request)
    {
        $className = get_class($throwable);

        if (array_key_exists($className, $this->handlers)) {
            $method = $this->handlers[$className];
            return $this->error($this->$method($throwable, $request));
        }

        $index = strrpos($className, '\\');

        return $this->error([
            [
                'type' => substr($className, $index + 1),
                'status' => 0,
                'message' => $throwable->getMessage(),
                'source' => 'Line: ' . $throwable->getLine() . ': ' . $throwable->getFile()
            ]
        ]);
    }

    private function getExceptionType(Throwable $e): string
    {
        $className = basename(str_replace('\\', '/', get_class($e)));
        return $className;
    }

    private function logException(Throwable $throwable, string $message, array $context = []): void
    {
        $logContext = array_merge([
            'exception' => get_class($throwable),
            'message' => $throwable->getMessage(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip' => request()->ip(),
        ], $context);

        Log::warning($message, $logContext);
    }
}