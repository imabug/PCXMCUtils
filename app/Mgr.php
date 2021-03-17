<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Mgr extends Model
{
    public function simulation()
    {
        return $this->belongsTo('App\Simulation');
    }

    public function dfr()
    {
        return $this->belongsTo('App\Dfr');
    }
}
