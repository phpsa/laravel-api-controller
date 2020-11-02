<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as Res;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait HasResponse
{
    /**
     * HTTP header status code.
     */
    protected $statusCode = Res::HTTP_OK;

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param $message
     *
     * @return self
     */
    public function setStatusCode($statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Respond with a given item.
     *
     * @param $item
     *
     * @return mixed Response|jsonResponse
     */
    protected function respondWithOne($item, $code = null, $headers = [])
    {
        return $this->respondWithResource($this->/** @scrutinizer ignore-call */getResourceSingle(), $item, $code, $headers);
    }

    /**
     * Respond with a given collection of items.
     *
     * @param mixed $items
     * @param mixed $code
     * @param array $headers
     *
     * @return mixed Response|jsonResponse
     */
    protected function respondWithMany($items, $code = null, $headers = [])
    {
        return $this->respondWithResource($this->/** @scrutinizer ignore-call */getResourceCollection(), $items, $code, $headers);
    }

    /**
     * Sends the response through a resource Object.
     *
     * @param mixed $resource Phpsa\LaravelApiController\Http\Resources\ApiResponse|\Phpsa\LaravelApiController\Http\Resources\ApiCollection
     * @param mixed $data
     * @param mixed $code
     * @param array $headers
     */
    protected function respondWithResource($resource, $data, $code = null, $headers = [])
    {
        return $resource::make($data)
            ->response()
            ->setStatusCode($code ?? $this->getStatusCode())
            ->withHeaders($headers);
    }

    /**
     * Created Response.
     *
     * @param mixed $item
     * @param mixed $code
     * @param array $headers
     *
     * @return mixed Response|jsonResponse
     */
    protected function respondItemCreated($item, $code = 201, $headers = [])
    {
        return $this->respondWithOne($item, $code, $headers);
    }

    /**
     * Respond with a given response.
     *
     * @param mixed $data
     * @param mixed $code
     * @param array $headers
     *
     * @return mixed Response|jsonResponse
     */
    protected function respond($data, $code = null, $headers = [])
    {
        return response()->json(
            $data,
            $code ? $code : $this->getStatusCode(),
            $headers
        );
    }

    /**
     * Respond with a no content reponse.
     *
     * @return mixed Response|jsonResponse
     */
    protected function respondNoContent()
    {
        return response('', Res::HTTP_NO_CONTENT);
    }

    /**
     * Response with the current error.
     *
     * @param string $message
     * @param array  $errors
     *
     * @return mixed Response|jsonResponse
     */
    protected function respondWithError($message, array $errors = [])
    {
        $response = [
            'message' => $message,
        ];

        if (count($errors)) {
            $response['errors'] = $errors;
        }

        return $this->respond($response);
    }

    /**
     * Generate a Response with a 403 HTTP header and a given message.
     *
     * @param string|null $message
     * @param array|null  $errors
     *
     * @throws AuthorizationException
     * @return void
     */
    protected function errorForbidden(?string $message = null, ?array $errors = null)
    {
        if ($errors) {
            Log::error($message, $errors);
        }
        throw new AuthorizationException($message);
    }

    /**
     * Generate a Response with a 500 HTTP header and a given message.
     *
     * @param string $message
     * @param array|null  $errors
     *
     * @throws HttpException
     * @return void
     */
    protected function errorInternalError(string $message = 'Internal Error', ?array $errors = null)
    {
        if ($errors) {
            Log::error($message, $errors);
        }
        throw new HttpException(500, $message);
    }

    /**
     * Generate a Response with a 404 HTTP header and a given message.
     *
     * @param string $message
     * @param array|null  $errors
     *
     * @throws NotFoundHttpException
     * @return void
     */
    protected function errorNotFound($message = 'Resource Not Found', ?array $errors = null)
    {
        if ($errors) {
            Log::error($message, $errors);
        }
        throw new NotFoundHttpException($message);
    }

    /**
     * Generate a Response with a 401 HTTP header and a given message.
     *
     * @param string|null $message
     * @param array|null  $errors
     *
     * @throws AuthorizationException
     * @return void
     */
    protected function errorUnauthorized($message = null, ?array $errors = null)
    {
        if ($errors) {
            Log::error($message, $errors);
        }
        throw new AuthorizationException($message);
    }

    /**
     * Generate a Response with a 400 HTTP header and a given message.
     *
     * @param string $message
     * @param array|null  $errors
     *
     * @throws BadRequestHttpException
     * @return void
     */
    protected function errorWrongArgs($message = 'Wrong Arguments', ?array $errors = null)
    {
        if ($errors) {
            Log::error($message, $errors);
        }
        throw new BadRequestHttpException($message);
    }

    /**
     * Generate a Response with a 501 HTTP header and a given message.
     *
     * @param string $message
     * @param array|null  $errors
     *
     * @throws HttpException
     * @return void
     */
    protected function errorNotImplemented($message = 'Not implemented', ?array $errors = null)
    {
        if ($errors) {
            Log::error($message, $errors);
        }
        throw new HttpException(501, $message);
    }

    protected function handleIndexResponse($items)
    {
        return $this->respondWithMany($items);
    }

    protected function handleStoreResponse($item)
    {
        return $this->respondItemCreated($item);
    }

    protected function handleShowResponse($item)
    {
        return $this->respondWithOne($item);
    }

    protected function handleUpdateResponse($item)
    {
        return $this->respondWithOne($item);
    }

    protected function handleDestroyResponse($id)
    {
        return $this->respondNoContent();
    }
}
