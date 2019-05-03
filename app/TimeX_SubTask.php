<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TimeX_SubTask extends Model
{
    protected $table = 'TimeX_SubTasks';
    protected $primaryKey = "SubTaskId";
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
//    protected $fillable = [
//        'SubTaskId','AssignedHoursInBaseTask','AssignedHoursInSecondLevelTask','EID','SubTaskName'
//    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
//    protected $hidden = [
//    ];
}
