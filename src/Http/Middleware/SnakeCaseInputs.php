<?php

namespace Phpsa\LaravelApiController\Http\Middleware;

use Closure;
use Phpsa\LaravelApiController\Helpers;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class SnakeCaseInputs.
 *
 * This middleware makes sure all incoming request parameters are snake cased for the application
 */
class SnakeCaseInputs
{
    /**
     * HTTP Methods we want to consider for transforming URL query params.
     */
    protected const RELEVANT_METHODS_QUERY = ['POST', 'PATCH', 'PUT', 'DELETE', 'GET'];

    /**
     * HTTP methods we want to consider for transorming request body input.
     */
    protected const RELEVANT_METHODS_BODY = ['POST', 'PATCH', 'PUT', 'DELETE'];

    /**
     * Handle an incoming request.
     *
     * Replace all request parameter keys with snake_cased equivilents
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Query string parameters
        if (in_array($request->method(), self::RELEVANT_METHODS_QUERY)) {
            $this->processParamBag($request->query);
        }
        // Input parameters
        if (in_array($request->method(), self::RELEVANT_METHODS_BODY)) {
            $this->processParamBag($request->request);

            if ($request->isJson()) {
                $this->processParamBag(/* @scrutinizer ignore-type */$request->json());
            }
        }

        return $next($request);
    }

    /**
     * Process parameters within a ParameterBag to snake_case the keys.
     *
     * @param \Symfony\Component\HttpFoundation\ParameterBag $bag
     */
    protected function processParamBag(ParameterBag $bag): void
    {
        $bag->replace(
            Helpers::snakeCaseArrayKeys($bag->all())
        );
    }
}
