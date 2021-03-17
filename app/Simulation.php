<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Simulation extends Model
{
    public function dfr()
    {
        return $this->hasMany('App\Dfr');
    }

    public function mgr()
    {
        return $this->hasMany('App\Mgr');
    }
}
