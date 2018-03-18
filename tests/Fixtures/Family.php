<?php

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

class Family extends Model
{
    public function vehicles()
    {
        return $this->hasOne(Vehicle::class);
    }

    public function members()
    {
        return $this->hasOne(Person::class);
    }      
}