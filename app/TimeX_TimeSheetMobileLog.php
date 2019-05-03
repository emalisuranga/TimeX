<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TimeX_TimeSheetMobileLog extends Model
{
    protected $table = 'TimeX_TimeSheetMobileLog';
    protected $primaryKey = "SubTaskId";
    public $incrementing = false;
}
