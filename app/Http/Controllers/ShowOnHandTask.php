<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\TimeX_SubTask;
use App\TimeX_TimeSheetMobileLog;

use Carbon\Carbon;

class ShowOnHandTask extends Controller
{
    /**
     * @param $id
     * @return \Illuminate\Support\Collection
     */
    public function showonHand($id)
    {
        $totalHoures= 0;
        $onGoingTask = 0;
        $pandingTask = 0;
        $assignedTasks = 0;

       // \DB::connection()->enableQueryLog();
        $onHandTask = TimeX_SubTask::leftJoin('TimeX_JobBased_Tasks as tt','TimeX_SubTasks.BaseTaskID', '=', 'tt.BaseTaskID')
            ->where('EmployeeId',$id)
            ->where('TimeX_SubTasks.TaskStatus','1')
            ->select('SubTaskId','tt.BaseTaskId','AssignedHoursInBaseTask','AssignedHoursInSecondLevelTask','EmployeeId','SubTaskName','JobCode','TimeX_SubTasks.ActualStartDate','TimeX_SubTasks.TaskStatus')
            ->get();
      //  $queries = \DB::getQueryLog();
        //return dd($queries);
        foreach ($onHandTask as $key =>  $value){
            $assignedTasks ++;
            $totalHoures += $value->AssignedHoursInBaseTask + $value->AssignedHoursInSecondLevelTask;
            $workTime = DB::select('Select SUM(DATEDIFF(ss, 0 , ElapsedTimeForTask)) ElapsedTimeForTask From TimeX_TimeSheetMobileLog where SubTaskId=?',[$value->SubTaskId]);
            //dd($workTime[0]->ElapsedTimeForTask);
            if($value->ActualStartDate == null){
                $pandingTask ++;
                $onHandTask_arry[] = array(
                    'SubTaskId' => $value->SubTaskId,
                    'BaseId' => $value->BaseTaskId,
                    'SubTaskName' => $value->SubTaskName,
                    'JobCode' => $value->JobCode,
                    'AssignedHours' =>  gmdate("H:i:s",($value->AssignedHoursInBaseTask + $value-> AssignedHoursInSecondLevelTask)*3600),
                    'TaskUtilizedHours' => '00:00:00',
                    //'ActualStartTime' => $value->ActualStartDate,
                    'ActualStartTime' => '01:00:00',
                    'TaskStatus' => $value->TaskStatus,
                    'Status' => 'pending'
                );
            }else{
                $onGoingTask ++;
                $onHandTask_arry[] = array(
                    'SubTaskId' => $value->SubTaskId,
                    'BaseId' => $value->BaseTaskId,
                    'SubTaskName' => $value->SubTaskName,
                    'JobCode' => $value->JobCode,
                    'AssignedHours' => gmdate("H:i:s",($value->AssignedHoursInBaseTask + $value-> AssignedHoursInSecondLevelTask)*3600),
                    'TaskUtilizedHours' => gmdate("H:i:s",$workTime[0]->ElapsedTimeForTask),
                    //'ActualStartTime' => $value->ActualStartDate,
                    'ActualStartTime' => '01:00:00',
                    'TaskStatus' => $value->TaskStatus,
                    'Status' => 'ongoing'
                );
            }

        }

//        $totalHoures = TimeX_SubTask::where('EmployeeId',$id)
//            ->where('TaskStatus','1')
//            ->value(DB::raw("SUM(AssignedHoursInBaseTask + AssignedHoursInSecondLevelTask)"));
//        dd($totalHoures);

        $totalWork = DB::select('SELECT SUM(DATEDIFF(MINUTE, 0 , ElapsedTimeForTask)) ElapsedTimeForTask FROM TimeX_TimeSheetMobileLog tt LEFT JOIN TimeX_SubTasks ts on tt.SubTaskId = ts.SubTaskId WHERE tt.EmployeeId = ? And ts.TaskStatus = \'1\'',[$id]);

        $lastTask = TimeX_TimeSheetMobileLog::where('EmployeeId',$id)
            ->orderBy('TaskEndDateTime', 'desc')
            ->first();


        $data['result'] = array(
            'employeeId' => $id,
            'tasks' => $onHandTask_arry,
            'assignedTasks' => $assignedTasks,
            'totalHours'=> $totalHoures,
            'utilizedHours'=> round(($totalWork[0]->ElapsedTimeForTask)/60,2),
            'pendingTasks' => $pandingTask,
            //'pendingTasks' => '1',
            'onGoingTasks' => $onGoingTask,
            'balanceHours' => round((($totalHoures*60) - $totalWork[0]->ElapsedTimeForTask)/60,2),
            'latestTask' => $lastTask->SubTaskId
        );
        $data['status'] = array('code' => 200, 'message' => "", 'error' => "");
//
//        $data['eid'] = $id;
//        $data['onHandTask'] = $onHandTask_arry;
//        $data['totalHours'] = $totalHoures;
//        $data['totalWork'] = $totalWork[0]->ElapsedTimeForTask;
        //dd($data);
        return response()->json($data);
    }

