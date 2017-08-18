<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    public $table = 'site';
    public $timestamps = false;

    public function log(){
        return $this->hasMany(Log::class);
    }


}
