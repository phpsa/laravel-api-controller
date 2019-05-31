<?php

namespace Phpsa\LaravelApiController\Http\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as LaravelController;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Phpsa\LaravelApiController\UriParser;
use \Illuminate\Http\Response as Res;
use Phpsa\LaravelApiController\Exceptions\ApiException;
use Illuminate\Support\Facades\Schema;

abstract class Controller extends LaravelController
{

	/**
     * HTTP header status code.
     *
     * @var int
     */
	protected $statusCode = Res::HTTP_OK;

	/**
     * Eloquent model instance.
     *
     * @var \Illuminate\Database\Eloquent\Model;
     */
    protected $model;

    /**
     * Repository instance
     *
     * @var  App\Repositories\BaseRepository
     */
    protected $repository;

    /**
     * Illuminate\Http\Request instance.
     *
     * @var Request
     */
	protected $request;

	/**
	 * UriParser instance
	 *
	 * @var UriParser
	 */
	protected $uriParser;

    /**
     * Do we need to unguard the model before create/update?
     *
     * @var bool
     */
	protected $unguard = false;

    /**
     * Number of items displayed at once if not specified.
     * There is no limit if it is 0 or false.
     *
     * @var int|bool
     */
	protected $defaultLimit = 25;

    /**
     * Maximum limit that can be set via $_GET['limit'].
     *
     * @var int|bool
     */
	protected $maximumLimit = false;

    /**
     * Resource key for an item.
     *
     * @var string
     */
	protected $resourceKeySingular = 'data';

    /**
     * Resource key for a collection.
     *
     * @var string
     */
    protected $resourceKeyPlural = 'data';

	/**
	 * Holds teh current authed user object
	 *
	 * @var User
	 * @author Craig Smith <craig.smith@customd.com>
	 */
	protected $user;

	/**
	 * Default Fields to response with
	 *
	 * @var array
	 * @author Craig Smith <craig.smith@customd.com>
	 */
	protected $fields = ['*'];


	/**
	 * Constructor.
	 *
	 * @param Request $request
	 */
    public function __construct(Request $request)
    {
        $this->model = $this->model();
        $this->repository = $this->repository();
		$this->request = $request;
		$this->uriParser = new UriParser($request);
		$this->user = auth()->user();
    }

	 /**
     * Eloquent model.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
	abstract protected function model();

	 /**
     * Eloquent model.
     *
     * @return App\Repositories\BaseRepository
     */
	abstract protected function repository();



	protected function _parseWith(){
		$with   = $this->request->input('with');
		if ($with !== null) {
			$this->repository->with(explode(",", $with));
		}
	}

	protected function _parseSort(){
		$sort     = $this->request->input('sort');

        if ($sort) {
            $sorts = explode(",", $sort);
            foreach ($sorts as $sort) {
                if (empty($sort)) {
                    continue;
                }
                $sortP = explode(" ", $sort);

                $sortF = $sortP[0];
                $sortD = !empty($sortP[1]) && strtolower($sortP[1]) == 'asc' ? 'asc' : 'desc';
                $this->repository->orderBy($sortF, $sortD);

            }
        }
	}


	protected function _parseWhere(){
		$where = $this->uriParser->whereParameters();
		if ($where) {
            foreach ($where as $whr) {
                switch ($whr['type']) {
                    case "In":
                        if (!empty($whr['values'])) {
                            $this->repository->whereIn($whr['key'], $whr['values']);
                        }
                        break;
                    case "NotIn":
                        if (!empty($whr['values'])) {
                            $this->repository->whereNotIn($whr['key'], $whr['values']);
                        }
                        break;
                    case "Basic":
                        $this->repository->where($whr['key'], $whr['value'], $whr['operator']);

                        break;
                }
            }
        }
	}

