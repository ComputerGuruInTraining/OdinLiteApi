<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\CaseFile as CaseFile;
use App\CaseNote as CaseNote;
use App\Cases as Cases;


class CaseNoteApiController extends Controller
{
    //get all data for case notes including geoLocation given a company_id
    public function getCaseNotes($compId)
    {
        $cases = DB::table('case_notes')
            ->join('cases', 'cases.id', '=', 'case_notes.case_id')
            ->join('users', 'users.id', '=', 'case_notes.user_id')
//            ->join('current_user_locations', 'case_notes.user_id', '=', 'current_user_locations.mobile_user_id')
            ->where('users.company_id', '=', $compId)
            ->where('case_notes.deleted_at', '=', null)
            ->where('cases.deleted_at', '=', null)
            ->select('case_notes.*', 'cases.location_id', 'users.first_name', 'users.last_name')
            ->orderBy('case_notes.created_at', 'desc')
            ->get();

        $locationIds = $cases->pluck('location_id');

        //retrieve location names, will retrieve all whether deleted or not
        $locations = DB::table('locations')
            ->whereIn('id', $locationIds)//array of $locationIds
            ->select('id', 'name', 'latitude', 'longitude')
            ->get();

        // //ensure there are $locations before adding the location names onto the end of case object
        if(sizeof($locations) > 0) {
            foreach ($cases as $i => $case) {
                foreach ($locations as $location){
                    if ($location->id == $cases[$i]->location_id) {
                        $cases[$i]->location = $location->name;
                        $cases[$i]->locLat = $location->latitude;
                        $cases[$i]->locLong = $location->longitude;
                    }
                }
            }
        }

        $currIds = $cases->pluck('curr_loc_id');

        $currLocs = DB::table('current_user_locations')
            ->whereIn('id', $currIds)
            ->select('id', 'latitude', 'longitude')
            ->get();

        //ensure there are $currLocs before adding the details onto the end of case object
        if(sizeof($currLocs) > 0) {
            foreach ($cases as $i => $case) {
                foreach ($currLocs as $currLoc){
                    if ($currLoc->id == $cases[$i]->curr_loc_id) {
                        $cases[$i]->geoLatitude = $currLoc->latitude;
                        $cases[$i]->geoLongitude = $currLoc->longitude;
                    }
                }
            }
        }

        //each cases object has a different case_id
        $caseIds = $cases->pluck('case_id');

        //retrieve files for those cases that they exist for
        $files = DB::table('case_files')
                ->whereIn('case_id', $caseIds)
                ->select('case_id','file')
                ->get();

        //if files exist
        //there could be more than 1 file for a case_id
        if(sizeof($files) > 0) {
            foreach ($cases as $i => $case) {

                $fileArray = [];

                foreach ($files as $file){
                    if ($file->case_id == $cases[$i]->case_id) {
                        array_push($fileArray,$file->file);
                    }
                }

                $cases[$i]->files = $fileArray;
            }
        }
        return response()->json($cases);
    }

    //get all data for case notes including geoLocation given a case_note_id
//    public function getCaseNote($caseNoteId)
//    {
//        $cases = DB::table('case_notes')
//            ->join('cases', 'cases.id', '=', 'case_notes.case_id')
//            ->join('users', 'users.id', '=', 'case_notes.user_id')
////            ->join('current_user_locations', 'case_notes.user_id', '=', 'current_user_locations.mobile_user_id')
//            ->where('users.company_id', '=', $compId)
//            ->where('case_notes.deleted_at', '=', null)
//            ->where('cases.deleted_at', '=', null)
//            ->select('case_notes.*', 'cases.location_id', 'users.first_name', 'users.last_name')
//            ->orderBy('case_notes.created_at', 'desc')
//            ->get();
//
//        $locationIds = $cases->pluck('location_id');
//
//        //retrieve location names, will retrieve all whether deleted or not
//        $locations = DB::table('locations')
//            ->whereIn('id', $locationIds)//array of $locationIds
//            ->select('id', 'name', 'latitude', 'longitude')
//            ->get();
//
//        // //ensure there are $locations before adding the location names onto the end of case object
//        if(sizeof($locations) > 0) {
//            foreach ($cases as $i => $case) {
//                foreach ($locations as $location){
//                    if ($location->id == $cases[$i]->location_id) {
//                        $cases[$i]->location = $location->name;
//                        $cases[$i]->locLat = $location->latitude;
//                        $cases[$i]->locLong = $location->longitude;
//                    }
//                }
//            }
//        }
//
//        $currIds = $cases->pluck('curr_loc_id');
//
//        $currLocs = DB::table('current_user_locations')
//            ->whereIn('id', $currIds)
//            ->select('id', 'latitude', 'longitude')
//            ->get();
//
//        //ensure there are $currLocs before adding the details onto the end of case object
//        if(sizeof($currLocs) > 0) {
//            foreach ($cases as $i => $case) {
//                foreach ($currLocs as $currLoc){
//                    if ($currLoc->id == $cases[$i]->curr_loc_id) {
//                        $cases[$i]->geoLatitude = $currLoc->latitude;
//                        $cases[$i]->geoLongitude = $currLoc->longitude;
//                    }
//                }
//            }
//        }
//
//        //each cases object has a different case_id
//        $caseIds = $cases->pluck('case_id');
//
//        //retrieve files for those cases that they exist for
//        $files = DB::table('case_files')
//            ->whereIn('case_id', $caseIds)
//            ->select('case_id','file')
//            ->get();
//
//        //if files exist
//        //there could be more than 1 file for a case_id
//        if(sizeof($files) > 0) {
//            foreach ($cases as $i => $case) {
//
//                $fileArray = [];
//
//                foreach ($files as $file){
//                    if ($file->case_id == $cases[$i]->case_id) {
//                        array_push($fileArray,$file->file);
//                    }
//                }
//
//                $cases[$i]->files = $fileArray;
//            }
//        }
//        return response()->json($cases);
//    }

