<?php

namespace Phpsa\LaravelApiController\Events;

use Illuminate\Queue\SerializesModels;

class Deleted
{
    use SerializesModels;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $record;

    /**
     * the request object.
     *
     * @var \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $record
     * @param \Illuminate\Http\Request|\Illuminate\Foundation\Http\FormRequest $request
     */
    public function __construct($record, $request)
    {
        $this->record = $record;
        $this->request = $request;
    }
}
