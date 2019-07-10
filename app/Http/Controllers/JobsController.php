<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\ShiftCheckCases as CheckCases;
use App\ShiftCheck as ShiftCheck;
use Carbon\Carbon;
use App\AssignedShift as AssignedShift;
use App\AssignedShiftEmployee as AssignedEmp;
use App\AssignedShiftLocation as AssignedLoc;
use App\Location as Location;
use App\User as User;
use App\ShiftResume as ShiftResume;


class JobsController extends Controller
{

    //note, in return collection $id = assigned_location_id
    public function getAssignedShiftsList($compId){

        $assigned = DB::table('assigned_shifts')
            ->join('assigned_shift_employees', 'assigned_shift_employees.assigned_shift_id', '=', 'assigned_shifts.id')
            ->join('assigned_shift_locations', 'assigned_shift_locations.assigned_shift_id', '=', 'assigned_shifts.id')
            ->where('assigned_shifts.company_id', '=', $compId)
            ->where('assigned_shifts.deleted_at', '=', null)
            ->where('assigned_shift_employees.deleted_at', '=', null)
            ->where('assigned_shift_locations.deleted_at', '=', null)
            ->orderBy('start', 'asc')
            ->orderBy('assigned_shift_locations.location_id')
            ->get();

        foreach ($assigned as $i => $item) {

            //find the location_id name if a location exists for that id in the locations table
            $location = Location::find($assigned[$i]->location_id);

            if ($location != null) {

                $name = $location->name;

                //store location name in the object
                $assigned[$i]->location = $name;
            }
        }

        foreach ($assigned as $i => $details) {
            //get the employee's name from the employees table for all employees assigned a shift
            $emp = User::find($assigned[$i]->mobile_user_id);
            //ensure the assigned_shift_employee record exists in the users table else errors could occur
            if ($emp != null) {

                $first_name = $emp->first_name;
                $last_name = $emp->last_name;
                $name = $first_name . ' ' . $last_name;

                //store location name in the object
                $assigned[$i]->employee = $name;
            } else {
                //$emp == null meaning it must have been deleted from the employees table
                //Step: soft delete the record from assigned_shift_employees table for the employee's user_id
                //to ensure there are no shifts assigned to a user that has been deleted
                //first, find the array of records that have the mobile_user_id in question
                $assignedEmps = AssignedEmp::where('mobile_user_id', '=', $assigned[$i]->mobile_user_id)->get();
                //then loop through those records, soft deleting each model
                foreach ($assignedEmps as $assignedEmp) {
                    $assignedEmp->delete();
                }

                //Step: having deleted it from the table, we now need to remove the object from the $assigned array
                $assigned->pull($i);
            }
        }

        //find the assigned_shifts that have an entry in the shifts table, and retrieve the user_id of those shifts
        foreach ($assigned as $i => $assignedShift) {

            $commenced = DB::table('shifts')
                ->join('assigned_shifts', 'assigned_shifts.id', '=', 'shifts.assigned_shift_id')
                ->join('users', 'users.id', '=', 'shifts.mobile_user_id')
                ->where('assigned_shifts.id', '=', $assignedShift->assigned_shift_id)
                ->select('shifts.mobile_user_id')
                ->get();

            if(count($commenced) == 0){
                $assigned[$i]->commenced = 'not commenced';
            }else if (count($commenced) > 0){

                $assigned[$i]->commenced = 'commenced';
            }
        }

        //Step: reset the keys on the collection, else datatype is std::class
        $assigned = $assigned->values();

        return response()->json($assigned);
    }

    //getAssignedShifts for a particular userId
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

