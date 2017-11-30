<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Report as Report;
use App\ReportCases as ReportCase;
use App\ReportCheckCase as ReportCheckCase;
use App\ShiftCheckCases as CheckCases;
use App\ReportCaseNotes as ReportCaseNote;
use App\CaseNote as CaseNote;
use App\Cases as Cases;
use App\ShiftCheck as ShiftCheck;
use App\ShiftCheckCases as ShiftCheckCase;


class ReportApiController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function postCaseNotes(Request $request)
    {
        //get request data
        $dateFrom = $request->input('dateFrom');
        $dateTo = $request->input('dateTo');
        $locId = $request->input('location');
        $type = $request->input('type');
        $compId = $request->input('compId');

        //convert the date strings to Carbon timestamps
        $dateStart = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom);
        $dateEnd = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo);
        //$ts = $dateStart->timestamp();

        //Retrieve the data for the location and date range needed to calculate totalHours and numGuards
        //NB: only retrieving shifts using shift_start_date, as ordinarily the shift_end_date would be the same or next day
        //get from table so that includes deletes from assigned_shifts so that the data can be provided in the report
//

        $shifts = $this->queryReport($dateStart, $dateEnd, $locId);

        //if there is data for the report, post to the report tables
        if (count($shifts) > 0) {

            $shiftIds = $shifts->pluck('id');

            //get the case notes for a report
            $caseNoteIds = $this->queryCaseNotes($locId, $shiftIds);

            if (count($caseNoteIds) > 0) {

                $result = $this->storeReport($dateStart, $dateEnd, $compId, $type);

                if ($result->get('error') == null) {

                    $id = $result->get('id');

                    //calculate the total hours///FIXME: incorrect hours at a location when considering a guard could visit several locations
//        $totalMins = $shifts->sum('duration');//duration is in minutes
//        $hours = $totalMins / 60;
//        $totalHours = floor($hours * 100) / 100;//hours to 2 decimal places


                    //add to report_cases table
                    $resultCase = $this->storeReportCase($id, $shifts, $locId);

                    if ($resultCase->get('error') == null) {

                        //variables needed to retrieve case_notes for the period and store in report_case_notes table
                        $reportCaseId = $resultCase->get('reportCaseId');

                        $resultNote = $this->storeReportCaseNote($reportCaseId, $caseNoteIds);

                        if ($resultNote->get('error') == null) {

                            return response()->json([
                                'success' => true
                            ]);
                        } else {
                            //error storing report_case notes
                            return response()->json([
                                'success' => false
                            ]);
                        }
                        //stored report and report case but not report case note
                        return response()->json([
                            'success' => false
                        ]);
                    } else {
                        //error storing report_case
                        return response()->json([
                            'success' => false
                        ]);
                    }

                    //shift data > 0 but error storing report
                    return response()->json([
                        'success' => false
                    ]);
                } else {
                    //error storing report
                    return response()->json([
                        'success' => false
                    ]);
                }
            } else {
                //no case notes, therefore don't generate report
                return response()->json([
                    'success' => false
                ]);
            }

        } else {
            //no shift data , count($shifts) == 0
            return response()->json([
                'success' => false
            ]);
        }

    }

    public function postCasesAndChecks(Request $request)
    {
        //get request data
        $dateFrom = $request->input('dateFrom');
        $dateTo = $request->input('dateTo');
        $type = $request->input('type');
        $compId = $request->input('compId');
        $locId = $request->input('location');

        //convert the date strings to Carbon timestamps
        $dateStart = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom);
        $dateEnd = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo);

        //check to see if there were shifts for the location during the period
        //otherwise will not create a report
        $shifts = $this->queryReport($dateStart, $dateEnd, $locId);

        //if there is data for the report, post to the report tables
        if (count($shifts) > 0) {

            //shift_ids
            $shiftIds = $shifts->pluck('id');

            //get the case notes for a report
            $caseNoteIds = $this->queryCaseNotes($locId, $shiftIds);

            //if there are no case notes, we will not generate a report
            //as the app structure requires case notes and therefore very rare for this not to be the case
            //and presume not enough data

            if (count($caseNoteIds) > 0) {

                $caseCheckIds = $this->queryShiftCheckCases($caseNoteIds);

                if (count($caseCheckIds) > 0) {


                    //insert into Reports table via function
                    $result = $this->storeReport($dateStart, $dateEnd, $compId, $type);

                    //report saved and id returned in $result
                    if ($result->get('error') == null) {

                        $reportId = $result->get('id');

                        $resultCase = $this->storeReportCase($reportId, $shifts, $locId);

                        if ($resultCase->get('error') == null) {

                            //variables needed to retrieve case_notes for the period and store in report_case_notes table
                            $reportCaseId = $resultCase->get('reportCaseId');

                            $resultNote = $this->storeReportCaseNote($reportCaseId, $caseNoteIds);

                            if ($resultNote->get('error') == null) {

                                $resultCheck = $this->storeReportCheckCase($reportCaseId, $caseCheckIds);

                                if ($resultCheck->get('error') == null) {

                                    return response()->json([
                                        //all inserts occurred successfully
                                        'success' => true
                                    ]);
                                } else {
                                    //no shift checks at the location
                                    //ie in the case of a single location that does not have check in and check out
                                    return response()->json([
                                        'success' => false
                                    ]);
                                }

                            } else {
                                //error storing report case notes
                                return response()->json([
                                    'success' => false
                                ]);
                            }
                            return response()->json([
                                'success' => false
                            ]);

                        } else {
                            //error storing report case
                            return response()->json([
                                'success' => false
                            ]);
                        }

                    } else {
                        //error storing report
                        return response()->json([
                            'success' => false
                        ]);
                    }
                } else {
                    //no shift checks, therefore presumably a single location with no location check data
                    return response()->json([
                        'success' => false
                    ]);
                }
            } else {
                //no case notes
                return response()->json([
                    'success' => false
                ]);
            }

        } else {
            //no shifts for the period at the location or no notes for the location during the shifts
            return response()->json([
                'success' => false
            ]);
        }
    }

    public function storeReportCheckCase($reportCaseId, $caseCheckIds)
    {
        //add to shift_check_cases table
        foreach ($caseCheckIds as $caseCheckId) {
            $reportChecks = new ReportCheckCase;
            $reportChecks->report_case_id = $reportCaseId;
            $reportChecks->shift_check_case_id = $caseCheckId;
            $reportChecks->save();
        }

        $error = array('error' => 'error');

        if ($reportChecks->save()) {
            return $caseCheckIds;
        } else {
            return $error;
        }
    }

    public function storeReportCaseNote($reportCaseId, $caseNoteIds)
    {
        //add to report_case_notes table
        foreach ($caseNoteIds as $caseNoteId) {
            $reportNotes = new ReportCaseNote;
            $reportNotes->report_case_id = $reportCaseId;
            $reportNotes->case_note_id = $caseNoteId;
            $reportNotes->save();
        }

        $error = array('error' => 'error');

        if ($reportNotes->save()) {
            return $caseNoteIds;
        } else {
            return $error;
        }
    }

    public function storeReport($dateStart, $dateEnd, $compId, $type)
    {
        //insert into the Reports table
        $report = new Report;

        $report->date_start = $dateStart;
        $report->date_end = $dateEnd;
        $report->company_id = $compId;
        $report->type = $type;

        $report->save();

        $id = $report->id;

        $reportData = collect(['id' => $id]);
        $error = collect(['error' => 'error']);

        if ($report->save()) {
            return $reportData;
        } else {
            return $error;
        }
    }

    public function storeReportCase($reportId, $shifts, $locId)
    {

        //calculate the total hours
        $totalMins = $shifts->sum('duration');//duration is in minutes
        $hours = $totalMins / 60;
        $totalHours = floor($hours * 100) / 100;//hours to 2 decimal places

        //calculate the number of guards
//        $numGuards = $shifts->count('mobile_user_id');
        $numGuards = $shifts->groupBy('mobile_user_id')->count();


        //add to report_cases table

        $reportCase = new ReportCase;
        $reportCase->report_id = $reportId;
        $reportCase->location_id = $locId;
        $reportCase->total_hours = $totalHours;
        $reportCase->total_guards = $numGuards;
        $reportCase->save();

        $reportCaseId = $reportCase->id;

        $data = collect(['reportCaseId' => $reportCaseId]);
        $error = collect(['error' => 'error']);

        if ($reportCase->save()) {
            return $data;
        } else {
            return $error;
        }

    }

    //get the location checks report data
    public function getCasesAndChecks($id)
    {
        try {

            //$reportCaseId will hold just one value for the report_case record that matches the report_id
            $reportCaseId = getTable2Id('report_cases', $id, 'report_id');

            $reportChecks = DB::table('report_check_cases')
                //single value to join on
                ->join('shift_check_cases', function ($join) {
                    //single value in where clause variable, array of report_case_notes with variable value
                    $join->on('shift_check_cases.id', '=', 'report_check_cases.shift_check_case_id');
                })
                ->join('report_cases', function ($join) use ($reportCaseId) {
                    //single value in where clause variable, array of report_case_notes with variable value
                    $join->on('report_cases.id', '=', 'report_check_cases.report_case_id')
                        ->where('report_check_cases.report_case_id', '=', $reportCaseId);
                })
                ->select('shift_check_cases.*', 'report_cases.*')
                ->get();

            //get shift_check data using the shift_check_cases.shift_check_id (array of different ids)
//            and case_note data using the shift_check_cases.case_note_id (array of different ids)

            $shiftCheckIds = $reportChecks->pluck('shift_check_id');

            $caseNoteIds = $reportChecks->pluck('case_note_id');

            $shiftChecks = DB::table('shift_check_cases')
                ->join('shift_checks', function ($join) use ($shiftCheckIds) {
                    //single value in where clause variable, array of report_case_notes with variable value
                    $join->on('shift_checks.id', '=', 'shift_check_cases.shift_check_id')
                        ->whereIn('shift_checks.id', $shiftCheckIds);
                })
                ->join('case_notes', function ($join) use ($caseNoteIds) {
                    //single value in where clause variable, array of report_case_notes with variable value
                    $join->on('case_notes.id', '=', 'shift_check_cases.case_note_id')
//                        ->where('case_notes.deleted_at', '=', null)//TODO
                        ->whereIn('case_notes.id', $caseNoteIds);
                })
                ->select('shift_checks.*', 'case_notes.case_id', 'case_notes.title', 'case_notes.user_id')
                ->get();

            //for each object,
            // 1: find the current_location details
            //2: add onto the end of the object
            //3: join reportChecks with shiftChecks

            //Note: not beyond reasonable possibility that the number of results returned could vary,
            //especially if a post fails, so cannot operate on the assumption will not vary

            foreach ($shiftChecks as $x => $item) {

                //functions.php
                //using the user_loc_id, gather details about the geoLocation from the current_user_locations table and add to the shiftChecks object
                //check_ins
                $geoIn = getGeoData($item->user_loc_check_in_id);

                $shiftChecks[$x]->checkin_latitude = $geoIn->get('lat');
                $shiftChecks[$x]->checkin_longitude = $geoIn->get('long');
                $shiftChecks[$x]->checkin_id = $geoIn->get('geoId');

                //check_outs
                $geoOut = getGeoData($item->user_loc_check_out_id);

                $shiftChecks[$x]->checkout_latitude = $geoOut->get('lat');
                $shiftChecks[$x]->checkout_longitude = $geoOut->get('long');
                $shiftChecks[$x]->checkout_id = $geoOut->get('geoId');

                foreach ($reportChecks as $j => $report) {

                    if ($shiftChecks[$x]->id == $reportChecks[$j]->shift_check_id) {

                        //add values onto the end of the shiftChecks object to correlate the data
                        $shiftChecks[$x]->shift_check_id = $reportChecks[$j]->shift_check_id;
                        $shiftChecks[$x]->shift_check_case_id = $reportChecks[$j]->id;
                        $shiftChecks[$x]->case_note_id = $reportChecks[$j]->case_note_id;
                        $shiftChecks[$x]->location_id = $reportChecks[$j]->location_id;
                        $shiftChecks[$x]->total_hours = $reportChecks[$j]->total_hours;
                        $shiftChecks[$x]->total_guards = $reportChecks[$j]->total_guards;
                        $shiftChecks[$x]->report_id = $reportChecks[$j]->report_id;
                    }
                }
            }

            //get employee's name using user_id from case_notes table
            $checkUserIds = $shiftChecks->pluck('user_id');

            $usersNames = userFirstLastName($checkUserIds);

            //add employee's name onto the end of the object data
            if ($usersNames != null) {
                foreach ($shiftChecks as $i => $item) {

                    foreach ($usersNames as $user) {
                        if ($shiftChecks[$i]->user_id == $user->id) {

                            //store name in the object
                            $shiftChecks[$i]->user = $user->first_name . ' ' . $user->last_name;
                        }
                    }
                }
            }

            //get location details
            $locationId = $shiftChecks->pluck('location_id')->first();
//             retrieve location using location_id from shiftChecks using table so as to still retrieve data if the location has been deleted.
            $location = locationAddressDetails($locationId);

            return response()->json([
                'shiftChecks' => $shiftChecks,
                'location' => $location,
                'success' => true
            ]);
        } //error thrown if no report_case record for a report_id ie when no shift falls within the date range for a location
        catch (\ErrorException $e) {
            return response()->json([
                'success' => false
            ]);
        }
    }

    //returns a collection or array?? of shifts
    public function queryReport($dateStart, $dateEnd, $locId)
    {
        //Retrieve the data for the location and date range needed to calculate totalHours and numGuards
        //NB: only retrieving shifts using shift_start_date, as ordinarily the shift_end_date would be the same or next day
        //get from table so that includes deletes from assigned_shifts so that the data can be provided in the report
        $shifts = DB::table('assigned_shift_locations')
            ->join('shifts', 'shifts.assigned_shift_id', '=', 'assigned_shift_locations.assigned_shift_id')
            ->where('assigned_shift_locations.location_id', '=', $locId)
            ->whereBetween('shifts.start_time', [$dateStart, $dateEnd])
            ->get();

        return $shifts;
    }

    public function queryCaseNotes($locId, $shiftIds)
    {
        //retrieve non-deleted case_notes that match the location and shifts in question
        //quirk: errors when trying to retrieve case_notes.id therefore subsequent query to get this data
        $notes = DB::table('case_notes')
            ->join('cases', 'case_notes.case_id', '=', 'cases.id')
            ->where('cases.location_id', '=', $locId)
            ->whereIn('case_notes.shift_id', $shiftIds)//whereIn equal to an array of shiftIds
            ->where('cases.deleted_at', '=', null)
            ->where('case_notes.deleted_at', '=', null)
            ->get();

        //these ids will be the case_ids for the query results (not the case_note_ids)
        $caseIds = $notes->pluck('case_id');

        $caseNotes = CaseNote::whereIn('case_id', $caseIds)->get();

        $caseNoteIds = $caseNotes->pluck('id');

        return $caseNoteIds;
    }

    public function queryShiftCheckCases($noteIds)
    {

        //retrieve from ShiftCheckCases the records for those shift_checks
        //ie all checks and the case notes created during the checks for the shifts
        $caseChecks = ShiftCheckCase::whereIn('case_note_id', $noteIds)->get();

        $caseCheckIds = $caseChecks->pluck('id');

        return $caseCheckIds;
    }

}
