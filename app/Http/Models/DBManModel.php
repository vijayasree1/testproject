<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class DBManModel extends Model
{
    public function __construct(array $attributes = [])
    {
        $this->visible = array_merge($this->visible, array_keys($this->maps));
        $this->appends = array_merge($this->appends, array_keys($this->maps));
        parent::__construct($attributes);
    }
}