    //return case_notes associated with a shift check
    public function getShiftCheckCaseNotes($arrayShiftCheckIds)
    {
        $shiftCheckCaseNotes = DB::table('shift_check_cases')
            ->join('case_notes', 'case_notes.id', '=', 'shift_check_cases.case_note_id')
            ->whereIn('shift_check_cases.shift_check_id', $arrayShiftCheckIds)
            ->get();

        return $shiftCheckCaseNotes;
    }

    //get case files by case_id which is required in case_files table, whereas case_note_id is nullable
    // to cater for cases where a case_file is being uploaded for a case without an accompanying case_note
    public function getCaseFiles($caseIds){

        $files = DB::table('case_files')
            ->whereIn('case_id', $caseIds)
            ->select('case_id','file', 'case_note_id')
            ->get();

        return $files;
    }

    //get the case notes for a shift
    public function getShiftCaseNotes($shiftId){

        $notes = DB::table('case_notes')
            ->where('shift_id', '=', $shiftId)
            ->get();

        return $notes;
    }

    public function postCase($locId, $title){

        $cases = new Cases;
        $cases->location_id = $locId;
        $cases->title = $title;

        if($cases->save()){
            $id = $cases->id;

            return $id;
        }else{

            return 0;
        }
    }

    public function postCaseNote($userId, $shiftId, $caseId, $title, $posId = null, $desc = null, $img = null){

        $caseNote = new CaseNote;
        $caseNote->title = $title;
        $caseNote->img = $img;
        $caseNote->description = $desc;
        $caseNote->user_id = $userId;
        $caseNote->shift_id = $shiftId;
        $caseNote->case_id = $caseId;
        $caseNote->curr_loc_id = $posId;

        if($caseNote->save()){
            $id = $caseNote->id;

            return $id;
        }else{

            return 0;
        }

    }

    /*purpose: insert record into Case Files table
    //can be used for any file
    //Request needs to contain:
    length, userId, filepath
    the first pms are the required, the optional parameters are not required in db
   */
    public function postCaseFile($caseId, $userId, $filepath, $caseNoteId = null){

        //post filepath in case_files table
        $file = new CaseFile;
        $file->case_id = $caseId;
        $file->file = $filepath;
        $file->user_id = $userId;
        $file->case_note_id = $caseNoteId;

        if ($file->save()) {

            $id = $file->id;
            return $id;
        }else{

            return 0;
        }
    }

    //returns $collection with a fileArray added to each object
    //purpose: where a collection has a case_id, append files to object where the case_file case_id matches
    public function appendCaseFiles($files, $collection){

        if(sizeof($files) > 0) {
            foreach ($collection as $i => $collect) {

                $fileArray = [];

                foreach ($files as $file){

                    if ($file->case_id == $collection[$i]->case_id) {
                        array_push($fileArray,$file->file);
                    }
                }

            $collection[$i]->files = $fileArray;
            }
        }

        return $collection;

    }

//    public function loopCaseFile($request, $caseId, $caseNoteId = null){
//
//        if ($request->has('length')) {
//
//            $length = $request->input('length');
//            $userId = $request->input('userId');
//            $numFilesSaved = 0;
//
//            //post filepath to the case_files table
//            for ($i = 0; $i < $length; $i++) {
//
//                $filepath = $request->input('file' + i);
//
//                $caseFileId = $this->postCaseFile($caseId, $userId, $filepath, $caseNoteId);
//
//                if($caseFileId != 0){
//                    $numFilesSaved++;
//                }
//            }
//            return $numFilesSaved;
//
//        }else{
//
//            return 0;
//        }
//    }

}