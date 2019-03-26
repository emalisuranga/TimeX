<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubTaskController extends Controller
{
    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $onHandTask = DB::table('TimeX_SubTasks')->select( )
            ->join('timex_jobcodebase_tasks', 'TimeX_SubTasks.BaseTaskID', '=', 'timex_jobcodebase_tasks.BaseTaskID')
            ->where([
                ['EID',$id],
                ['TaskStatus','1']
            ])->get();
        return $onHandTask;
    }

    public function subTaskIndex($id){

            $workTime = DB:: select('SELECT SEC_TO_TIME( SUM(time_to_sec(timex_timesheet.ElapsedTimeForTask))) FROM timex_timesheet WHERE timex_timesheet.SubTaskId = ?',[$id]);
            return $workTime;
        }


    public function storeTask(Request $req)
    {
        $name = $req->input('name');
        $variable = DB::table('table_name')->insert([
            ['firstname' => $req->firstname, 'lastname' => $req->lastname, 'subject' => $req->subject]
        ]);
        return $variable;


    }
}
