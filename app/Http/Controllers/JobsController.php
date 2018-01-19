<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\ShiftCheckCases as CheckCases;
use App\ShiftCheck as ShiftCheck;
use Carbon\Carbon;


class JobsController extends Controller
{

    public function getAssignedShifts($id){
        //the logic is:
        //step 1: all assignedShifts for the period. (array1)
        //step 2: all assignedShifts that have been started by the mobile user (array2)
        // (!Important! > 1 mobile user can be assigned to shift)
        //step 3: array1 items that don't appear in array2 have not been started, therefore include in results.(array4)
        //step 4: all assignedShifts that have been started, check if they have ended (array3)
        //step 5: array2 items that are not in array3 have been started but not completed, therefore include in results.(array5)
        //step 6: add (array1-2) to (array2-3) to get the complete set of results (= array6)

        //step 1: all assigned shifts
        $array1 = DB::table('assigned_shifts')
            ->join('assigned_shift_employees', 'assigned_shift_employees.assigned_shift_id', '=',
                'assigned_shifts.id')
            ->select('assigned_shifts.id')
            ->where('assigned_shift_employees.mobile_user_id', '=', $id)
            ->where('assigned_shifts.end', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 2 DAY)'))
            ->where('assigned_shifts.deleted_at', '=', null)
            ->where('assigned_shift_employees.deleted_at', '=', null)
            ->get();

        //all assigned shifts for the period specified
        $array1ids = $array1->pluck('id');

        //step2 :all shifts that have been started by this mobile_user
        $array2ids = DB::table('shifts')
            ->whereIn('assigned_shift_id', $array1ids)
            ->where('mobile_user_id', '=', $id)
            ->pluck('assigned_shift_id');

        //step 3: array1 items that don't appear in array2 have not been started, therefore include in results.(array1-2)
        //1st set of data
        $array4 = $array1ids->diff($array2ids);

        //step4: all shifts out of the shifts that have been started and have been completed
        $array3ids = DB::table('shifts')
            //array of ids
            ->whereIn('assigned_shift_id', $array2ids)
            ->where('end_time', '!=', null)
            ->where('mobile_user_id', '=', $id)
            ->pluck('assigned_shift_id');

        //step 5: array2 items that are not in array3 have been started but not completed, therefore include in results.(array5)
        $array5 = $array2ids->diff($array3ids);

        //step 6: add (array1-2) to (array2-3) to get the complete set of results (= array6)
        $array6ids = $array4->merge($array5);

        $myAssigned = DB::table('assigned_shift_employees')
            ->join('assigned_shifts', 'assigned_shift_employees.assigned_shift_id', '=', 'assigned_shifts.id')
            ->whereIn('assigned_shifts.id', $array6ids)
            ->where('mobile_user_id', '=', $id)
            ->where('assigned_shifts.deleted_at', '=', null)
            ->where('assigned_shift_employees.deleted_at', '=', null)
            ->get();

        foreach ($myAssigned as $i => $assigned) {
            //convert start and end from a datetime object to timestamps
            //and append to the end of all of the assigned objects
            $myAssigned[$i]->start_ts = strtotime($assigned->start);
            $myAssigned[$i]->end_ts = strtotime($assigned->end);
        }

        return response()->json($myAssigned);

    }

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

