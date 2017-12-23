<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class JobsController extends Controller
{
    public function getCommencedShifts($mobileuserid){

        //step 1: all assigned shifts that have an entry in the shifts table, but have not ended
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
}
