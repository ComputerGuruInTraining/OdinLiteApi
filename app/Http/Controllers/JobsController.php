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


    public function getCommencedShiftDetails($assignedid){

        //Step 1: get the shift_locations
        //each assigned object will have:
        //location_id, name, address, latitude, longitude, notes, [required] checks
        //result set is a collection, whether 1 or many
        //Data Requirement 1
        $assignedLoc = $this->getShiftLocations($assignedid);

        //Step 2: get the shift_id that corresponds to the assigned_shift_id
        //each assigned_shift_id will only have 1 shift_id for a particular user
        //but retrieve first, because technically possible to have more than 1 from db query point of view
        //result is an array even though only one.
        $shiftId = $this->getShiftId($assignedid);

        //single locations
        //data required is: location details, has a case note been submitted,
        if(count($assignedLoc) == 1){

            $notes = app('App\Http\Controllers\CaseNoteApiController')->getShiftCaseNotes($shiftId->id);

            if(count($notes) > 0){
                $singleCaseNote = true;

            }else{
                $singleCaseNote = false;
            }

            return response()->json([
                'locations' => $assignedLoc,
                'shiftId' => $shiftId,
                'caseCheck' => $singleCaseNote,
                ]);

        }else{
            //several locations
            //data required is: 1.location details, 2.number of checks completed at each location,
            // 3.the current check in (if there is one),
            // 4.has a case note been submitted?

            //Note: Data Requirement 2 ie number of checks:
            //can be deciphered from the number of shift_check entries that
            //correspond to the shift_id and the location_id and that have a check_outs entry
            //unfortunately, if the put_check_out fails, the check will not be recorded as complete.
            //one of those imperfections. But success rate is high thankfully.

            foreach ($assignedLoc as $i => $location) {

                //Data Requirement 2. number of checks completed
                $numChecks =  $this->countShiftChecks($shiftId->id, $location->location_id);

                $assignedLoc[$i]->checked = $numChecks;

//todo: implement at a later date as needs to be thorough or there will be errors
                //returns the shiftCheckId for each location
//                $checkId = $this->getCurrentCheckIn($shiftId->id, $location->location_id);

                //Data Requirement 3. if a shift check is still to be completed
                //only possibly for 1 location per shift
//                if(count($checkId) > 0){
//                    //assign this location to the locationCheckedIn Variable in mobile
//                    $assignedLoc[$i]->checkedIn  = true;
//
//                    //Data Requirement 4:
//                    $casePerCheck = $this->caseNoteSbmtd($checkId->id);
//
//                    //if a case note exists for the current check in
//                    if(count($casePerCheck) > 0){
//                        $assignedLoc[$i]->casePerCheck = true;
//                        $caseCheck = true;
//
//                    }else if(count($casePerCheck) == 0){
//                        //case note not submitted
//                        $assignedLoc[$i]->casePerCheck = false;
//                        $caseCheck = false;
//                    }
//
//                }else if(count($checkId) == 0){
//                    //location not checked in
//                    $assignedLoc[$i]->checkedIn  = false;
//                    $assignedLoc[$i]->casePerCheck = false;
//                    $caseCheck = false;
//                }
            }

            return response()->json([
                'locations' => $assignedLoc,
                'shiftId' => $shiftId
            ]);

        }//end else several locations
    }

    public function getShiftLocations($asgnshftid){

        $assigned = DB::table('assigned_shift_locations')
            ->join('locations', 'locations.id', '=', 'assigned_shift_locations.location_id')
            ->where('assigned_shift_locations.assigned_shift_id', '=', $asgnshftid)
            ->where('locations.deleted_at', '=', null)
            ->where('assigned_shift_locations.deleted_at', '=', null)
            ->get();

        return $assigned;
    }

    //ensure get the most recent shift that relates to an assigned_shift_id,
    // mostly useful in development as shouldn't be a factor in production but just in case
    //as all shift data used within mobile for commenced shifts relies upon the shiftId being accurate
    public function getShiftId($assignedId){

        $shiftIdObject = DB::table('shifts')
            ->select('id')
            ->where('assigned_shift_id', '=', $assignedId)
            ->orderBy('created_at', 'desc')
            ->first();

        return $shiftIdObject;
    }

    //get shift checks amount completed for a particular shift_id per location
    public function countShiftChecks($shiftId, $locationId){

        $numChecks = DB::table('shift_checks')
            ->where('shift_id', '=', $shiftId)
            ->where('location_id', '=', $locationId)
            ->where('check_outs', '!=',  null)
            ->count();

        return $numChecks;
    }

    //return: should only be one value
    //if a location is checked in at and not checked out of
    //and the app deals with this thoroughly,
    //then each shiftcheck will have only one entry
    //but atm, this requires too many changes to the mobile app
    //so include a shift check gather once check in data being thoroughly implemented in mobile app
    public function getCurrentCheckIn($shiftId, $locationId){

        $checkInProgress = DB::table('shift_checks')
            ->select('id')
            ->where('shift_id', '=', $shiftId)
            ->where('location_id', '=', $locationId)
            ->where('check_outs', '=',  null)
            ->first();

        return $checkInProgress;
    }

    public function caseNoteSbmtd($checkIn){

        $shiftCheckCase = DB::table('shift_check_cases')
            ->where('shift_check_id', '=', $checkIn)
            ->first();

        return $shiftCheckCase;
    }
}
