<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

use App\Notifications\NewUser;
use App\Notifications\NewMobileUser;
use App\Notifications\ChangePW;
use App\Notifications\ChangeEmailNew;
use App\Notifications\ChangeEmailOld;
use App\Notifications\NewMobileUserExistingUser;

use App\User as User;
use App\Location as Location;
use App\AssignedShift as Assigned;
use App\Company as Company;
use App\AssignedShiftEmployee as AssignedEmp;
use App\AssignedShiftLocation as AssignedLoc;
use App\LocationCompany as LocationCo;
use App\Shift as Shift;
use App\CurrentUserLocation as Position;
use App\UserRole as Role;
use App\Report as Report;
use App\ReportCases as ReportCase;
use App\ReportCaseNotes as ReportCaseNote;
use App\CaseNote as CaseNote;
use App\Cases as Cases;
use App\CaseFile as CaseFile;
use App\ShiftCheck as ShiftCheck;
use App\ShiftCheckCases as CheckCases;
use App\Employee as Employee;
use App\Recipients\DynamicRecipient;

/*---------------User----------------*/

Route::group(['middleware' => 'auth:api'], function () {

    //get currently authorized user
    Route::get('/user', function () {
        return Auth::user();
    });

    //get a user by id //todo: except pw
    Route::get("/user/{id}", function ($id) {
        $user = App\User::find($id);
        return response()->json($user);
    });

    //create User (console)
    Route::post("/user", function (Request $request) {

        $user = new App\User;

        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->email = $request->input('email');
        $password = str_random(8);
        $pwEnc = Hash::make($password);
        $user->password = $pwEnc;
        $user->company_id = $request->input('company_id');
        $user->remember_token = str_random(10);
        //the default value of true for column make_change_pw will be set for new users
        //helpful if console users required to change pw
        $user->save();

        //retrieve id of last insert
        $id = $user->id;
        $newuser = User::find($id);
        $compName = Company::where('id', '=', $request->input('company_id'))->pluck('name')->first();

        //save User role
        $userRole = new App\UserRole;
        $userRole->role = $request->input('role');
        $userRole->user_id = $id;
        $userRole->save();

        if ($userRole->save()) {
            //notify user they were added to the system
            //and send through a Create Password link
            $email = $newuser->notify(new NewUser($compName));

            return response()->json([
                'success' => true,
                'email' => $email
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }
    });


    //edit
    Route::get("/user/{id}/edit", function ($id) {
        $user = App\User::find($id);
        return response()->json($user);
    });

    //Update Console user
    Route::put("/user/{id}/edit", function (Request $request, $id) {

        $user = App\User::find($id);

        if ($request->has('first_name')) {
            $user->first_name = $request->input('first_name');
        }

        if ($request->has('last_name')) {
            $user->last_name = $request->input('last_name');
        }

        if ($request->has('email')) {

            //before changing the email, check the email has changed,
            //if so, email the employee/mobile user's new email address,
            $emailOld = $user->email;

            $emailNew = $request->input('email');

            if ($emailNew != $emailOld) {
                //email the new email address and old email address and advise the employee changed
                $compName = Company::where('id', '=', $user->company_id)->pluck('name')->first();

                //new email address notification mail
                $recipientNew = new DynamicRecipient($emailNew);
                $recipientNew->notify(new ChangeEmailNew($compName));

                //old email address notification mail
                $recipientOld = new DynamicRecipient($emailOld);
                $recipientOld->notify(new ChangeEmailOld($compName, $emailNew));

                $user->email = $emailNew;
            }
        }

        $user->save();

        if($request->has('role')){

            $userRole = App\UserRole::where('user_id', '=', $id)->first();

            $userRole->role = $request->input('role');
            $userRole->save();
        }

        if ($user->save()) {
            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }
    });

    //Update Mobile user password
    Route::put("/user/{id}/change-pw", function (Request $request, $id) {
        $user = App\User::find($id);

        $oldPw = $request->input('old');

        $hashedPassword = Auth::user()->password;  // Taking the value from database

        //if the current password entered by user equals the password stored in the database
        if (Hash::check($oldPw, $hashedPassword)) {

            if ($request->has('password')) {
                $password = $request->input('password');
                $pwEnc = Hash::make($password);
                $user->password = $pwEnc;
            }

            $amount = $user->save();

            //gather info for email notification
            $id = $user->id;
            $userPw = User::find($id);
            $compName = Company::where('id', '=', $userPw->company_id)->pluck('name')->first();

            //notify user their password has been changed in the mobile app
            if ($amount == 1) {
                $userPw->notify(new ChangePW($compName));
            }

//        TODO: if email is successful
            if ($amount == 1) {
                return response()->json([
                    'success' => true,
                    'pw' => true
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'pw' => true
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'pw' => false
            ]);
        }
    });

    //delete user
    //soft delete
    Route::delete('/user/{id}', function ($id) {

        User::where('id', $id)->delete();

        Role::where('user_id', $id)->delete();


        return response()->json([
            'success' => true
        ]);
    });

    Route::get("/user/list/{compId}", function ($compId) {

        //check the user_roles table and if a user_id is in there, don't retrieve
        $users = DB::table('users')
            ->join('user_roles', 'user_roles.user_id', '=', 'users.id')
            ->where('users.company_id', '=', $compId)
            ->where('user_roles.deleted_at', '=', null)
            ->where('users.deleted_at', '=', null)
            ->get();

        return response()->json($users);//previously variable named $emps just in case error occurs
    });

    //get a list of users that are not already added as employees
    Route::get("/user/add-emp/{compId}", function ($compId) {

        //check the user_roles table and if a user_id is in there, don't retrieve
        $userIds = DB::table('users')
            ->select('users.id as userId')
            ->join('user_roles', 'user_roles.user_id', '=', 'users.id')
            ->where('users.company_id', '=', $compId)
            ->where('user_roles.deleted_at', '=', null)
            ->where('users.deleted_at', '=', null)
            ->get();

        //make an array of userIds for checking the employees table
        $userIdsArray = $userIds->pluck('userId');

        //check the employees table to see if the user exists as an employee
        $empUserIds = DB::table('employees')
            ->select('user_id')
            ->whereIn('user_id', $userIdsArray)
            ->get();
//
//        //TODO: check result in $emps should be an array of user_ids, else use the pluck
        $empIds = $empUserIds->pluck('user_id');
//
//        //check the userIds against the empIds and make a new array
//        //which is made up of the ids that don't appear in empIds,
//        //ie the userIds that are not already employees with empIds
        $nonEmpIds = $userIdsArray->diff($empIds);
//
//        //retrieve user details for nonEmpIds ie users that are not employees
        $users = DB::table('users')
            ->select('id', 'first_name', 'last_name')
            ->whereIn('id', $nonEmpIds)
            ->get();

        return response()->json($users);//previously variable named $emps just in case error occurs
    });

    /*---------------User Roles----------------*/

    Route::get("/user/role/{id}", function ($id) {
        $role = Role::where('user_id', '=', $id)->pluck('role');
        return response()->json($role);
    });

//get all users/employees/mobile_users  --> sql raw select * from users  left outer join user_roles on users.id = user_roles.user_id  where company_id= 1 and  user_roles.user_id is null;

    /*------------Company Status-----------*/
    Route::get('/status/{compId}', function ($compId) {
        $status = Company::where('id', '=', $compId)->pluck('status')->first();
        return response()->json($status);
    });

    /*---------------------Employees(Mobile Users)---------------*/
    Route::get("/employees/list/{compId}", function ($compId) {

        $employees = DB::table('users')
            ->join('employees', 'users.id', '=', 'employees.user_id')
            ->where('users.company_id', '=', $compId)
            ->where('employees.deleted_at', '=', null)
            ->where('users.deleted_at', '=', null)
            ->get();
        return response()->json($employees);
    });

    //adding new employees(mobile users) through console (not already a user of console)
    Route::post("/employees", function (Request $request) {
        $employee = new Employee;
        $user = new User;
        $email = $request->input('email');

        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->email = $request->input('email');
        $password = str_random(8);
        $pwEnc = Hash::make($password);
        $user->password = $pwEnc;
        $user->company_id = $request->input('company_id');
        $user->remember_token = str_random(10);
        //the default value of true for column make_change_pw will be set for new employees

        $user->save();
        //return response()->json($id);
        //retrieve id of last insert
        $user_id = $user->id;

        $employee->dob = $request->input('dateOfBirth');
        $employee->mobile = $request->input('mobile');
        $employee->gender = $request->input('sex');
        $employee->user_id = $user_id;
        $employee->save();

        //for emailing, grab the user from the db
        $newUser = User::find($user_id);

        $compName = Company::where('id', '=', $request->input('company_id'))->pluck('name')->first();

        if ($employee->save()) {
            $newUser->notify(new NewMobileUser($compName));

            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }

    });

    //adding existing users as employees(mobile users) through console (already a console user)
    Route::post("/employees/{userId}", function (Request $request, $userId) {

        $employee = new Employee;

        $employee->dob = $request->input('dateOfBirth');
        $employee->mobile = $request->input('mobile');
        $employee->gender = $request->input('sex');
        //user_id of the existing user, sent through in url
        $employee->user_id = $userId;
        $employee->save();

        //for emailing, grab the user from the db
        $newEmp = User::find($userId);

        $compName = Company::where('id', '=', $newEmp->company_id)->pluck('name')->first();

        if ($employee->save()) {
            $newEmp->notify(new NewMobileUserExistingUser($compName));

            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }
    });

    //Edit Employees (ie mobile users)
    Route::get("/employees/{id}/edit", function ($id) {
        //not sure if it;s a good way to get a record. Might get multiple record in future but we only need one to show up in the Edit Page of employee
        $employees = DB::table('users')
            ->join('employees', 'users.id', '=', 'employees.user_id')
            ->where('users.id', '=', $id)
            ->get();

        return response()->json($employees);
    });

    //update record of employees (mobile users)
    Route::put("/employees/{id}/update", function (Request $request, $id) {
        try {
            //update employee
            $employee = App\Employee::where('user_id', $id)->first();

            $employee->mobile = $request->input('mobile');

            $employee->dob = $request->input('dateOfBirth');

            $employee->gender = $request->input('sex');

            $employee->save();

            //update user
            $user = App\User::find($id);

            $user->first_name = $request->input('first_name');

            $user->last_name = $request->input('last_name');

            $emailNew = $request->input('email');
            //before changing the email, check the email has changed,
            //if so, email the employee/mobile user's new email address,
            //IF it emails successfully, change the email,
            //ELSE don't and make the changes besides the email address
            //and advise console user the email address was not changed because email not valid (ie real)
            $emailOld = $user->email;
            $msg = '';
            $response = '';

            if ($emailNew != $emailOld) {
                //email the new email address and old email address and advise the employee changed
                $compName = Company::where('id', '=', $user->company_id)->pluck('name')->first();

                //FIXME: atm there is no check for if email delivered successfully.
                //$reponse has a value of null either way.

                //new email address notification mail
                $recipientNew = new DynamicRecipient($emailNew);
                $response = $recipientNew->notify(new ChangeEmailNew($compName));

                //old email address notification mail
                $recipientOld = new DynamicRecipient($emailOld);
                $recipientOld->notify(new ChangeEmailOld($compName, $emailNew));


//                $response = $user->notify(new ChangeEmailNew($compName));//worked on user

                //check to ensure the email was successful
                //if notification event etc...

                $user->email = $emailNew;

                $user->save();
                // }
                // else{
                // don't change the email because the email is invalid (email sending unsuccessfuly)
                // $user->save();
                // $msg = "email invalid";
                // }

            } else {
                //don't change the email because it hasn't changed
                $msg = "email unchanged";
                $user->save();
            }
            return response()->json([
                'success' => true,
                'msg' => $msg
            ]);

        } catch (\ErrorException $e) {

            return response()->json([
                'error' => $e
            ]);
        }
    });


    //Employees(mobile users) Soft Delete From Console
    //TODO: better implement test of delete here and throughout app
    Route::delete('/employees/{id}', function ($id) {

        $user = User::where('id', $id)->delete();

        $employee = Employee::where('user_id', $id)->delete();

        //also delete from assigned_shift_employees table
        AssignedEmp::where('mobile_user_id', $id)->delete();

        if (($employee != null) && ($user != null)) {
            return response()->json([
                'success' => true
            ]);
        } //delete in one of the tables did not work properly
        else if (($employee != null) || ($user != null)) {
            return response()->json([
                'success' => false,
                'table' => 'one',
            ]);
        } //delete in both of the tables did not work properly
        else {
            return response()->json([
                'success' => false
            ]);
        }

    });

    /*-----------Dashboard-----------*/
    //called upon dashboard intial load see route in web.php
    Route::get("/dashboard/{compId}/current-location", function ($compId) {

        $res = DB::table('current_user_locations')
            ->select('current_user_locations.mobile_user_id', 'current_user_locations.address',
                'current_user_locations.latitude', 'current_user_locations.longitude',
                'current_user_locations.shift_id', 'current_user_locations.user_first_name',
                'current_user_locations.user_last_name', 'current_user_locations.location_id',
                'current_user_locations.created_at')
            ->join('employees', 'current_user_locations.mobile_user_id', '=', 'employees.user_id')
            ->join('users', 'employees.user_id', '=', 'users.id')
            ->join('shifts', 'shifts.mobile_user_id', '=', 'current_user_locations.mobile_user_id')
            ->join(DB::raw('(SELECT mobile_user_id, MAX(created_at) MaxDate 
               FROM `current_user_locations` GROUP BY mobile_user_id) t2'), function ($join) {
                $join->on('current_user_locations.mobile_user_id', '=', 't2.mobile_user_id');
                $join->on('current_user_locations.created_at', '=', 't2.MaxDate');
            })
            ->where('users.company_id', '=', $compId)
            ->where('employees.deleted_at', '=', null)
            ->where('users.deleted_at', '=', null)
            ->where('shifts.end_time', '=', null)
            ->where('shifts.start_time', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 1 DAY)'))
            ->distinct()
            ->get();

//        $res = $res->groupBy('mobile_user_id');

        return response()->json($res);

    });

    Route::get("/dashboard/{compId}/company-detail", function ($compId) {

        $company = Company::find($compId);

        return response()->json($company);

    });

    /*--------Company Info (Console Settings)-----*/
    Route::get("/company/{compId}", function ($compId) {

        $company = Company::find($compId);

        $contact = User::find($company->primary_contact);

        return response()->json([
            'company' => $company,
            'contact' => $contact
        ]);

    });

    /*---------------Case Notes----------------*/

    Route::get("/casenotes/list/{compId}", 'CaseNoteApiController@getCaseNotes');

    Route::post("/casenote", function (Request $request) {

        //response variables initialised to false
        $caseSaved = false;
        $caseNoteSaved = false;
        $caseFileSaved = false;
        $sfkChkCaseSaved = false;
        $numFilesSaved = 0;

        //input values
        $userId = $request->input('userId');
        $locId = $request->input('locId');

        //convert to required string for db
        $location = Location::find($locId);
        $locName = $location->name;
        $time = Carbon::now();
        $title = $locName . ' ' . $time;

        //save to cases table
        $caseId = app('App\Http\Controllers\CaseNoteApiController')->postCase($locId, $title);

        //if cases table insert successfully... proceed with subsequent inserts
        if($caseId != 0) {

            $caseSaved = true;

            //save to case_notes table including the case_id
            //first, grab request data
            $title = $request->input('title');
            $shiftId = $request->input('shiftId');
            $posId = $request->input('posId');

            //description is not required for submit case note feature in mobile
            if ($request->has('description')) {

                $desc = $request->input('description');
                $caseNoteId = app('App\Http\Controllers\CaseNoteApiController')->postCaseNote($userId, $shiftId, $caseId, $title, $posId, $desc);

            } else {

                $caseNoteId = app('App\Http\Controllers\CaseNoteApiController')->postCaseNote($userId, $shiftId, $caseId, $title, $posId);
            }

            //if case_notes insert successful
            if ($caseNoteId != 0) {

                $caseNoteSaved = true;

                //save the case_note_id to shift_checks table to relate the data
                //if there is a shift_check (there won't be if only 1 location)
                if ($request->input('sftChkId') != 0) {

                    $sftChkId = $request->input('sftChkId');

                    $sftChkCaseId = app('App\Http\Controllers\JobsController')->postShiftCheckCase($caseNoteId, $sftChkId);

                    if ($sftChkCaseId != 0) {

                        $sfkChkCaseSaved = true;
                    }
                }
            }

            //insert into case_files if case insert successful, as can proceed even if case note insert fails for some reason, as case_note_id is not required in db ie nullable
//            $numFilesSaved = app('App\Http\Controllers\CaseNoteApiController')->loopCaseFile($request, $caseId, $caseNoteId);
            if ($request->has('length')) {

//fixme: loop through, but trouble passing through variable input key
//                $length = $request->input('length');

                //post filepath to the case_files table
//                for ($i = 0; $i < $length; $i++) {
//
//                    $file = 'file' + $i;

//                    $filepath = $request->input($file);

                if ($request->has('file0')) {

                    $file0 = $request->input('file0');

                    $caseFileId = app('App\Http\Controllers\CaseNoteApiController')->postCaseFile($caseId, $userId, $file0, $caseNoteId);

                    if ($caseFileId != 0) {
                        $numFilesSaved++;
                    }
                }

                if ($request->has('file1')) {

                    $file1 = $request->input('file1');

                    $caseFileId = app('App\Http\Controllers\CaseNoteApiController')->postCaseFile($caseId, $userId, $file1, $caseNoteId);

                    if ($caseFileId != 0) {
                        $numFilesSaved++;
                    }
                }

                if ($request->has('file2')) {

                    $file2 = $request->input('file2');

                    $caseFileId = app('App\Http\Controllers\CaseNoteApiController')->postCaseFile($caseId, $userId, $file2, $caseNoteId);

                    if ($caseFileId != 0) {
                        $numFilesSaved++;
                    }
                }

                if ($request->has('file3')) {

                    $file3 = $request->input('file3');

                    $caseFileId = app('App\Http\Controllers\CaseNoteApiController')->postCaseFile($caseId, $userId, $file3, $caseNoteId);

                    if ($caseFileId != 0) {
                        $numFilesSaved++;
                    }
                }

                if ($request->has('file4')) {

                    $file4 = $request->input('file4');

                    $caseFileId = app('App\Http\Controllers\CaseNoteApiController')->postCaseFile($caseId, $userId, $file4, $caseNoteId);

                    if ($caseFileId != 0) {
                        $numFilesSaved++;
                    }
                }
            }
        }

        //value will be true if saved successfully, or default false if not
        return response()->json([
            'caseSaved' => $caseSaved,
            'caseNoteSaved' => $caseNoteSaved,
            'caseFileSaved' => $caseFileSaved,
            'sfkChkCaseSaved' => $sfkChkCaseSaved,
            'numFilesSaved' => $numFilesSaved
        ]);
    });

    //console, edit a case note via report
    Route::get("/casenote/{id}/edit", function ($id) {
        $caseNote = App\CaseNote::find($id);

        //retrieve employee details even if employee has been deleted since making the case note
        //details required are in the User table
        $employee = App\User::withTrashed()
            ->where('id', '=', $caseNote->user_id)
            ->get();

        $firstName = $employee->pluck('first_name');
        $lastName = $employee->pluck('last_name');

        return response()->json([
            'caseNote' => $caseNote,
            'firstName' => $firstName,
            'lastName' => $lastName
        ]);
    });

    Route::put("/casenote/{id}/edit", function (Request $request, $id) {
        $casenote = CaseNote::find($id);

        if ($request->has('title')) {
            $casenote->title = $request->input('title');
        }
        if ($request->has('desc')) {
            $casenote->description = $request->input('desc');
        }

        if ($casenote->save()) {
            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }
    });

    //delete case note via console
    //TODO?? need to soft delete from:
    //case_notes table
    //report_case_notes table

    Route::delete('/casenote/{id}', function ($id) {

        //report_case_notes table
//        ReportCaseNote::where('case_note_id', $id)->delete();

        //case_notes table
        $deleted = CaseNote::find($id)->delete();

        //TODO: ensure record destroyed before returning success true

        if ($deleted != null) {
            return response()->json([
                'success' => true
            ]);
        }
    });

    /*---------------Reports----------------*/

    //retrieve a list of reports generated for a company
    Route::get("/reports/list/{compId}", 'ReportApiController@getReportList');

    //get basic details about a report
    Route::get("/report/{id}", function ($id) {

        $report = Report::find($id);

        $verified = verifyCompany($report);

        if(!$verified){

            return response()->json($verified);//value = false
        }

        return response()->json($report);
    });

    //insert a report of type = "Cases and Checks" || "Client" \\ "Management"
    Route::post("/reports/casesandchecks", 'ReportApiController@postCasesAndChecks');

    //insert a report of type = "Case Notes"
//    Route::post("/reports/casenotes", 'ReportApiController@postCaseNotes');

    //insert a report of type = "Individual"
    Route::post("/reports/individual/{userId}", 'ReportApiController@postIndividual');


    //soft delete a report and relevant tables by report_id
    Route::delete('/reports/{id}', function ($id) {
        try {
            //soft delete from reports table and report_cases and report_case_notes tables

            //find report item
            $report = Report::find($id);

            if ($report->type == "Case Notes") {

                $reportCase = ReportCase::where('report_id', '=', $id)->first();//potentially more than 1???

                ReportCaseNote::where('report_case_id', '=', $reportCase->id)->delete();

                $reportCase->delete();
            }
            //else for different types of reports....
            //{}

            //soft delete  using delete method on model
            $report->delete();

            //TODO: ensure record destroyed before returning success true
            return response()->json([
                'severalTables' => true
            ]);

        } //catch for the case of no data in report_case table due to no shift being completed at the location for the period
        catch (\ErrorException $e) {
            //just soft delete from report table
            $report = Report::find($id);

            $report->delete();

            return response()->json([
                'severalTables' => false
            ]);
        }
    });

    /*---------------Report Type = Location Checks----------------*/

    Route::get("/reportchecks/{id}", 'ReportApiController@getCasesAndChecks');

    /*---------------Report Type = Client/Management----------------*/

    Route::get("/locationreport/{id}", 'ReportApiController@getLocationReport');

    /*---------------Report Type = Individual----------------*/

    Route::get("/individualreport/{reportId}", 'ReportApiController@getIndividualReport');


    /*---------------Report Type = Case Notes----------------*/
//retrieve all case notes for a particular report_id where the case_note has not been deleted
    //parameter is report_id
    Route::get("/reportcases/{id}", function ($id) {
        //retrieve the report_case_id
        try {
            $reportCases = DB::table('report_cases')
                ->where('report_id', '=', $id)
                ->where('deleted_at', '=', null)
                ->first();

            $whereId = $reportCases->id;

            $reportCaseNotes = DB::table('report_case_notes')
                //single value to join on
                ->join('case_notes', function ($join) {
                    //single value in where clause variable, array of report_case_notes with variable value
                    $join->on('case_notes.id', '=', 'report_case_notes.case_note_id')
                        ->where('case_notes.deleted_at', '=', null);
                })
                ->join('report_cases', function ($join) use ($whereId) {
                    //single value in where clause variable, array of report_case_notes with variable value
                    $join->on('report_cases.id', '=', 'report_case_notes.report_case_id')
                        ->where('report_case_notes.report_case_id', '=', $whereId);
                })
                ->where('report_case_notes.deleted_at', '=', null)
                ->select('case_notes.*', 'report_case_notes.report_case_id')
                ->orderBy('case_notes.created_at', 'desc')
                ->get();


            $caseUsers = $reportCaseNotes->pluck('user_id');

            //need to display the employee first name and last name even if the employee
            //has been deleted since taking the case note, so get withTrashed
            $employees = User::withTrashed()->whereIn('id', $caseUsers)->get();

            if ($employees != null) {
                foreach ($reportCaseNotes as $i => $item) {

                    foreach ($employees as $employee) {
                        if ($reportCaseNotes[$i]->user_id == $employee->id) {

                            //store location name in the object
                            $reportCaseNotes[$i]->employee = $employee->first_name . ' ' . $employee->last_name;
                        }
                    }

                }
            }

            // retrieve location name using location_id from reportCases using table so as to still retrieve data if the location has been deleted.
            $location = DB::table('locations')
                ->where('id', '=', $reportCases->location_id)
                ->get()
                ->first();

            //empty stdclass reportCaseNotes object is returned when no report_case_notes for a report_case_id, but there is a report_case record
            //ie when no case_notes made for the shifts, but shifts have been completed for the date range at the location
            return response()->json([
                'reportCaseNotes' => $reportCaseNotes,
                'reportCases' => $reportCases,
                'location' => $location,
                'success' => true
            ]);
        } //error thrown if no report_case record for a report_id ie when no shift falls within the date range for a location
        catch (\ErrorException $e) {
            return response()->json([
                'success' => false
            ]);
        }

    });

    /*
      * Assigned Shifts
     */
    //retrieve an assigned shift
    //believe not in use atm, more thorough check required
    Route::get("/assignedshift/{id}", function ($id) {

        $assigned = App\AssignedShift::find($id);

        return response()->json($assigned);

    });

    //console
    //retrieve a list of assigned shifts for a particular company for roster page
    Route::get("/assignedshifts/list/{compId}", function ($compId) {

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
                $location = App\Location::find($assigned[$i]->location_id);

                if ($location != null) {

                    $name = $location->name;

                    //store location name in the object
                    $assigned[$i]->location = $name;
                }
            }


            foreach ($assigned as $i => $details) {
                //get the employee's name from the employees table for all employees assigned a shift
                $emp = App\User::find($assigned[$i]->mobile_user_id);
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
                    $assignedEmps = App\AssignedShiftEmployee::where('mobile_user_id', '=', $assigned[$i]->mobile_user_id)->get();
                    //then loop through those records, soft deleting each model
                    foreach ($assignedEmps as $assignedEmp) {
                        $assignedEmp->delete();
                    }
                    //Step: having deleted it from the table, we now need to remove the object from the $assigned array
                    $assigned->pull($i);
                }
            }
            //Step: reset the keys on the collection, else datatype is std::class
            $assigned = $assigned->values();

        return response()->json($assigned);
    });

    //mobile
    //route to get assigned shifts (ie complete roster) for a particular mobile_user/employee
    //that occur within the specified INTERVAL X DAY
    //and for which the shift has not ended
    Route::get("/assignedshifts/{id}", 'JobsController@getAssignedShifts');

    //mobile
    //route to get commenced assigned shifts for a particular mobile_user/employee
    //that occur within the specified INTERVAL X DAY
    //and for which the shift has started but not ended
    Route::get("/commencedshifts/{mobileuserid}", 'JobsController@getCommencedShifts');

    //mobile
    //get shift details already stored in db for a shift that has been started
    Route::get("/commencedshiftdetails/{assignedid}/{mobileuserid}", 'JobsController@getCommencedShiftDetails');

    //mobile
    //route to get the locations for a particular assigned_shift
    Route::get("/assignedshifts/locations/{asgnshftid}", function ($asgnshftid) {

        $assigned = app('App\Http\Controllers\JobsController')->getShiftLocations($asgnshftid);

        return response()->json($assigned);
    });

    //console
    //insert assigned shift into assigned_shifts, assigned_shift_locations and assigned_shift_employees tables
    Route::post("/assignedshifts", function (Request $request) {

        //table: assigned_shifts
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('start'));
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('end'));

        $assigned = new App\AssignedShift;

        $assigned->company_id = $request->input('comp_id');
        $assigned->shift_title = $request->input('title');
        $assigned->shift_description = $request->input('desc');
        $assigned->roster_id = $request->input('roster_id');

        $assigned->start = $start;
        $assigned->end = $end;
        $assigned->save();

        $id = $assigned->id;


        $employeeArray = $request->input('employees');

        $locationArray = $request->input('locations');

        $checksArray =  $request->input('checksArray');

        //for each employee...
        for ($emp = 0; $emp < sizeof($employeeArray); $emp++) {
            $employee = new App\AssignedShiftEmployee;
            $employee->mobile_user_id = $employeeArray[$emp];
            $employee->assigned_shift_id = $id;
            $employee->save();

        }

        //insert a job record for each location
//        if(sizeof($locationArray) > 1){
            for ($loc = 0; $loc < sizeof($locationArray); $loc++) {

                $location = new App\AssignedShiftLocation;
                $location->location_id = $locationArray[$loc];
                $location->assigned_shift_id = $id;

                //checks
                $location->checks = $checksArray[$loc];
                $location->save();
            }

//        }else{
//            $location = new App\AssignedShiftLocation;
//            $location->location_id = $locationArray[$loc];
//            $location->assigned_shift_id = $id;
//
//            //checks
//            $location->checks = $checksArray[$loc];
//            $location->save();
//
//        }


        //if($assigned->save()) {
        return response()->json([
            'success' => true
        ]);
        //  }else {
        //     return response()->json([
        //       'success' => false
        // ]);
        // }
    });

    //edit
    //with verification
    Route::get("/assignedshifts/{id}/edit", function ($id) {
        $assigned = DB::table('assigned_shifts')
            ->join('assigned_shift_employees', 'assigned_shift_employees.assigned_shift_id', '=', 'assigned_shifts.id')
            ->join('assigned_shift_locations', 'assigned_shift_locations.assigned_shift_id', '=', 'assigned_shifts.id')
            ->where('assigned_shifts.id', '=', $id)
            ->where('assigned_shift_locations.deleted_at', '=', null)
            ->where('assigned_shift_employees.deleted_at', '=', null)
            ->orderBy('start', 'asc')
            ->orderBy('assigned_shift_locations.location_id')
            ->get();

        $verified = verifyCompany($assigned);

        if(!$verified){

            return response()->json($verified);//value = false
        }

        //if verified as being the same company, or if no record is returned from the query ie $assigned = {}

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
            $location = App\Location::find($assigned[$i]->location_id);

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

        return response()->json($assigned);
    });

    Route::put("/assignedshifts/{id}/edit", 'JobsController@putShift');

    //soft delete from the assigned_shifts_table and the relations where assigned_shift_id is a fk
    Route::delete('/assignedshift/{id}', function ($id) {

        $assigned = Assigned::find($id);

        $assigned->delete();

        AssignedEmp::where('assigned_shift_id', '=', $id)->delete();

        AssignedLoc::where('assigned_shift_id', '=', $id)->delete();

        //TODO: ensure record destroyed before returning success true
        return response()->json([
            'success' => true
        ]);
    });


    //    /**
