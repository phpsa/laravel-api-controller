<?php

namespace Phpsa\LaravelApiController\Traits;

use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response as Res;

trait Response
{
    /**
     * HTTP header status code.
     *
     * @var int
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
     * @return self
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Respond with a given item.
     *
     * @param $item
     *
     * @return Res
     */
    protected function respondWithOne($item, $code = null, $headers = [])
    {
        return $this->resourceSingle::make($item)
            ->response()
            ->setStatusCode($code ?? $this->getStatusCode())
            ->withHeaders($headers);
    }

    /**
     * Respond with a given collection of items.
     *
     * @param $items
     * @param int $skip
     * @param int $limit
     *
     * @return Res
     */
    protected function respondWithMany($items, $code = null, $headers = [])
    {
        return $this->resourceCollection::make($items)
            ->response()
            ->setStatusCode($code ?? $this->getStatusCode())
            ->withHeaders($headers);
    }

    /**
     * Created Response.
     *
     * @param mixed  $id      id of insterted data
     * @param string $message message to respond with
     *
     * @return Res
     */
    protected function respondItemCreated($item, $code = 201, $headers = [])
    {
        return $this->respondWithOne($item, $code, $headers);
    }

    /**
     * Created Response.
     *
     * @param mixed  $id      id of insterted data
     * @param string $message message to respond with
     *
     * @deprecated 0.4.0 - to be removed by 0.5.0 @see self::respond() || self::respondItemCreated ||
     *
     * @return Res
     */
    public function respondCreated($id = null, $message = null)
    {
        $response = [];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($id !== null) {
            if (is_scalar($id)) {
                $response[$this->resourceKeySingular] = $id;
            } else {
                $response[$this->resourceKeyPlural] = $id;
            }
        }

        return $this->respond($response);
    }

    /**
     * @param LengthAwarePaginator $paginate
     * @param $data
     * @return Res
     *
     * @deprecated 0.5.0 - to be removed by 0.6.0
     */
    protected function respondWithPagination(LengthAwarePaginator $paginator)
    {
        return $this->resourceCollection::make($paginator);
    }

    /**
     * Respond with a given response.
     *
     * @param mixed $data
     * @param array $headers
     *
     * @return Res
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
     * Respond with a given response.
     *
     * @param mixed $data
     * @param array $headers
     *
     * @return Res
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
     * @return Res
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
     * @return Res
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
     * @return Res
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
     * @return Res
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
     * @return Res
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
     * @return Res
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
     * @return Res
     */
    protected function errorNotImplemented($message = 'Not implemented', array $errors = [])
    {
        return $this->setStatusCode(501)->respondWithError($message, $errors);
    }
}