    public function getCommencedShiftDetails($assignedid, $mobileUserId){

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
        $shiftId = $this->getShiftId($assignedid, $mobileUserId);

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
    public function getShiftId($assignedId, $mobileUserId){

        $shiftIdObject = DB::table('shifts')
            ->select('id')
            ->where('assigned_shift_id', '=', $assignedId)
            ->where('mobile_user_id', '=', $mobileUserId)
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

    public function caseNoteSbmtd($checkIn)
    {

        $shiftCheckCase = DB::table('shift_check_cases')
            ->where('shift_check_id', '=', $checkIn)
            ->first();

        return $shiftCheckCase;
    }

   //insert into shift_check_cases table
    public function postShiftCheckCase($caseNoteId, $sftChkId){

        $sftChkCase = new CheckCases;
        $sftChkCase->case_note_id = $caseNoteId;
        $sftChkCase->shift_check_id = $sftChkId;

        if($sftChkCase->save()){
            $id = $sftChkCase->id;

            return $id;
        }else{

            return 0;
        }
    }

    //mobile: Called to store the shift check_in in the shift_checks table
    public function storeCheckIn($request)
    {
        //use posId to get the latitude and the longitude of the geoLocation
        //and compare with the location of the shiftCheck to determine if withinRange
        $posId = $request->input('posId');
        $locId = $request->input('locId');

        $distance = app('App\Http\Controllers\LocationController')->implementDistance($posId, $locId);

        $shiftCheck = new ShiftCheck;

        //$shift->check_ins will take the default current_timestamp
        $shiftCheck->shift_id = $request->input('shiftId');
        $shiftCheck->user_loc_check_in_id = $posId;
        $shiftCheck->location_id = $locId;//note: ts is in UTC time
        $shiftCheck->checks = $request->input('checks');
        $shiftCheck->distance_check_in = $distance;
        $shiftCheck->save();

//        $createdAt = $shiftCheck->created_at;

        //retrieve id of the saved shift
        $id = $shiftCheck->id;

        return $id;
    }

    //mobile: store the location check out
    public function storeCheckOut($request)
    {
        //retrieve current shift's record for update
        //using shift_id and location_id where check_outs null
        $checkId = $request->input('shiftChecksId');
        $check = ShiftCheck::find($checkId);

        $posId = $request->input('posId');
        $locId = $check->location_id;

        $distance = app('App\Http\Controllers\LocationController')->implementDistance($posId, $locId);

        $checkOut = Carbon::now();
        $checkInTime = $check->check_ins;

        //calculate checkDuration
        $checkDuration = checkDuration($checkInTime, $checkOut);

        $check->user_loc_check_out_id = $posId;
        $check->check_outs = $checkOut;
        $check->distance_check_out = $distance;
        $check->check_duration = $checkDuration;

        if ($check->save()) {
            return 'success';
        } else {
            return 'failed';
        }
    }

//    public function getShiftsByShiftCheckIds($reportSftChkIds)
//    {
//        $shifts = DB::table('shifts')
//            ->whereIn('id', $reportSftChkIds)
//            ->get();
//
//        return $shifts;
//
//    }
//
//    public function storeCheckOutTest($shiftCheckId, $posId)
//    {
//        //retrieve current shift's record for update
//        //using shift_id and location_id where check_outs null
//        $checkId = $shiftCheckId;
//        $check = ShiftCheck::find($checkId);
//
////        dd($check);
//
//        $posId = $posId;
//        $locId = $check->location_id;
//
//        $distance = app('App\Http\Controllers\LocationController')->implementDistance($posId, $locId);
//
//        $checkOut = Carbon::now();
//        $checkInTime = $check->check_ins;
//
//        //calculate checkDuration
//        $checkDuration = checkDuration($checkInTime, $checkOut);
//
//        $check->user_loc_check_out_id = $posId;
//        $check->check_outs = $checkOut;
//        $check->distance_check_out = $distance;
//        $check->check_duration = $checkDuration;
////dd($checkDuration);
//
////        $check->save()
//        if ($check->save()) {
//            return response()->json([
//                'success' => true
//            ]);
//        } else {
//            return response()->json([
//                'success' => false
//            ]);
//        }
//    }

//    public function storeCheckInTest($posId, $locId, $shiftId, $checks)
//    {
//        //use posId to get the latitude and the longitude of the geoLocation
//        //and compare with the location of the shiftCheck to determine if withinRange
////        $posId = $request->input('posId');
////        $locId = $request->input('locId');
//
//        //gets the geoLongitude, geoLatitude for the current_user_location_id
//        $distance = app('App\Http\Controllers\LocationController')->implementDistance($posId, $locId);
//
//        $shiftCheck = new ShiftCheck;
//
//        //$shift->check_ins will take the default current_timestamp
//        $shiftCheck->shift_id = $shiftId;
//        $shiftCheck->user_loc_check_in_id = $posId;
//        $shiftCheck->location_id = $locId;//note: ts is in UTC time
//        $shiftCheck->checks = $checks;
//        $shiftCheck->distance_check_in = $distance;
//        $shiftCheck->save();
//
////        $createdAt = $shiftCheck->created_at;
//
//        //retrieve id of the saved shift
//        $id = $shiftCheck->id;
//
//        return $id;
//    }
}