	/**
	* Display a listing of the resource.
	* GET /api/{resource}.
	*
	* @return Response
	*/
   public function index(){

		$this->_parseWith();
		$this->_parseSort();
		$this->_parseWhere();

		$fields = empty($this->request->input('fields')) ? $this->fields : explode(",", $this->request->input('fields'));

		$limit = $this->request->has('limit') ? $this->request->input('limit') : $this->defaultLimit;
		if($this->maximumLimit && ( $limit > $this->maximumLimit || !$limit ) ){
			$limit = $this->maximumLimit;
		}

		return $limit ? $this->respondWithPagination(
			$this->respository->paginate($limit, $fields)
		) : $this->respondWithMany(
			$this->repository->get($fields)
		);

   }

    /**
     * Store a newly created resource in storage.
     * POST /api/{resource}.
     *
     * @return Response
     */
    public function store()
    {
		$data = $this->request->all();

        if (!$data) {
            return $this->errorWrongArgs('Empty request');
		}

		$validator = Validator::make($data, $this->rulesForCreate());

        if ($validator->fails()) {
            return $this->errorWrongArgs($validator->messages());
		}

		$columns = Schema::getColumnListing($this->model->getTable());

		$insert = array_intersect_key($data, array_flip($columns));

		$this->unguardIfNeeded();

		$item = $this->model->create($columns);

        return $this->respondCreated($item->id);
	}

	/**
     * Display the specified resource.
     * GET /api/{resource}/{id}.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
		$this->_parseWith();
		$fields = empty($this->request->input('fields')) ? $this->fields : explode(",", $this->request->input('fields'));
		$item = $this->repository->getById($id);

        if (!$item) {
            return $this->errorNotFound();
        }
        return $this->respondWithItem($item);
	}

	  /**
     * Update the specified resource in storage.
     * PUT /api/{resource}/{id}.
     *
     * @param int $id
     *
     * @return Response
     */
    public function update($id)
    {
		$data = $this->request->all();

        if (!$data) {
            return $this->errorWrongArgs('Empty request');
		}

		$item = $this->repository->getById($id);
		if (!$item) {
            return $this->errorNotFound();
        }

		$validator = Validator::make($data, $this->rulesForUpdate($item->id));

        if ($validator->fails()) {
            return $this->errorWrongArgs($validator->messages());
		}

		$columns = Schema::getColumnListing($this->model->getTable());

		$fields = array_intersect_key($data, array_flip($columns));

        $this->unguardIfNeeded();
        $item->fill($fields);
        $item->save();
        return $this->respondWithItem($item);
	}

