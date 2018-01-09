<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\CaseFile as CaseFile;
use App\CaseNote as CaseNote;
use App\Cases as Cases;


class CaseNoteApiController extends Controller
{
    public function getCaseNotes($compId)
    {
        $cases = DB::table('case_notes')
            ->join('cases', 'cases.id', '=', 'case_notes.case_id')
            ->join('case_files', 'case_notes.case_id', '=', 'case_files.case_id')
            ->join('users', 'users.id', '=', 'case_notes.user_id')
            ->where('users.company_id', '=', $compId)
            ->where('case_notes.deleted_at', '=', null)
            ->where('cases.deleted_at', '=', null)
            ->where('case_files.deleted_at', '=', null)
            ->select('case_notes.*', 'cases.location_id', 'users.first_name', 'users.last_name', 'case_files.file')
            ->orderBy('case_notes.created_at', 'desc')
            ->get();

        $locationIds = $cases->pluck('location_id');

        //retrieve location names
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

        return response()->json($cases);
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

    public function postCaseNote($userId, $shiftId, $caseId, $title, $desc = null, $img = null){

        $caseNote = new CaseNote;
        $caseNote->title = $title;
        $caseNote->img = $img;
        $caseNote->description = $desc;
        $caseNote->user_id = $userId;
        $caseNote->shift_id = $shiftId;
        $caseNote->case_id = $caseId;

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