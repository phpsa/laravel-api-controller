<?php

namespace Phpsa\LaravelApiController\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
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
                $this->processParamBag(/** @scrutinizer ignore-type */$request->json());
            }
        }

        return $next($request);
    }

    /**
     * Process parameters within a ParameterBag to snake_case the keys.
     *
     * @param ParameterBag $bag
     */
    protected function processParamBag(ParameterBag $bag)
    {
        $parameters = $bag->all();

        if (! empty($parameters) && count($parameters) > 0) {
            $parameters = $this->snakeCaseArrayKeys($parameters);
            $bag->replace($parameters);
        }
    }

    protected function snakeCaseArrayKeys(array $array, $levels = null)
    {
        foreach (array_keys($array) as $key) {
            // Get a reference to the value of the key (avoid copy)
            // Then remove that array element
            $value = &$array[$key];
            unset($array[$key]);
            // Transform key
            $transformedKey = $this->snake($key);
            // Recurse
            if (is_array($value) && (is_null($levels) || --$levels > 0)) {
                $value = $this->snakeCaseArrayKeys($value, $levels);
            }
            // Store the transformed key with the referenced value
            $array[$transformedKey] = $value;
            // We'll be dealing with some large values, so memory cleanup is important
            unset($value);
        }

        return $array;
    }

    protected function snake($value)
    {
        if (strtoupper($value) === $value) {
            return $value;
        }
        $value = Str::snake($value);
        // Extra things which Str::snake doesn't do, but maybe should
        $value = str_replace('-', '_', $value);
        $value = preg_replace('/__+/', '_', $value);

        return $value;
    }
}