    public function subTaskIndex(Request $request){
        $value = [$request->input('EmployeeId'),$request->input('SubTaskId')];
        $subTasksTimeRange[] = DB::select('EXEC subTask ?,?',$value);
        //$totalWorkHours = DB::select('SELECT SUM(DATEDIFF(ss, 0 , ElapsedTimeForTask)) ElapsedTimeForTask FROM TimeX_TimeSheetMobileLog Where EmployeeId = ? And SubTaskId = ?',[$request->input('EmployeeId'),$request->input('SubTaskId')]);
        $subTask =  DB::select('EXEC subTaskData ?,?',$value);
//        dd(gmdate("H:i:s", (int)$totalWorkHours[0]->ElapsedTimeForTask));
       $week[]= array_chunk($subTasksTimeRange[0],7);
       //dd(array_chunk($subTasksTimeRange[0],7));

       // return json_encode( array(1 => array('aa'=>1, 'bbb'=>2), 2 => array('aa'=>1, 'bbb'=>2)));
//dd($week);
$count = count($week[0]);
        for ($i = 1; $i < $count; $i++){
            $dailyTasksList[] = array(
                $i => $week[0][$i]
            );
        }


        //dd($subTask[0]->AssignedHoursInBaseTask);
        $a= json_encode($week);
      // return $dailyTasksList;
       // $b = json_decode($a);

        $data['result'] = array(
            'dailyTasksList' => $dailyTasksList,
            'jobCode' => $subTask[0]->JobCode,
            'subTaskName' => $subTask[0]->SubTaskName,
            'subTaskId' => $request->input('SubTaskId'),
            'assignedStartDate' => $subTask[0]->AssignedStartDate,
            'assignedEndDate' => $subTask[0]->AssignedEndDate,
            'allocatedHours' => gmdate("H:i:s", ($subTask[0]->AssignedHoursInBaseTask + $subTask[0]->AssignedHoursInSecondLevelTask)*3600),
            'utilizedHours' => gmdate("H:i:s", $subTask[0]->ElapsedTimeForTask),
            'balanceHours' => gmdate("H:i:s",(($subTask[0]->AssignedHoursInBaseTask + $subTask[0]->AssignedHoursInSecondLevelTask)*3600) - $subTask[0]->ElapsedTimeForTask)
        );//dd($data);

        if($subTasksTimeRange){
            $data['status'] = array('code' => 200, 'message' => "", 'error' => "");
        } else {
            $data['status'] = array('code' => 400, 'message' => "Something went wrong", 'error' => "Checkin details not saved.");
        }
       // dd($data['result']['dailyTasksList'][3]);
        return response()->json($data);
    }

