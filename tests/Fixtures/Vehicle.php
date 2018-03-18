<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    public function family()
    {
        return $this->belongsTo(Family::class);
    }    
}