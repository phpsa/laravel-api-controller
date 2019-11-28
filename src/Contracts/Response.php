<?php

namespace Phpsa\LaravelApiController\Contracts;

use Symfony\Component\HttpFoundation\Response as Res;

trait Response
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
        return $this->respondWithResource($this->resourceSingle, $item, $code, $headers);
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
        return $this->respondWithResource($this->resourceCollection, $items, $code, $headers);
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
     * @param $message
     * @param array  $errors
     *
     * @return mixed Response|jsonResponse
     */
    protected function errorForbidden($message = 'Forbidden', array $errors = [])
    {
        return $this->setStatusCode(403)->respondWithError($message, $errors);
    }

    /**
     * Generate a Response with a 500 HTTP header and a given message.
     *
     * @param string $message
     * @param array  $errors
     *
     * @return mixed Response|jsonResponse
     */
    protected function errorInternalError($message = 'Internal Error', array $errors = [])
    {
        return $this->setStatusCode(500)->respondWithError($message, $errors);
    }

    /**
     * Generate a Response with a 404 HTTP header and a given message.
     *
     * @param string $message
     * @param array  $errors
     *
     * @return mixed Response|jsonResponse
     */
    protected function errorNotFound($message = 'Resource Not Found', array $errors = [])
    {
        return $this->setStatusCode(404)->respondWithError($message, $errors);
    }

    /**
     * Generate a Response with a 401 HTTP header and a given message.
     *
     * @param string $message
     * @param array  $errors
     *
     * @return mixed Response|jsonResponse
     */
    protected function errorUnauthorized($message = 'Unauthorized', array $errors = [])
    {
        return $this->setStatusCode(401)->respondWithError($message, $errors);
    }

    /**
     * Generate a Response with a 400 HTTP header and a given message.
     *
     * @param string $message
     * @param array  $errors
     *
     * @return mixed Response|jsonResponse
     */
    protected function errorWrongArgs($message = 'Wrong Arguments', array $errors = [])
    {
        return $this->setStatusCode(400)->respondWithError($message, $errors);
    }

    /**
     * Generate a Response with a 501 HTTP header and a given message.
     *
     * @param string $message
     * @param array  $errors
     *
     * @return mixed Response|jsonResponse
     */
    protected function errorNotImplemented($message = 'Not implemented', array $errors = [])
    {
        return $this->setStatusCode(501)->respondWithError($message, $errors);
    }
}
