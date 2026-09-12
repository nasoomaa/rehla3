<?php

declare(strict_types=1);

namespace Rehla\Api\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Rehla\Api\Errors\ProblemDetailsFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class FormatProblemDetails
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (AuthenticationException $e) {
            return ProblemDetailsFactory::make(401, 'UNAUTHENTICATED');
        } catch (ValidationException $e) {
            return ProblemDetailsFactory::make(422, 'VALIDATION_FAILED', errors: $e->errors());
        } catch (AuthorizationException $e) {
            return ProblemDetailsFactory::make(403, 'UNAUTHORIZED', detail: $e->getMessage() ?: null);
        } catch (HttpExceptionInterface $e) {
            $code = match ($e->getStatusCode()) {
                401 => 'UNAUTHENTICATED',
                403 => 'UNAUTHORIZED',
                404 => 'NOT_FOUND',
                409 => 'CONFLICT',
                422 => 'VALIDATION_FAILED',
                429 => 'TOO_MANY_REQUESTS',
                default => 'HTTP_ERROR',
            };

            return ProblemDetailsFactory::make($e->getStatusCode(), $code, detail: $e->getMessage() ?: null);
        } catch (\Throwable $e) {
            return ProblemDetailsFactory::make(500, 'INTERNAL_ERROR', detail: config('app.debug') ? $e->getMessage() : null);
        }

        if ($response->getStatusCode() === 401 && ! str_contains((string) $response->headers->get('Content-Type'), 'problem+json')) {
            return ProblemDetailsFactory::make(401, 'UNAUTHENTICATED');
        }

        if ($response->getStatusCode() === 403 && ! str_contains((string) $response->headers->get('Content-Type'), 'problem+json')) {
            return ProblemDetailsFactory::make(403, 'UNAUTHORIZED');
        }

        if ($response->getStatusCode() === 404 && ! str_contains((string) $response->headers->get('Content-Type'), 'problem+json')) {
            return ProblemDetailsFactory::make(404, 'NOT_FOUND');
        }

        if ($response->getStatusCode() === 422 && ! str_contains((string) $response->headers->get('Content-Type'), 'problem+json')) {
            $content = json_decode($response->getContent() ?: '{}', true);
            $errors = is_array($content['errors'] ?? null) ? $content['errors'] : [];

            return ProblemDetailsFactory::make(
                status: 422,
                code: 'VALIDATION_FAILED',
                type: 'https://rehla.example/problems/validation-error',
                errors: $errors,
            );
        }

        if ($response->getStatusCode() === 429 && ! str_contains((string) $response->headers->get('Content-Type'), 'problem+json')) {
            return ProblemDetailsFactory::make(429, 'TOO_MANY_REQUESTS');
        }

        return $response;
    }
}
