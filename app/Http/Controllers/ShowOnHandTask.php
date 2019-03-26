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
    public function showonHand($id)
    {
        $onHandTask = DB::table('TimeX_SubTasks')->select('SubTaskId','AssignedHoursInBaseTask','AssignedHoursInSecondLevelTask',	'EID','SubTaskName')
            ->join('timex_jobcodebase_tasks', 'TimeX_SubTasks.BaseTaskID', '=', 'timex_jobcodebase_tasks.BaseTaskID')
            ->where([
            ['EID',$id],
            ['TaskStatus','1']
        ])->get();

        //dd($onHandTask);
        $totalHoures = DB::select('SELECT SUM(AssignedHoursInBaseTask + AssignedHoursInSecondLevelTask) as total FROM `timex_subtasks` WHERE EID = ? AND TaskStatus = ?',[$id,'1']);
        $totalWork = DB::select('SELECT sum(t.time) as totalWork FROM (SELECT SubTaskName,tt.SubTaskId as SubTaskIdas,sum(ElapsedTimeForTask) as time FROM `timex_timesheet` as tt left join timex_subtasks as ts on tt.SubTaskId = ts.SubTaskId group by tt.SubTaskId) as t');
        $pandingTask = DB::select('SELECT tt.SubTaskId FROM `timex_subtasks` as tt left join timex_timesheet as ts on tt.SubTaskId = ts.SubTaskId WHERE EID = \'1003\' AND TaskStatus = \'1\' AND ts.SubTaskId  IS NULL');
        //dd($onHandTask);
        $data['onHandTask'] = $onHandTask;
        $data['totalHoures'] = $totalHoures;
        $data['totalWork'] = $totalWork;
        $data['pandingTask'] = $pandingTask;
        dd($data);
        //return response()->json($data);
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