	/**
     * Remove the specified resource from storage.
     * DELETE /api/{resource}/{id}.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
		$item = $this->repository->getById($id);
        if (!$item) {
            return $this->errorNotFound();
        }
        $item->delete();
        return response()->json(['message' => 'Deleted']);
	}

	 /**
     * Show the form for creating the specified resource.
     *
     * @return Response
     */
    public function create()
    {
        return $this->errorNotImplemented();
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        return $this->errorNotImplemented();
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
    /**
     * @param $message
     * @return json Res
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
     * @return mixed
     */
    protected function respondWithOne($item)
    {
		return $this->respond([
			'status'      					=> 'success',
			'status_code' 					=> Res::HTTP_OK,
            $this->resourceKeySingular      => $data,
        ]);
	}

	 /**
     * Respond with a given collection of items.
     *
     * @param $items
     * @param int $skip
     * @param int $limit
     *
     * @return mixed
     */
    protected function respondWithMany($items)
    {
        return $this->respond([
            'status'      					=> 'success',
            'status_code' 					=> Res::HTTP_OK,
            $this->resourceKeyPlural        => $data,
        ]);
    }


	/**
	 * Created Response
	 *
	 * @param [int] $id of insterted data
	 * @param [type] $message
	 *
	 * @return void
	 * @author Craig Smith <craig.smith@customd.com>
	 */
    public function respondCreated($id = null, $message = NULL)
    {
		$response = [
			'status'      		=> 'success',
			'status_code' 		=> Res::HTTP_CREATED
		];
		if($message !== NULL){
			$response['message'] = $message;
		}
		if($id !== NULL){
			if(is_scalar($id)){
				$response[$this->resourceKeySingular] = $id;
			}else{
				$response[$this->resourceKeyPlural] = $id;
			}
		}
        return $this->respond($response);
    }

    /**
     * @param Paginator $paginate
     * @param $data
     * @return mixed
     */
    protected function respondWithPagination(Paginator $paginator)
    {

        return $this->respond([
            'status'      					=> 'success',
            'status_code' 					=> Res::HTTP_OK,
            'paginator' => [
                'total_count'  => $paginator->total(),
                'total_pages'  => ceil($paginator->total() / $paginator->perPage()),
                'current_page' => $paginator->currentPage(),
                'limit'        => $paginator->perPage(),
			],
			$this->resourceKeyPlural => $paginator->items()
        ]);
    }

	/**
     * Respond with a given response.
     *
     * @param mixed $data
     * @param array $headers
     *
     * @return mixed
     */
    protected function respond($data, $headers = [])
    {
        return response()->json($data, $this->getStatusCode(), $headers);
    }

	/**
     * Response with the current error.
     *
     * @param string $message
     *
     * @return mixed
     */
    protected function respondWithError($message)
    {
		return $this->respond([
            'status'      => 'error',
            'status_code' =>  $this->statusCode,
            'message'     => $message,
        ]);
	}

	/**
     * Generate a Response with a 403 HTTP header and a given message.
     *
     * @param $message
     *
     * @return Response
     */
    protected function errorForbidden($message = 'Forbidden')
    {
        return $this->setStatusCode(403)->respondWithError($message);
    }
    /**
     * Generate a Response with a 500 HTTP header and a given message.
     *
     * @param string $message
     *
     * @return Response
     */
    protected function errorInternalError($message = 'Internal Error')
    {
        return $this->setStatusCode(500)->respondWithError($message);
    }
    /**
     * Generate a Response with a 404 HTTP header and a given message.
     *
     * @param string $message
     *
     * @return Response
     */
    protected function errorNotFound($message = 'Resource Not Found')
    {
        return $this->setStatusCode(404)->respondWithError($message);
    }
    /**
     * Generate a Response with a 401 HTTP header and a given message.
     *
     * @param string $message
     *
     * @return Response
     */
    protected function errorUnauthorized($message = 'Unauthorized')
    {
        return $this->setStatusCode(401)->respondWithError($message);
    }
    /**
     * Generate a Response with a 400 HTTP header and a given message.
     *
     * @param string$message
     *
     * @return Response
     */
    protected function errorWrongArgs($message = 'Wrong Arguments')
    {
        return $this->setStatusCode(400)->respondWithError($message);
    }
    /**
     * Generate a Response with a 501 HTTP header and a given message.
     *
     * @param string $message
     *
     * @return Response
     */
    protected function errorNotImplemented($message = 'Not implemented')
    {
        return $this->setStatusCode(501)->respondWithError($message);
    }

	/**
     * Get the validation rules for create.
     *
     * @return array
     */
    protected function rulesForCreate()
    {
        return [];
    }
    /**
     * Get the validation rules for update.
     *
     * @param int $id
     *
     * @return array
     */
    protected function rulesForUpdate($id)
    {
        return [];
	}


	/**
     * Unguard eloquent model if needed.
     */
    protected function unguardIfNeeded()
    {
        if ($this->unguard) {
            $this->model->unguard();
        }
    }


    /**
     * Check if the user has one or more roles
     *
     * @param mixed $role role name or array of role names
     *
     * @return bool
     * @author Craig Smith <craig.smith@customd.com>
     * @copyright 2018 Custom D
     * @since 1.0.0
     */
    protected function hasRole($role)
    {
        return $this->user && $this->user->hasRole($role);
    }

    /**
     * Checks if user has all the passed roles
     *
     * @param array $roles
     *
     * @return bool
     * @author Craig Smith <craig.smith@customd.com>
     * @copyright 2018 Custom D
     * @since 1.0.0
     */
    protected function hasAllRoles($roles)
    {
        return $this->user && $this->user->hasRole($roles, true);
    }



}
