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


use App\User as User;
use App\Location as Location;
use App\AssignedShift as Assigned;
use App\Company as Company;
use App\AssignedShiftEmployee as AssignedEmp;
use App\AssignedShiftLocation as AssignedLoc;
use App\LocationCompany as LocationCo;
use App\ConsoleUser as ConsoleUser;
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
use App\Recipient;
use App\DynamicRecipient;

/*---------------User----------------*/

Route::group(['middleware' => 'auth:api'], function () {

    Route::get('/user', function () {
        return Auth::user();
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
        $userRole->role = 'Manager';
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

        $emailNew = $request->input('email');
        //before changing the email, check the email has changed,
        //if so, email the employee/mobile user's new email address,
        $emailOld = $user->email;

        if($emailNew != $emailOld){
            //email the new email address and old email address and advise the employee changed
            $compName = Company::where('id', '=', $user->company_id)->pluck('name')->first();

            //new email address notification mail
            $recipientNew = new DynamicRecipient($emailNew);
            $recipientNew->notify(new ChangeEmailNew($compName));

            //old email address notification mail
            $recipientOld = new DynamicRecipient($emailOld);
            $recipientOld->notify(new ChangeEmailOld($compName, $emailNew));

            $user->email = $emailNew;

            $user->save();
        }
        else{
            //don't change the email because it hasn't changed
            $user->save();
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
        if(Hash::check($oldPw, $hashedPassword)) {

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
        }
        else{
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

//TODO: rewrite route to get users only (console user) the below route is incorrect and somehow happens to result in giving console user.
    Route::get("/user/list/{compId}", function ($compId) {
        //all users in user_roles table are console users and therefore not employees using the mobile app
        $users = App\User::all();

        //check the user_roles table and if a user_id is in there, don't retrieve
        // which means table join
        $emps = DB::table('users')
            ->join('user_roles', 'user_roles.user_id', '=', 'users.id')
            ->where('users.id', '!=', 'user_roles.user_id')
            ->where('users.company_id', '=', $compId)
            ->where('users.deleted_at', '=', null)
            ->get();

        return response()->json($emps);
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
        //all users in user_roles table are console users and therefore not employees using the mobile app
        //$users = App\Employee::all();

        $employees = DB::table('users')
            ->join('employees', 'users.id', '=', 'employees.user_id')
            ->where('users.company_id', '=', $compId)
            ->where('employees.deleted_at', '=', null)
            ->where('users.deleted_at', '=', null)
            ->get();
        return response()->json($employees);
    });

    //adding employees(mobile users) through console
    Route::post("/employees", function (Request $request) {
        $employee = new Employee;
        $user = new User;
        $email = $request->input('email');

        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->email = $request->input('email');
        $user->password = $request->input('password');
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

            /* Mail::to($email)
                 ->send(new NewMobileUser($newUser));*/
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

            if($emailNew != $emailOld){
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

            }
            else{
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

    //mobile, insert a new case note
    Route::post("/casenote", function (Request $request) {

        //save to cases table
        $cases = new Cases;

        $locId = $request->input('locId');
        $location = Location::find($locId);
        $locName = $location->name;

        $time = Carbon::now();

        $cases->location_id = $locId;
        $cases->title = $locName . ' ' . $time;
        $cases->save();

        $id = $cases->id;

        //save to case_notes table including the case_id
        $case = new CaseNote;

        $case->title = $request->input('title');
        $case->img = $request->input('img');
        $case->description = $request->input('description');
        $case->user_id = $request->input('mobileUserId');
        $case->shift_id = $request->input('shiftId');
        $case->case_id = $id;
        $case->save();

        $noteId = $case->id;


        //save the case_note_id to shift_checks table to relate the data
        //if there is a shift_check (there won't be if only 1 location)
        if ($request->input('sftChkId') != 0) {

            $sftChkId = $request->input('sftChkId');
            $sftChk = new App\ShiftCheckCases;

            $sftChk->case_note_id = $noteId;
            $sftChk->shift_check_id = $sftChkId;
            $sftChk->save();
        }

        //ensure at least the case table and case note was successful
        if ($case->save()) {
            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }
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
    //need to soft delete from:
    //case_notes table
    //report_case_notes table

    Route::delete('/casenote/{id}', function ($id) {

        //report_case_notes table
        ReportCaseNote::where('case_note_id', $id)->delete();

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
    Route::get("/reports/list/{compId}", function ($compId) {
        $reports = App\Report::where('company_id', $compId)
            ->orderBy('date_start', 'asc')
            ->get();

        /********get location*******/

        //if type="CaseNotes" from report_cases table
        //  foreach($reports as $report){
        foreach ($reports as $i => $report) {
            $reportCase = DB::table('report_cases')
                ->join('locations', 'report_cases.location_id', '=', 'locations.id')
                ->where('report_cases.deleted_at', '=', null)
                ->where('report_id', '=', $reports[$i]->id)
                ->first();
            // ->get();

            $reports[$i]->location = $reportCase->name;
        }

        return response()->json($reports);

    });

    //get basic details about a report
    Route::get("/report/{id}", function ($id) {
        $report = Report::find($id);
        return response()->json($report);
    });

    //insert a report of type = "Cases and Checks"
    Route::post("/reports/casesandchecks", 'ReportApiController@postCasesAndChecks');

    //insert a report of type = "Case Notes"
    Route::post("/reports/casenotes", 'ReportApiController@postCaseNotes');

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


    /*---------------Report Cases----------------*/
//retrieve all case notes for a particular report_id where the case_note has not been deleted
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
                ->orderBy('case_notes.created_at')
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


            $name = $location->name;

            //store location name in the object
            $assigned[$i]->location = $name;
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
    //route to get assigned shifts for a particular mobile_user/employee
    Route::get("/assignedshifts/{id}", function ($id) {
        $assignedNow = DB::table('assigned_shifts')
            ->join('assigned_shift_employees', 'assigned_shift_employees.assigned_shift_id', '=',
                'assigned_shifts.id')
            ->join('shifts', 'assigned_shifts.id', '=', 'shifts.assigned_shift_id')
            ->select('shifts.assigned_shift_id')
            ->where('assigned_shift_employees.mobile_user_id', '=', $id)
            ->where('shifts.end_time', '=', null)
            ->where('assigned_shifts.end', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL 3 DAY)'))
            ->where('assigned_shifts.deleted_at', '=', null)
            ->where('assigned_shift_employees.deleted_at', '=', null)
            ->groupBy('shifts.assigned_shift_id')
            ->get();

        $assignedIds = $assignedNow->pluck('assigned_shift_id');

        $myAssigned = DB::table('assigned_shift_employees')
            ->join('assigned_shifts', 'assigned_shift_employees.assigned_shift_id', '=', 'assigned_shifts.id')
            ->whereIn('assigned_shifts.id', $assignedIds)
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
    });

    //mobile
    //route to get the locations for a particular assigned_shift
    Route::get("/assignedshifts/locations/{asgnshftid}", function ($asgnshftid) {

        $assigned = DB::table('assigned_shift_locations')
            ->join('locations', 'locations.id', '=', 'assigned_shift_locations.location_id')
            ->where('assigned_shift_locations.assigned_shift_id', '=', $asgnshftid)
            ->where('locations.deleted_at', '=', null)
            ->where('assigned_shift_locations.deleted_at', '=', null)
            ->get();

        return response()->json($assigned);
    });

    //console
    //insert assigned shift into assigned_shifts, assigned_shift_locations and assigned_shift_employees tables
    Route::post("/assignedshifts", function (Request $request) {

        //table: assigned_shifts
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('start'), 'America/Chicago');
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('end'), 'America/Chicago');

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
        //for each employee...
        for ($emp = 0; $emp < sizeof($employeeArray); $emp++) {
            $employee = new App\AssignedShiftEmployee;
            $employee->mobile_user_id = $employeeArray[$emp];
            $employee->assigned_shift_id = $id;
            $employee->save();
//            insert a job record for each location

        }

        for ($loc = 0; $loc < sizeof($locationArray); $loc++) {

            $location = new App\AssignedShiftLocation;
            $location->location_id = $locationArray[$loc];
            $location->assigned_shift_id = $id;
            $location->checks = $request->input('checks');
            $location->save();
        }

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

    Route::put("/assignedshifts/{id}/edit", function (Request $request, $id) {

        //table: assigned_shifts

        //TODO: variable datetimes
        $start = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('start'), 'America/Chicago');
        $end = Carbon::createFromFormat('Y-m-d H:i:s', $request->input('end'), 'America/Chicago');

        $assigned = App\AssignedShift::find($id);

        if ($request->has('compid')) {
            $assigned->company_id = $request->input('compId');
        }

        if ($request->has('title')) {
            $assigned->shift_title = $request->input('title');
        }

        if ($request->has('desc')) {
            $assigned->shift_description = $request->input('desc');
        }

        if ($request->has('roster_id')) {
            $assigned->roster_id = $request->input('roster_id');
        }

        $assigned->start = $start;
        $assigned->end = $end;

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
            $employee = new App\AssignedShiftEmployee;
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

        //assigned_shift_locations
        //update the locations assigned to the shift

        $newLocArray = $request->input('locations');
        $newLoc = collect($newLocArray);

        //current old assigned_location values to be edited that have not otherwise been deleted
        $oldLocs = DB::table('assigned_shift_locations')
            ->where('assigned_shift_id', '=', $id)
            ->where('deleted_at', '=', null)
            ->pluck('location_id');

        //create new records for those new locations updated in the view that aren't already in the assigned_shift_locations table
        $addLocs = $newLoc->diff($oldLocs);

        foreach ($addLocs as $addLoc) {
            $location = new App\AssignedShiftLocation;
            $location->location_id = $addLoc;
            $location->assigned_shift_id = $id;
            $location->checks = $request->input('checks');
            $location->save();
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

            $location = App\AssignedShiftLocation::find($toUpdateId);
            if ($location->checks != $request->input('checks')) {
                $location->checks = $request->input('checks');
                $location->save();
            }
        }

        if ($assigned->save()) {
            return response()->json(
                ['success' => true]
            );
        } else {
            return response()->json(
                ['success' => false]
            );
        }

    });

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

        //retrieve company id
        /*$consoleId = $request->input('consoleUserId');

        $consoleUser = ConsoleUser::find($consoleId);*/

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


        //$duration =

        //calculate duration in minutes and store in db
        $shift->duration = $end->diffInMinutes($startDT);
        $shift->end_time = $end;
        // $shift->save();

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

        $shift = new ShiftCheck;

        //$shift->check_ins will take the default current_timestamp
        $shift->shift_id = $request->input('shiftId');
        $shift->user_loc_check_in_id = $request->input('posId');
        $shift->location_id = $request->input('locId');//note: ts is in UTC time
        $shift->checks = $request->input('checks');
        $shift->save();

        $createdAt = $shift->created_at;

        //retrieve id of the saved shift
        $id = $shift->id;

        return response()->json([
            'success' => true,
            'id' => $id,
        ]);
    });

    //called to log the shift check out
    Route::put('/shift/checkouts', function (Request $request) {

        //retrieve current shift's record for update
        //using shift_id and location_id where check_outs null


        $checkId = $request->input('shiftChecksId');
        $check = ShiftCheck::find($checkId);

        $checkOut = Carbon::now();

        $check->user_loc_check_out_id = $request->input('posId');
        $check->check_outs = $checkOut;

        if ($check->save()) {
            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }

    });


});//end Route::group...