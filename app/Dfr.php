<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Dfr extends Model
{
    public function simulation()
    {
        return $this->belongsTo('App\Simulation');
    }

    public function mgr()
    {
        return $this->hasOne('App\Mgr');
    }
}
