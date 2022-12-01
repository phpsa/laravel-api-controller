<?php

namespace Phpsa\LaravelApiController\Http\Api\Contracts;

use Illuminate\Database\Eloquent\Model;
use Phpsa\LaravelApiController\Events\Created;
use Phpsa\LaravelApiController\Events\Updated;
use Phpsa\LaravelApiController\Events\Deleted;
use Phpsa\LaravelApiController\Events\Restored;

trait HasEvents
{
    protected function triggerCreatedEvent(Model $item): void
    {
        event(new Created($item, $this->request));
    }

    protected function triggerUpdatedEvent(Model $item): void
    {
        event(new Updated($item, $this->request));
    }

    protected function triggerDeletedEvent(Model $item): void
    {
        event(new Deleted($item, $this->request));
    }

    protected function triggerRestoredEvent(Model $item): void
    {
        event(new Restored($item, $this->request));
    }
}
