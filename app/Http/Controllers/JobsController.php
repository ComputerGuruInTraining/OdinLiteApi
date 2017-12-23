<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class JobsController extends Controller
{
    public function getCommencedShifts($mobileuserid){

//the logic is:

        //step 1: all assignedShifts for the period. (array1)
        //step 2: all assignedShifts that have been started by the mobile user (array2)
        // (!Important! > 1 mobile user can be assigned to shift)
        //step 4: all assignedShifts that have been started, check if they have ended (array3)
        //step 5: array2 items that are not in array3 have been started but not completed, therefore include in results.(array5)



//        NO^
        //step 3: array1 items that don't appear in array2 have not been started, therefore include in results.(array4)
        //step 6: add (array1-2) to (array2-3) to get the complete set of results (= array6)

//        no^

        //step 1: all assigned shifts that have an entry in the shifts table, but have not ended
        $myCommenced = DB::table('assigned_shifts')
            ->join('assigned_shift_employees', 'assigned_shift_employees.assigned_shift_id', '=',
                'assigned_shifts.id')
            ->join('shifts', 'assigned_shifts.id', '=', 'shifts.assigned_shift_id')
            ->select('assigned_shifts.id, shifts.start_time')
            ->where('assigned_shift_employees.mobile_user_id', '=', $mobileuserid)
            ->where('assigned_shifts.end', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 2 DAY)'))
            ->where('assigned_shifts.deleted_at', '=', null)
            ->where('assigned_shift_employees.deleted_at', '=', null)
            ->where('shifts.end_time', '=' , null)//shift has not ended
            ->where('shifts.deleted_at', '=', null)//ensuring shift has not been deleted
            ->get();

        //all assigned shifts for the period specified
//        $array1ids = $array1->pluck('id');
//
//        //step2 :all shifts that have been started by this mobile_user
//        $array2ids = DB::table('shifts')
//            ->whereIn('assigned_shift_id', $array1ids)
//            ->where('mobile_user_id', '=', $mobileuserid)
//            ->pluck('assigned_shift_id');
//
//        //step 3: array1 items that don't appear in array2 have not been started, therefore include in results.(array1-2)
//        //1st set of data
////        $array4 = $array1ids->diff($array2ids);
//
//        //step4: all shifts out of the shifts that have been started and have been completed
//        $array3ids = DB::table('shifts')
//            //array of ids
//            ->whereIn('assigned_shift_id', $array2ids)
//            ->where('end_time', '!=', null)
//            ->pluck('assigned_shift_id');
//
//        //step 5: array2 items that are not in array3 have been started but not completed, therefore include in results.(array5)
//        $array5 = $array2ids->diff($array3ids);
//
//        //step 6: add (array1-2) to (array2-3) to get the complete set of results (= array6)
//        $array6ids = $array4->merge($array5);

//        $myCommenced = DB::table('assigned_shift_employees')
//            ->join('assigned_shifts', 'assigned_shift_employees.assigned_shift_id', '=', 'assigned_shifts.id')
//            ->whereIn('assigned_shifts.id', $array6ids)
//            ->where('mobile_user_id', '=', $mobileuserid)
//            ->where('assigned_shifts.deleted_at', '=', null)
//            ->where('assigned_shift_employees.deleted_at', '=', null)
//            ->get();

//        foreach ($myCommenced as $i => $commenced) {
//            //convert start and end from a datetime object to timestamps
//            //and append to the end of all of the assigned objects
//            //required for fomatting display
//            $myCommenced[$i]->start_ts = strtotime($commenced->start);
//            $myCommenced[$i]->end_ts = strtotime($commenced->end);
//        }

        //returns a result set of assigned_shift_ids
        return response()->json($myCommenced);
    }
}
