<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Phpsa\LaravelApiController\Repository\BaseRepository;

trait HasRepository
{
    /**
     * Repository instance.
     *
     * @var mixed|BaseRepository
     */
    protected $repository;

    /**
     * Creates our repository linkage.
     */
    protected function makeRepository()
    {
        $this->repository = BaseRepository::withModel($this->/** @scrutinizer ignore-call */model());
    }
}
