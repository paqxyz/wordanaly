<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    public $table = 'log';

    public function site()
    {
        $this->belongsTo(Site::class);
    }

}