//     * Location
//     */

    Route::get("/locations/list/{compId}", function ($compId) {

        //get all location_ids for the company from Location_Companies table
        //using model so only returns non-deleted records
        $locationCos = LocationCo::where('company_id', '=', $compId)
            ->pluck('location_id');

        //get all locations where the id is equal to an array of location_ids
        $locations = Location::whereIn('id', $locationCos)->get();

        return response()->json($locations);
    });

    //show
    //not in use perhaps, need further checking
    Route::get("/location/{id}", function ($id) {
        $location = App\Location::find($id);
        return response()->json($location);
    });

    //get a location for a company  (used for centering map)
    Route::get("location/{compId}", function ($compId) {
        //get all location_ids for the company from Location_Companies table
        //using model so only returns non-deleted records
        $locationCos = LocationCo::where('company_id', '=', $compId)
            ->pluck('location_id');

        //get all locations where the id is equal to an array of location_ids
        $location = Location::whereIn('id', $locationCos)->first();

        return response()->json($location);
    });

    //edit
    Route::get("/locations/{id}/edit", function ($id) {

        $location = App\Location::find($id);

        $verified = verifyCompany(
            $location,
            'locations',
            'location_companies',
            'locations.id',
            'location_companies.location_id'
        );

        if(!$verified){

            return response()->json($verified);//value = false
        }

        //if verified as being the same company, or if no record is returned from the query ie $assigned = {}

        return response()->json($location);
    });

    Route::put("/locations/{id}/edit", function (Request $request, $id) {
        $location = App\Location::find($id);

        if ($request->has('name')) {
            $location->name = $request->input('name');
        }
        if (($request->has('address')) || ($request->input('address') == '')) {

            if ($request->input('address') != '') {
                $location->address = $request->input('address');
                $location->latitude = $request->input('latitude');
                $location->longitude = $request->input('longitude');
            }
        }
        if (($request->has('notes')) || ($request->input('notes') == '')) {
            $location->notes = $request->input('notes');
        }


        if ($location->save()) {
            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }

    });

    //insert to locations and location_companies table
    Route::post("/locations", function (Request $request) {

        $location = new App\Location;

        $location->name = $request->input('name');
        $location->address = $request->input('address');
        $location->latitude = $request->input('latitude');
        $location->longitude = $request->input('longitude');
        $location->notes = $request->input('notes');

        $location->save();
        //retrieve id of last insert
        $id = $location->id;

        //save location as current users company's location
        $locationCo = new LocationCo;
        $locationCo->location_id = $id;
        $locationCo->company_id = $request->input('compId');
        //$locationCo->save();

        if ($locationCo->save()) {
            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }
    });

    //soft delete
    Route::delete('/locations/{id}', function ($id) {

        //Assigned_shift_locations table
        AssignedLoc::where('location_id', $id)->delete();

        //Location_Companies table
        LocationCo::where('location_id', $id)->delete();

        $location = Location::find($id);

        //locations table
        $location->delete();

        AssignedLoc::where('location_id', $id)->delete();

        //for current_user_locations, if a location is deleted, make the location_id 0 for the deleted location
        $positions = Position::where('location_id', $id)->get();

        foreach ($positions as $position) {
            $position->location_id = 0;
            $position->save();
        }

        //for report_cases, if a location is deleted, make the location_id 0 for the deleted location
        $reportCases = ReportCase::where('location_id', $id)->get();

        foreach ($reportCases as $reportCase) {
            $reportCase->location_id = 0;
            $reportCase->save();

        }

        //TODO: ensure record destroyed before returning success true
        return response()->json([
            'success' => true
        ]);
    });

    /*
    *Current User Locations
    */

    //mobile
    Route::post('/currentlocation', function (Request $request) {

        //determine address//

        $latitude = $request->input('lat');
        $longitude = $request->input('long');

        //use latitude and longitude to determine address
        $converted = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $latitude . ',' . $longitude . '&key=AIzaSyAwMSIuq6URGvS9Sb-asJ4izgNNaQkWnEQ');
        $output = json_decode($converted);
        $address = $output->results[0]->formatted_address;

        //find user details//

        $userId = $request->input('userId');

        $user = User::find($userId);

        //store in db

        $position = new Position;

        $position->latitude = $latitude;
        $position->longitude = $longitude;
        $position->address = $address;
        $position->shift_id = $request->input('shiftId');
        $position->mobile_user_id = $userId;
        $position->user_first_name = $user->first_name;
        $position->user_last_name = $user->last_name;

        if ($request->input('locId') != 0) {
            $position->location_id = $request->input('locId');
        }
        $position->save();
        $id = $position->id;

        if ($position->save()) {
            return response()->json([
                'success' => true,
                'id' => $id
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);

        }
    });


    /* --------------- Shift --------------- */

    Route::post('/shift/start', function (Request $request) {

        $userId = $request->input('mobile_user_id');
        $user = User::find($userId);

        $shift = new Shift;

        $shift->assigned_shift_id = $request->input('assigned_shift_id');
        $shift->mobile_user_id = $userId;
        $shift->start_time = Carbon::now();//note: ts is in UTC time
        $shift->company_id = $user->company_id;
        $shift->save();

        //retrieve id of the saved shift
        $id = $shift->id;

        return response()->json([
            'success' => true,
            'id' => $id,
            'user' => $user
        ]);
    });

    Route::put('/shift/end', function (Request $request) {

        //retrieve current shift's record for update
        $shiftId = $request->input('shiftId');
        $shift = Shift::find($shiftId);

        $end = Carbon::now();

        //string returned from db
        $startStr = $shift->start_time;

        //convert string to a Carbon datetime object
        $startDT = new Carbon($startStr);

        //calculate duration in minutes and store in db
        $shift->duration = $end->diffInMinutes($startDT);
        $shift->end_time = $end;

        if ($shift->save()) {
            return response()->json([
                'success' => true,
                'shift' => $shift->duration
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }

    });

    //Called to log the shift check in
    Route::post('/shift/checks', function (Request $request) {

        $id = app('App\Http\Controllers\JobsController')->storeCheckIn($request);

        return response()->json([
            'success' => true,
            'id' => $id,
        ]);

    });
//
//        //use posId to get the latitude and the longitude of the geoLocation
//        //and compare with the location of the shiftCheck to determine if withinRange
//        $posId = $request->input('posId');
//        $locId = $request->input('locId');
//
//        //gets the geoLongitude, geoLatitude for the current_user_location_id
//        $currLocData = getGeoData($posId);
//
//        $geoLat = $currLocData->get('lat');
//        $geoLong = $currLocData->get('long');
//
//        $locData = app('App\Http\Controllers\LocationController')->getLocationData($locId);
//
//        $locLat = $locData->latitude;
//        $locLong = $locData->longitude;
//
//        $withinRange = app('App\Http\Controllers\LocationController')->withinRangeApi($geoLat, $geoLong, $locLat, $locLong);
//
//        $shift = new ShiftCheck;
//
//        //$shift->check_ins will take the default current_timestamp
//        $shift->shift_id = $request->input('shiftId');
//        $shift->user_loc_check_in_id = $posId;
//        $shift->location_id = $locId;//note: ts is in UTC time
//        $shift->checks = $request->input('checks');
//        $shift->within_range_check_in = $withinRange;
//        $shift->save();
//
//        $createdAt = $shift->created_at;
//
//        //retrieve id of the saved shift
//        $id = $shift->id;



    //called to log the shift check out
    Route::put('/shift/checkouts', function (Request $request) {

        $id = app('App\Http\Controllers\JobsController')->storeCheckOut($request);

//
//        //retrieve current shift's record for update
//        //using shift_id and location_id where check_outs null
//
//
//        $checkId = $request->input('shiftChecksId');
//        $check = ShiftCheck::find($checkId);
//
//        $checkOut = Carbon::now();
//
//        $check->user_loc_check_out_id = $request->input('posId');
//        $check->check_outs = $checkOut;

        if ($id == 'success') {
            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }

    });

    Route::get('/commencedshiftdetails/{assignedId}', 'JobsController@getCommencedShiftDetails');


});//end Route::group...