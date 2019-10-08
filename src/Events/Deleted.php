<?php

namespace Phpsa\LaravelApiController\Events;

use Illuminate\Queue\SerializesModels;

class Deleted
{
    use SerializesModels;

    /**
     * The authenticated user.
     *
     * @var Illuminate\Database\Eloquent\Model
     */
    public $record;

    /**
     * Create a new event instance.
     *
     * @param  Illuminate\Database\Eloquent\Model  $record
     * @return void
     */
    public function __construct($record)
    {
        $this->record = $record;
    }
}
