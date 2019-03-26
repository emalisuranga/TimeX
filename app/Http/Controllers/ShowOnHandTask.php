<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ShowOnHandTask extends Controller
{
    /**
     * @param $id
     * @return \Illuminate\Support\Collection
     */
    public function show($id)
    {
        $totalHoures = null;
        $subTaskId = [];
        $workTime = null;
        $onHandTask = DB::table('TimeX_SubTasks')
            ->join('timex_jobcodebase_tasks', 'TimeX_SubTasks.BaseTaskID', '=', 'timex_jobcodebase_tasks.BaseTaskID')
            ->where([
            ['EID',$id],
            ['TaskStatus','1']
        ])->get();
        foreach ($onHandTask as $on) {
            $totalHoures += json_decode($on->AssignedHoursInBaseTask + $on->AssignedHoursInSecondLevelTask);
            //$subTaskName = json_decode($on->SubTaskId);
            array_push($subTaskId,$on->SubTaskId);
        }

        foreach ($subTaskId as $subid){
            $workTime = DB:: select('SELECT SEC_TO_TIME( SUM(time_to_sec(timex_timesheet.ElapsedTimeForTask))) FROM timex_timesheet WHERE timex_timesheet.SubTaskId = ?',['S3']);
            //return $workTime;
        }
        return $workTime;
    }

    public function subTaskIndex($id){

        $workTime = DB:: select('SELECT SEC_TO_TIME( SUM(time_to_sec(timex_timesheet.ElapsedTimeForTask))) FROM timex_timesheet WHERE timex_timesheet.SubTaskId = ?',[$id]);
        return $workTime;
    }

    public function storeTask(Request $request)
    {
//        $EMPID = $request->input('EMPID');
//        $SubTaskId = $request->input('SubTaskId');
//        $DeviceId = $request->input('DeviceId');
//        $variable = DB::table('table_name')->insert([
//            ['firstname' => $request->firstname, 'lastname' => $request->lastname, 'subject' => $request->subject]
//        ]);
//        return $variable;
return $request;

    }
}
