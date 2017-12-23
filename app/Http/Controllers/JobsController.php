<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class JobsController extends Controller
{
    public function getCommencedShifts($mobileuserid){

        $myCommenced = DB::table('assigned_shifts')
            ->join('shifts', 'assigned_shifts.id', '=', 'shifts.assigned_shift_id')
            ->select('assigned_shifts.id', 'shifts.start_time')
            ->where('assigned_shifts.end', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 2 DAY)'))
            ->where('assigned_shifts.deleted_at', '=', null)
            ->where('shifts.mobile_user_id', '=', $mobileuserid)
            ->where('shifts.end_time', '=' , null)//shift has not ended
            ->where('shifts.deleted_at', '=', null)//ensuring shift has not been deleted
            ->orderBy('shifts.start_time', 'desc')//the most recent first
            ->get();

        //returns a result set of assigned_shift_ids
        return response()->json($myCommenced);
    }


//    public function getCommencedShiftDetails($assignedid){
//       //what we need exactly:
//        //
//        //locations
//        //case notes
//        //shift checks
//        //
////        if several locations
////need the number of times the shift has been checked
//        //which can be deciphered from the number of shift_check entries that
//        //correspond to the shift_id and the location_id and that have a check_outs entry
//        //unfortunately, if the put_check_out fails, the check will not be recorded as complete.
//        //one of those imperfectations. But success rate is high thankfully.
//
////and whether a case note has been logged for that check. not the case note length. the shift_check_case length > 0
//
//
//        //if single location
//        ////case note length
//
//        //Step 1: get the shift_locations
//
//
//
//
//        $shiftInProgress = DB::table('shifts')
//
//
//
//            //single locations
//
//
//            //several locations
////                if()
//            ->join('shift_checks', 'shifts.id', '=', 'shift_checks.shift_id')
//            ->join()
//
//
//
//
//
//    }

    public function getShiftLocations($asgnshftid){

        $assigned = DB::table('assigned_shift_locations')
            ->join('locations', 'locations.id', '=', 'assigned_shift_locations.location_id')
            ->where('assigned_shift_locations.assigned_shift_id', '=', $asgnshftid)
            ->where('locations.deleted_at', '=', null)
            ->where('assigned_shift_locations.deleted_at', '=', null)
            ->get();

        return $assigned;

    }
}
