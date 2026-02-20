<?php

namespace App\Traits;

trait HasEncodedId
{
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where('id', decodeId($value))->firstOrFail();
    }
}
