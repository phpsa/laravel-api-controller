<?php

namespace Phpsa\LaravelApiController\Http\Request\Traits;

trait MethodRules
{

    public function rules()
    {
        if (! $this->route()) {
            return [];
        }

        $methodRules = $this->route()->getActionMethod() . 'Rules';

        return method_exists($this, $methodRules)
        ? array_merge($this->commonRules(), $this->$methodRules())
        : $this->commonRules();
    }

    public function commonRules(): array
    {
        return [];
    }
}