    public function storeTask(Request $request)
    {
        $checkTask = TimeX_TimeSheetMobileLog::where('SubTaskId',$request->input('SubTaskId'))->orderBy('MobileLog_RecordId', 'DESC')->first();

        $currentDate = date('Y-m-d H:i:s');

      //return $checkTask;
       if(!$checkTask) {
           DB::table('TimeX_SubTasks')
               ->where('SubTaskId', $request->input('SubTaskId'))
               ->update(['ActualStartDate' => $currentDate]);


          // if (status == "start") {
               $newTask = new TimeX_TimeSheetMobileLog();
               $newTask->EmployeeId = $request->input('EmployeeId');
               $newTask->DeviceId = $request->input('DeviceId');
               $newTask->SubTaskId = $request->input('SubTaskId');
               $newTask->TaskStartDateTime = $currentDate;
               $newTask->TaskEndDateTime = '00:00:00';
               $newTask->ElapsedTimeForTask = '00:00:00';
               $newTask->IsSynced = $request->input('IsSynced');
               $newTask->LocalDbId = $request->input('LocalDbId');
               $newTask->SyncedTime = $currentDate;
               $newTask->IsAvailableInTimeSheet = $request->input('IsAvailableInTimeSheet');
               if ($newTask->save()) {
                   $data['status'] = array('code' => 200, 'message' => "Successfully Checked out.", 'error' => "");
               } else {
                   $data['status'] = array('code' => 400, 'message' => "Something went wrong", 'error' => "Checkin details not saved.");
               }
           return response()->json($data);
          // }

//            $newTask = new TimeX_TimeSheetMobileLog();
//            $newTask->EmployeeId = $request->input('EmployeeId');
//            $newTask->DeviceId = $request->input('DeviceId');
//            $newTask->SubTaskId = $request->input('SubTaskId');
//            $newTask->TaskStartDateTime = $firstTime;
//            $newTask->TaskEndDateTime = $currentDate;
//            $newTask->ElapsedTimeForTask = $ElapsedTimeForTask;
//            $newTask->IsSynced = $request->input('IsSynced');
//            $newTask->LocalDbId = $request->input('LocalDbId');
//            $newTask->SyncedTime = $currentDate;
//            $newTask->IsAvailableInTimeSheet = $request->input('IsAvailableInTimeSheet');
//            //dd($newTask);
//           if($newTask->save()){
//               $data['status'] = array('code' => 200, 'message' => "Successfully Checked out.", 'error' => "");
//           } else {
//               $data['status'] = array('code' => 400, 'message' => "Something went wrong", 'error' => "Checkin details not saved.");
//           }

       }else {
               if ( $request->input('IsStopped') == '1') {

                   $newTask = new TimeX_TimeSheetMobileLog();
                   $newTask->EmployeeId = $request->input('EmployeeId');
                   $newTask->DeviceId = $request->input('DeviceId');
                   $newTask->SubTaskId = $request->input('SubTaskId');
                   $newTask->TaskStartDateTime = $currentDate;
                   $newTask->TaskEndDateTime = '00:00:00';
                   $newTask->ElapsedTimeForTask = '00:00:00';
                   $newTask->IsSynced = $request->input('IsSynced');
                   $newTask->LocalDbId = $request->input('LocalDbId');
                   $newTask->SyncedTime = $currentDate;
                   $newTask->IsAvailableInTimeSheet = $request->input('IsAvailableInTimeSheet');
                  // dd($newTask);
                   if ($newTask->save()) {
                       $data['status'] = array('code' => 200, 'message' => "Successfully Checked out.", 'error' => "");
                   } else {
                       $data['status'] = array('code' => 400, 'message' => "Something went wrong", 'error' => "Checkin details not saved.");
                   }
                   return response()->json($data);
               } else {

                   if ($checkTask->TaskEndDateTime == '1900-01-01 00:00:00.000') {

                       $startTime = Carbon::parse($checkTask->TaskStartDateTime);
                       $finishTime = Carbon::parse($currentDate);
                       $totalDuration = $finishTime->diffInSeconds($startTime);
                       $ElapsedTimeForTask = gmdate('H:i:s', $totalDuration);
                      // dd("aa");
                       $newTask = DB::table('TimeX_TimeSheetMobileLog')
                           ->where('TaskStartDateTime', $checkTask->TaskStartDateTime)
                           ->update(['TaskEndDateTime' => $currentDate,'ElapsedTimeForTask' => $ElapsedTimeForTask]);
                       if ($newTask) {
                           $data['status'] = array('code' => 200, 'message' => "Successfully Checked out.", 'error' => "");
                       } else {
                           $data['status'] = array('code' => 400, 'message' => "Something went wrong", 'error' => "Checkin details not saved.");
                       }
                       return response()->json($data);
                   }else {

                       $startTime = Carbon::parse($checkTask->TaskEndDateTime);
                       $finishTime = Carbon::parse($currentDate);
                       $totalDuration = $finishTime->diffInSeconds($startTime);
                       $ElapsedTimeForTask = gmdate('H:i:s', $totalDuration);

                       $newTask = new TimeX_TimeSheetMobileLog();
                       $newTask->EmployeeId = $request->input('EmployeeId');
                       $newTask->DeviceId = $request->input('DeviceId');
                       $newTask->SubTaskId = $request->input('SubTaskId');
                       $newTask->TaskStartDateTime = $checkTask->TaskEndDateTime;
                       $newTask->TaskEndDateTime = $currentDate;
                       $newTask->ElapsedTimeForTask = $ElapsedTimeForTask;
                       $newTask->IsSynced = $request->input('IsSynced');
                       $newTask->LocalDbId = $request->input('LocalDbId');
                       $newTask->SyncedTime = $currentDate;
                       $newTask->IsAvailableInTimeSheet = $request->input('IsAvailableInTimeSheet');
                       //dd($newTask);
                       if ($newTask->save()) {
                           $data['status'] = array('code' => 200, 'message' => "Successfully Checked out.", 'error' => "");
                       } else {
                           $data['status'] = array('code' => 400, 'message' => "Something went wrong", 'error' => "Checkin details not saved.");
                       }
                       return response()->json($data);
                   }
               }


           }


       }

