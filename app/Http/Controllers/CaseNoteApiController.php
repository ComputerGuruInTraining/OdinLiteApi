<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
            ->where('cases.deleted_at', '=', null)
            ->select('case_notes.*', 'cases.location_id', 'users.first_name', 'users.last_name')
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

}