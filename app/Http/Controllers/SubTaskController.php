<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\TimeX_SubTask;
use App\TimeX_TimeSheetMobileLog;

class SubTaskController extends Controller
{
    public function showonHand($id)
    {
        $totalHoures= 0;
        $onGoingTask = 0;
        $pandingTask = 0;
        $assignedTasks = 0;

        $onHandTask = TimeX_SubTask::leftJoin('TimeX_JobBased_Tasks as tt','TimeX_SubTasks.BaseTaskID', '=', 'tt.BaseTaskID')
            ->where('EmployeeId',$id)
            ->where('TimeX_SubTasks.TaskStatus','1')
            ->select('SubTaskId','tt.BaseTaskId','AssignedHoursInBaseTask','AssignedHoursInSecondLevelTask','EmployeeId','SubTaskName','JobCode','TimeX_SubTasks.ActualStartDate','TimeX_SubTasks.TaskStatus')
            ->get();

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
            'onGoingTasks' => $onGoingTask,
            'balanceHours' => round((($totalHoures*60) - $totalWork[0]->ElapsedTimeForTask)/60,2),
            'latestTask' => $lastTask->SubTaskId
        );
        $data['status'] = array('code' => 200, 'message' => "", 'error' => "");

        return response()->json($data);
    }

    public function subTaskIndex(Request $request){

        $value = [$request->input('EmployeeId'),$request->input('SubTaskId')];
        $subTasksTimeRange[] = DB::select('EXEC subTask ?,?',$value);
        $subTask =  DB::select('EXEC subTaskData ?,?',$value);

        $data['result'] = array(
            'dailyTaskWork' => array_chunk($subTasksTimeRange[0],7),
            'jobCode' => $subTask[0]->JobCode,
            'subTaskName' => $subTask[0]->SubTaskName,
            'subTaskId' => $request->input('SubTaskId'),
            'assignedStartDate' => $subTask[0]->AssignedStartDate,
            'assignedEndDate' => $subTask[0]->AssignedEndDate,
            'allocatedHours' => gmdate("H:i:s", ($subTask[0]->AssignedHoursInBaseTask + $subTask[0]->AssignedHoursInSecondLevelTask)*3600),
            'utilizedHours' => gmdate("H:i:s", $subTask[0]->ElapsedTimeForTask),
            'balanceHours' => gmdate("H:i:s",(($subTask[0]->AssignedHoursInBaseTask + $subTask[0]->AssignedHoursInSecondLevelTask)*3600) - $subTask[0]->ElapsedTimeForTask)
        );

        if($subTasksTimeRange){
            $data['status'] = array('code' => 200, 'message' => "", 'error' => "");
        } else {
            $data['status'] = array('code' => 400, 'message' => "Something went wrong", 'error' => "Checkin details not saved.");
        }
        return response()->json($data);
        }

    public function storeTask(Request $request)
    {
//        $checkTask = TimeX_TimeSheetMobileLog::where('SubTaskId',$request->input('SubTaskId'))->first();
//
//        if($checkTask){
//            DB::table('TimeX_SubTasks')
//                ->where('SubTaskId',$request->input('SubTaskId'))
//                ->update(['ActualStartDate' => date('Y-m-d H:i:s')]);
//
//            $newTask = new TimeX_TimeSheetMobileLog();
//            $newTask->EmployeeId = $request->input('SubTaskId');
//            $newTask->DeviceId = $request->input('DeviceId');
//            $newTask->SubTaskId = $request->input('SubTaskId');
//            $newTask->TaskStartDateTime = $request->input('TaskStartDateTime');
//            $newTask->TaskEndDateTime = $request->input('TaskEndDateTime');
//            $newTask->ElapsedTimeForTask = $request->input('ElapsedTimeForTask');
//            $newTask->IsSynced = $request->input('IsSynced');
//            $newTask->LocalDbId = $request->input('LocalDbId');
//            $newTask->SyncedTime = $request->input('SyncedTime');
//            $newTask->IsAvailableInTimeSheet = $request->input('IsAvailableInTimeSheet');
//            if($newTask->save()){
//                $data['status'] = array('code' => 200, 'message' => "Successfully Checked out.", 'error' => "");
//            } else {
//                $data['status'] = array('code' => 400, 'message' => "Something went wrong", 'error' => "Checkin details not saved.");
//            }
//
//
//        }else{
//            $newTask = new TimeX_TimeSheetMobileLog();
//            $newTask->EmployeeId = $request->input('SubTaskId');
//            $newTask->DeviceId = $request->input('DeviceId');
//            $newTask->SubTaskId = $request->input('SubTaskId');
//            $newTask->TaskStartDateTime = $request->input('TaskStartDateTime');
//            $newTask->TaskEndDateTime = $request->input('TaskEndDateTime');
//            $newTask->ElapsedTimeForTask = $request->input('ElapsedTimeForTask');
//            $newTask->IsSynced = $request->input('IsSynced');
//            $newTask->LocalDbId = $request->input('LocalDbId');
//            $newTask->SyncedTime = $request->input('SyncedTime');
//            $newTask->IsAvailableInTimeSheet = $request->input('IsAvailableInTimeSheet');
//            if($newTask->save()){
//                $data['status'] = array('code' => 200, 'message' => "Successfully Checked out.", 'error' => "");
//            } else {
//                $data['status'] = array('code' => 400, 'message' => "Something went wrong", 'error' => "Checkin details not saved.");
//            }
//        }
//
//        return response()->json($data);

        $checkTask = TimeX_TimeSheetMobileLog::where('SubTaskId',$request->input('SubTaskId'))->orderBy('MobileLog_RecordId', 'DESC')->first();
        $currentDate = date('Y-m-d H:i:s');

        if(!$checkTask) {
            DB::table('TimeX_SubTasks')
                ->where('SubTaskId', $request->input('SubTaskId'))
                ->update(['ActualStartDate' => $currentDate]);

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
        }else {
            if ($request->input('status') == "start") {
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
            } else {
                $startTime = Carbon::parse($checkTask->TaskStartDateTime);
                $finishTime = Carbon::parse($currentDate);
                $totalDuration = $finishTime->diffInSeconds($startTime);
                $ElapsedTimeForTask = gmdate('H:i:s', $totalDuration);

                if ($checkTask->TaskEndDateTime == '1900-01-01 00:00:00.000') {
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
                    dd($newTask);
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

        DB::table('TimeX_SubTasks')
            ->where('SubTaskId',$request->input('SubTaskId'))
            ->update(['ActualEndDate' => date('Y-m-d H:i:s')],['TaskStatus' => '4'],[['LastUpdatedTimeForSync' => date('Y-m-d H:i:s')]]);

        $newTask = new TimeX_TimeSheetMobileLog();
        $newTask->EmployeeId = $request->input('SubTaskId');
        $newTask->DeviceId = $request->input('DeviceId');
        $newTask->SubTaskId = $request->input('SubTaskId');
        $newTask->TaskStartDateTime = $request->input('TaskStartDateTime');
        $newTask->TaskEndDateTime = $request->input('TaskEndDateTime');
        $newTask->ElapsedTimeForTask = $request->input('ElapsedTimeForTask');
        $newTask->IsSynced = $request->input('IsSynced');
        $newTask->LocalDbId = $request->input('LocalDbId');
        $newTask->SyncedTime = $request->input('SyncedTime');
        $newTask->IsAvailableInTimeSheet = $request->input('IsAvailableInTimeSheet');
        if($newTask->save()){
            $data['status'] = array('code' => 200, 'message' => "Successfully Checked out.", 'error' => "");
        } else {
            $data['status'] = array('code' => 400, 'message' => "Something went wrong", 'error' => "Checkin details not saved.");
        }

    }
}