    public function completeTask(Request $request){

        $currentDate = date('Y-m-d H:i:s');
        $time = strtotime($currentDate);
        $firstTime = date("Y-m-d H:i:s", strtotime('-5 minutes', $time));
        $startTime = Carbon::parse($currentDate);
        $finishTime = Carbon::parse($firstTime);
        $totalDuration = $finishTime->diffInSeconds($startTime);
        //$ElapsedTimeForTask = gmdate('H:i:s', $totalDuration);

        $newTask = DB::table('TimeX_SubTasks')
            ->where('SubTaskId',$request->input('SubTaskId'))
            ->update(['ActualEndDate' => date('Y-m-d H:i:s'),'TaskStatus' => '4','LastUpdatedTimeForSync' => date('Y-m-d H:i:s')]);

//        $newTask = new TimeX_TimeSheetMobileLog();
//        $newTask->EmployeeId = $request->input('EmployeeId');
//        $newTask->DeviceId = $request->input('DeviceId');
//        $newTask->SubTaskId = $request->input('SubTaskId');
//        $newTask->TaskStartDateTime = $firstTime;;
//        $newTask->TaskEndDateTime = $currentDate;
//        $newTask->ElapsedTimeForTask = $ElapsedTimeForTask;
//        $newTask->IsSynced = $request->input('IsSynced');
//        $newTask->LocalDbId = $request->input('LocalDbId');
//        $newTask->SyncedTime = $currentDate;
//        $newTask->IsAvailableInTimeSheet = $request->input('IsAvailableInTimeSheet');
        if($newTask){
            $data['status'] = array('code' => 200, 'message' => "Successfully Checked out.", 'error' => "");
        }else {
            $data['status'] = array('code' => 400, 'message' => "Something went wrong", 'error' => "Checkin details not saved.");
        }
		
		return response()->json($data);

    }
}