        //returns a result set of assigned_shift_ids or null
        return response()->json($myCommenced);
    }

    public function getCommencedShiftDetails($assignedid, $mobileUserId)
    {
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

        //todo: implement at a later date as needs to be thorough or there will be errors
        //returns details such as the shiftCheckId for each location and location_id
        $checkId = $this->getCurrentCheckIn($shiftId->id);

        $countChecksWoCheckOut = count($checkId);

        foreach ($assignedLoc as $i => $location) {

            //Data Requirement 2. number of checks completed
            $numChecks = $this->countShiftChecks($shiftId->id, $location->location_id);

            $assignedLoc[$i]->checked = $numChecks;

        }

        if (count($assignedLoc) > 1) {
            $caseCheck = false;//initialise

            foreach ($assignedLoc as $i => $location) {
                //Data Requirement 3. if a shift check is still to be completed
                //only possibly for 1 location per shift
                if (count($checkId) == 1) {
                    //therefore only 1 array item
                    //for the location that has a current check in without a check out
                    if ($assignedLoc[$i]->location_id == $checkId[0]->location_id) {
                        //assign this location to the locationCheckedIn Variable in mobile
                        $assignedLoc[$i]->checkedIn = true;
                        $assignedLoc[$i]->currentCheckIn = $checkId[0]->id;

                        //Data Requirement 4:
                        $casePerCheck = $this->caseNoteSbmtd($checkId[0]->id);

                        //if a case note exists for the current check in
                        if (count($casePerCheck) > 0) {
                            $assignedLoc[$i]->casePerCheck = true;
                            $caseCheck = true;

                        } else if (count($casePerCheck) == 0) {
                            //case note not submitted
                            $assignedLoc[$i]->casePerCheck = false;
                            $caseCheck = false;
                        }
                    } else {
                        $assignedLoc[$i]->currentCheckIn = null;
                        $assignedLoc[$i]->checkedIn = false;
                        $assignedLoc[$i]->casePerCheck = false;
                    }

                } else if (count($checkId) == 0) {
                    //location not checked in
                    $assignedLoc[$i]->checkedIn = false;
                    $assignedLoc[$i]->casePerCheck = false;
                    $assignedLoc[$i]->currentCheckIn = null;
                    $caseCheck = false;

                } else if (count($checkId) > 1) {
                    //commencedshiftdetails/2114/2104
                    //commencedshiftdetails/2174/2014 (shiftId = 5344, 2 checkInsWoCheckOuts via db and count good)
                    //todo: in future, optimize for greater than 1 current check in
                    //location not checked in
                    foreach ($checkId as $j => $checkIdItem) {
                        if ($assignedLoc[$i]->location_id == $checkId[$j]->location_id) {

                            $assignedLoc[$i]->checkedIn = true;
                            $assignedLoc[$i]->currentCheckIn = $checkId[$j]->id;

                            //Data Requirement 4:
                            $casePerCheck = $this->caseNoteSbmtd($checkIdItem->id);

                            //if a case note exists for the current check in
                            if (count($casePerCheck) > 0) {
                                $assignedLoc[$i]->casePerCheck = true;
                                $caseCheck = true;

                            } else if (count($casePerCheck) == 0) {
                                //case note not submitted
                                $assignedLoc[$i]->casePerCheck = false;
                                $caseCheck = false;
                            }
                        } else {
                            $assignedLoc[$i]->currentCheckIn = null;
                            $assignedLoc[$i]->checkedIn = false;
                            $assignedLoc[$i]->casePerCheck = false;
                        }
                    }
                }
            }
        }

        //store the resume shift in the shiftResumeTable
        $shiftResumeId = app('App\Http\Controllers\JobsController')->storeShiftResume('resume', $shiftId->id);

        //single locations
        //data required is: location details, has a case note been submitted,
        //ATM, before more testing is complete, reluctant to remove this working code for single location shifts,
        //so keep it for the moment
        if (count($assignedLoc) == 1) {
//            commencedshiftdetails/2124/2104
            $notes = app('App\Http\Controllers\CaseNoteApiController')->getShiftCaseNotes($shiftId->id);

            if (count($notes) > 0) {
                $singleCaseNote = true;
                //single loc commencedshiftdetails/2124/2084

            } else {
                $singleCaseNote = false;
            }

            if (count($checkId) == 1) {
                //todo: test case for this
                $assignedLoc[0]->checkedIn = true;
                $assignedLoc[0]->currentCheckIn = $checkId[0]->id;

            }else if (count($checkId) == 0) {
                //2124/2104 & 2144/2084
                $assignedLoc[0]->checkedIn = false;
                $assignedLoc[0]->currentCheckIn = null;

            } else if (count($checkId) > 1) {
                //2124/2084
                foreach ($checkId as $x => $checkIdItem) {
                    if ($assignedLoc[0]->location_id == $checkId[$x]->location_id) {

                        $assignedLoc[0]->checkedIn = true;
                        $assignedLoc[0]->currentCheckIn = $checkId[$x]->id;
                    }
                }
            }

            return response()->json([
                'locations' => $assignedLoc,
                'shiftId' => $shiftId,
                'caseCheck' => $singleCaseNote,
                'shiftResumeId' => $shiftResumeId,//value or null if storeError
                'countChecksWoCheckOut' => $countChecksWoCheckOut
            ]);

        } else {

            //several locations
            //data required is: 1.location details, 2.number of checks completed at each location,
            // 3.the current check in (if there is one),
            // 4.has a case note been submitted?

            //Note: Data Requirement 2 ie number of checks:
            //can be deciphered from the number of shift_check entries that
            //correspond to the shift_id and the location_id and that have a check_outs entry
            //unfortunately, if the put_check_out fails, the check will not be recorded as complete.
            //one of those imperfections. But success rate is high thankfully.

            return response()->json([
                'locations' => $assignedLoc,
                'shiftId' => $shiftId,
                'caseCheck' => $caseCheck,
                'shiftResumeId' => $shiftResumeId,//value or null if storeError
                'countChecksWoCheckOut' => $countChecksWoCheckOut
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
    //it is possible that there may be more than one checkIn without a checkOut
    public function getCurrentCheckIn($shiftId){

        $checkInProgress = DB::table('shift_checks')
            ->select('id', 'check_ins', 'check_outs', 'location_id', 'created_at')
            ->where('shift_id', '=', $shiftId)
            ->where('check_outs', '=',  null)
            ->get();

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
    //and the shiftResumeTable
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
        $check->check_duration = $checkDuration;//seconds

        if ($check->save()) {
            return 'success';
        } else {
            return 'failed';
        }
    }

    //returns a single record or an array of records as the assigned shift may have multiple entries in the
    // assigned_shift_locations and/or assigned_shift_employees tables
    public function getAssignedShift($id){

        //verify company first
        $assignedObject = AssignedShift::find($id);

        $verified = verifyCompany($assignedObject);

        if(!$verified){

            return response()->json($verified);//value = false
        }

        //if verified as being the same company, or if no record is returned from the query ie $assigned = {}
        $assigned = DB::table('assigned_shifts')
            ->join('assigned_shift_employees', 'assigned_shift_employees.assigned_shift_id', '=', 'assigned_shifts.id')
            ->join('assigned_shift_locations', 'assigned_shift_locations.assigned_shift_id', '=', 'assigned_shifts.id')
            ->where('assigned_shifts.id', '=', $id)
            ->where('assigned_shift_locations.deleted_at', '=', null)
            ->where('assigned_shift_employees.deleted_at', '=', null)
            ->orderBy('start', 'asc')
            ->orderBy('assigned_shift_locations.location_id')
            ->get();

        foreach ($assigned as $i => $details) {
            $emp = User::find($assigned[$i]->mobile_user_id);

            //ensure the assigned_shift_employee record exists in the users table
            if ($emp != null) {
                $first_name = $emp->first_name;
                $last_name = $emp->last_name;
                $name = $first_name . ' ' . $last_name;
            } //mobile_user_id does not exist in locations table
            else {
                $name = "Employee not in database";
            }
            //store location name in the object
            $assigned[$i]->employee = $name;
        }

        foreach ($assigned as $i => $item) {

            //find the location_id name if a location exists for that id in the locations table
            $location = Location::find($assigned[$i]->location_id);

            if ($location != null) {
                $name = $location->name;
            } //location_id does not exist in locations table
            else {
                $name = "Location not in database";
                $assigned[$i]->checks = 0;
            }
            //store location name in the object
            $assigned[$i]->location = $name;
        }

        //find whether this assigned_shift has an entry in the shifts table, and retrieve the user_id of those shifts
        foreach ($assigned as $i => $assignedShift) {

            $commenced = DB::table('shifts')
                ->join('assigned_shifts', 'assigned_shifts.id', '=', 'shifts.assigned_shift_id')
                ->join('users', 'users.id', '=', 'shifts.mobile_user_id')
                ->where('assigned_shifts.id', '=', $assignedShift->assigned_shift_id)
                ->select('shifts.mobile_user_id')
                ->get();

            if(count($commenced) == 0){
                $assigned[$i]->commenced = 'not commenced';

            }else if (count($commenced) > 0){

                $assigned[$i]->commenced = 'commenced';
            }
        }

        return response()->json($assigned);
    }

    public function putShift(Request $request, $id){
        //table: assigned_shifts

        $start = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('start'));
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('end'));

        $assigned = AssignedShift::find($id);

        //verify company first
        $verified = verifyCompany($assigned);

        if(!$verified){

            return response()->json($verified);//value = false
        }

        $assigned->company_id = $request->input('compId');

        if ($request->has('title')) {
            $assigned->shift_title = $request->input('title');
        }

        if ($request->has('desc')) {
            if($request->input('desc') == "none") {
                $assigned->shift_description = null;
            }else{
                $assigned->shift_description = $request->input('desc');
            }
        }

        if ($request->has('roster_id')) {
            $assigned->roster_id = 1;
        }

        $assigned->start = $start;
        $assigned->end = $end;

        $assigned->asg_duration_mins = $start->diffInMinutes($end);

        $assigned->save();

        //assigned_shift_employees
        //update the employees assigned to the shift

        //new employee values to be used for updating the table
        $newEmpArray = $request->input('employees');

        $newEmp = collect($newEmpArray);

        //current employee values to be replaced with the new edits that haven't already been deleted by way of deleting employee
        $oldEmps = DB::table('assigned_shift_employees')
            ->where('assigned_shift_id', '=', $id)
            ->where('deleted_at', '=', null)
            ->pluck('mobile_user_id');

        /*
        **compare the old employees to the new employees
        **in assigned_shift_employees table
        */

        //create new records for those new employees updated in the view that aren't already in the assigned_shift_employees table
        $addEmps = $newEmp->diff($oldEmps);

        foreach ($addEmps as $addEmp) {
            $employee = new AssignedEmp;
            $employee->mobile_user_id = $addEmp;
            $employee->assigned_shift_id = $id;
            $employee->save();
        }

        //delete those records that are currently in the assigned_shift_employees table but were not included in the edit
        $deleteEmps = $oldEmps->diff($newEmp);

        foreach ($deleteEmps as $deleteEmp) {
            $assignedEmp = AssignedEmp::where('mobile_user_id', '=', $deleteEmp)
                ->where('assigned_shift_id', '=', $id)
                ->delete();

        }

        //might fixme be best to include an object with the location and check, not just an array which is separate
        $checksArray = $request->input('checks');
        //assigned_shift_locations
        //update the locations assigned to the shift

        $newLocArray = $request->input('locations');//from 664, 714

        //for location checks
        $colLocCheck = collect([]);

        for($l = 0; $l < sizeof($newLocArray); $l++){

            $array = array_add(['location' => $newLocArray[$l]], 'checks', $checksArray[$l]);

            $colLocCheck->push($array);
        }

        $newLoc = collect($newLocArray);

        //current old assigned_location values to be edited that have not otherwise been deleted
        $oldLocs = DB::table('assigned_shift_locations')
            ->where('assigned_shift_id', '=', $id)
            ->where('deleted_at', '=', null)
            ->pluck('location_id');

        //create new records for those new locations updated in the view that aren't already in the assigned_shift_locations table
        $addLocs = $newLoc->diff($oldLocs);

        foreach ($addLocs as $addLoc) {
            //before adding the location, find the appropriate collection item with the corresponding checks
            foreach($colLocCheck as $colCheck){

                if($addLoc == $colCheck['location']){

                    $location = new AssignedLoc;
                    $location->location_id = $addLoc;
                    $location->assigned_shift_id = $id;
                    $location->checks = $colCheck['checks'];
                    $location->save();
                }
            }
        }

        //delete those records that are currently in the assigned_shift_locations table but were not included in the edit
        $deleteLocs = $oldLocs->diff($newLoc);

        //soft delete on model
        foreach ($deleteLocs as $deleteLoc) {
            $assignedLoc = AssignedLoc::where('location_id', '=', $deleteLoc)
                ->where('assigned_shift_id', '=', $id)
                ->delete();
        }

        //update those records that have the same location_id and assigned_shift_id, but a different amount of checks
        $sameLocs = $oldLocs->intersect($newLoc);

        foreach ($sameLocs as $sameLoc) {
            $toUpdateId = DB::table('assigned_shift_locations')
                ->where('location_id', '=', $sameLoc)
                ->where('assigned_shift_id', '=', $id)
                ->where('deleted_at', '=', null)
                ->pluck('id');

            $location = AssignedLoc::find($toUpdateId);

            //before modifying the location checks, find the appropriate collection item with the corresponding checks
            foreach($colLocCheck as $colCheck){

                if($sameLoc == $colCheck['location']){
                    if ($location->checks != $colCheck['checks']) {
                        $location->checks = $colCheck['checks'];
                        $location->save();
                    }
                }
            }
        }

        if ($assigned->save()) {
            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }

    }

    public function deleteAssignedShift($id){

        $assigned = AssignedShift::find($id);

        //verify company first
        $verified = verifyCompany($assigned);

        if(!$verified){

            return response()->json($verified);//value = false
        }

        //find whether this assigned_shift has an entry in the shifts table, and retrieve the user_id of those shifts
        $commenced = DB::table('shifts')
            ->join('assigned_shifts', 'assigned_shifts.id', '=', 'shifts.assigned_shift_id')
            ->join('users', 'users.id', '=', 'shifts.mobile_user_id')
            ->where('assigned_shifts.id', '=', $assigned->id)
            ->select('shifts.mobile_user_id')
            ->get();

        //shift has been commenced, so return to the console without deleting record
        if (count($commenced) > 0){

            return response()->json(['commenced' => 'commenced']);
        }

        $assigned->delete();

        AssignedEmp::where('assigned_shift_id', '=', $id)->delete();

        AssignedLoc::where('assigned_shift_id', '=', $id)->delete();

        //TODO: ensure record destroyed before returning success true
        return response()->json([
            'success' => true
        ]);
    }

    public function storeShiftResume($status, $shiftId){

        try {
            $shiftResume = new ShiftResume;

            $shiftResume->status = $status;
            $shiftResume->shift_id = $shiftId;
            $shiftResume->save();

            //retrieve id of the saved shift
            $id = $shiftResume->id;

            return $id;

        }catch (\Exception $e) {
        //Exception will catch all errors thrown
            return null;
        }
    }

    //get last shift resumed by the user
    //then ensure that shift has not ended
    //return: shiftId, assignedShiftId, shiftResumeCreatedAt
    public function getLastShiftResumed(Request $request){
        try{

            //first, verify company
//            $user = User::find($userId);//works
            $userId = $request->input('userId');
            $user = User::find($userId);

            $verified = verifyCompany($user);

            if(!$verified){

                return response()->json($verified);//value = false
            }

            //get the last shift resumed by the user
            $lastShiftIdPerUser = DB::table('shift_resumes')
                ->join('shifts', 'shifts.id', '=', 'shift_resumes.shift_id')
                ->select('shifts.id as shiftId',
                    'shift_resumes.created_at as shiftResumeCreatedAt', 'shifts.assigned_shift_id as assignedId')
                ->where('shifts.mobile_user_id', '=', $userId)
                ->where('shifts.deleted_at', '=', null)
                ->where('shift_resumes.deleted_at', '=', null)
                ->latest('shift_resumes.created_at')
                ->first();

            //then, if there is a value, ensure that shift has not ended
            if ($lastShiftIdPerUser != null) {

                $shiftIdNotEnded = DB::table('shifts')
                    ->select('id')
                    ->where('id', '=', $lastShiftIdPerUser->shiftId)
                    ->where('end_time', '=', null)
                    ->get();

                if(count($shiftIdNotEnded) != 0){

                    $shiftId = $shiftIdNotEnded[0]->id;

                    //1. lastShiftIdPerUser has a value and shiftId has a value
                    return response()->json([
                        'success' => true,
                        'shiftId' => $shiftId,
                        'lastShiftResumed' => $lastShiftIdPerUser->shiftId,//mostly for testing/checking purposes, this shift may be ended
                        'assignedId' => $lastShiftIdPerUser->assignedId,
                        'shiftResumeCreatedAt' => $lastShiftIdPerUser->shiftResumeCreatedAt
                    ]);
                }else {

                    //2. lastShiftIdPerUser has a value but shiftId does not have a value
                    return response()->json([
                        'success' => true,
                        'shiftIdEnded' => $shiftIdNotEnded,//the last shift resumed by the user has been ended, but return the empty array for thorough mobile testing
                        'lastShiftResumed' => $lastShiftIdPerUser->shiftId,//mostly for testing/checking purposes
                        'assignedId' => $lastShiftIdPerUser->assignedId,
                        'shiftResumeCreatedAt' => $lastShiftIdPerUser->shiftResumeCreatedAt
                    ]);
                }
            } else {
                //3. lastShiftIdPerUser has no value because user has no entries in the shiftResume table
                return response()->json([
                    'success' => true,
                    'shiftId' => null//the user does not have an entry in the shift resume table, so return null
                ]);
            }
        }catch (\Exception $e) {
            //Exception will catch all errors thrown
            return response()->json([
                'success' => false
            ]);
        }
    }

    //WIP
   /* public function shiftsPerUser($userId){
        //get the last shift resumed for a particular user
        $shiftsPerUser = DB::table('shift_resumes')
            ->join('shifts', 'shifts.id', '=', 'shift_resumes.shift_id')
//                ->join('assigned_shift_employees', 'assigned_shift_employees.assigned_shift_id', '=', 'assigned_shifts.id')
            ->where('shifts.mobile_user_id', '=', $userId)
            ->where('shifts.deleted_at', '=', null)
            ->where('shift_resumes.deleted_at', '=', null)
            ->get();

    }*/



}
