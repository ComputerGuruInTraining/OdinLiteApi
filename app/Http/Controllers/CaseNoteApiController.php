<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use App\CaseNote as CaseNote;
use Illuminate\Support\Facades\DB;


class CaseNoteApiController extends Controller
{
    public function getCaseNotes($compId)
    {

        $cases = DB::table('case_notes')
            ->join('cases', 'cases.id', '=', 'case_notes.case_id')
            ->join('users', 'users.id', '=', 'case_notes.user_id')
            ->where('users.company_id', '=', $compId)
            ->where('case_notes.deleted_at', '=', null)
            ->where('case.deleted_at', '=', null)
            ->groupBy('cases.location_id')
            ->orderBy('case_notes.case_id')
            ->get();

        //retrieve location names
//        $locations = DB::table('locations')
//            ->where('locations.id', '=', $cases->location_id)
//            ->select('id', 'name')
//            ->get();
//
//        //ensure there are $locations before adding the location names onto the end of case object
//        if(sizeof($locations) > 0) {
//            foreach ($cases as $i => $case) {
//                foreach ($locations as $location)
//                    if ($locations->id == $case->location_id) {
//                        $cases[$i]->location = $location->name;
//                    }
//            }
//        }

        return response()->json($cases);
    }

}
