<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Notifications\NewMobileUser;
use Illuminate\Support\Facades\Storage;
use MicrosoftAzure\Storage\Common\ServicesBuilder;
use Carbon\Carbon;

/*Notify odin primary email that new company registered*/
use App\Notifications\RegisterCompany;
use App\Events\CompanyRegistered;

/*webhooks post and event*/
use App\Events\EmailDropped;
use App\OdinErrorLogging as AppErrors;


Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index');

/*Reset Password*/

/*Usage: Mobile and Console have tailored reset pw routes which call a different fn
than the reset pw from the api does (the api fn is used when adding new employees and users)
*/

/*Usage: Tailored forgot pw link for mobile and console so that only this route is
excluded from csrf token routes (see app/http/middleware) not those pw resets that are
implemented through the api ie for new users and employees
CSRF_Token excluded route*/
Route::post('/user/new/pw', 'Auth\ForgotPasswordController@sendResetLinkEmailClient');

/*Tailored confirm page following a completed password reset*/
Route::get('/password/confirm', function () {
//    Auth::logout();TODO: try this fix for forgot passwords
    return view('auth/passwords/confirm_reset');
});

//register new user and company for console
//CSRF_Token excluded route
Route::post('/company', function (Request $request) {

    $emailRegister = $request->input('email_user');

    //$checkEmail will be a single string
    $checkEmail = App\User::where('email', '=', $emailRegister)->select('email')->first();

    if($checkEmail != null){
        //email exists, don't create the company
        //and save a value in the $nonUnique variable to be checked in the console and if the variable holds this value,
        //return a relevant msg to the individual attempting to register
        $nonUnique = "Not Unique";

        return response()->json([
            'success' => false,
            'nonUnique' => $nonUnique
        ]);

    }else {

        $company = new App\Company;

        $company->name = $request->input('company');
        $company->owner = $request->input('owner');
        $company->primary_contact = 0;//awaiting creation of user
        $company->status = 'incomplete';

        $company->save();

        //retrieve id of last insert
        $compId = $company->id;

        $user = new App\User;

        $user->first_name = $request->input('first_name');
        $user->last_name = $request->input('last_name');
        $user->email = $emailRegister;

        $password = $request->input('pw');
        $pwEnc = Hash::make($password);
        $user->password = $pwEnc;

        $user->company_id = $compId;
        $user->remember_token = str_random(10);
        $user->save();

        //retrieve id of last insert
        $id = $user->id;

        //add the user_id to the company as the primary contact for emails
        $comp = App\Company::find($compId);
        $comp->primary_contact = $id;
        $comp->save();

        //save User role
        $userRole = new App\UserRole;
        //the first user registered during registration process is assigned top level access ie Manager
        $userRole->role = 'Manager';
        $userRole->user_id = $id;
        $userRole->save();

        //retrieve saved user for notification
        $newuser = App\User::find($id);

        //send email to new company requesting activation of the company which enables login and
        // validates address belongs to the new user
        $newuser->notify(new RegisterCompany($compId));

        //event to notify Odin admin that a new company has registered
        event(new CompanyRegistered($comp));

        return response()->json([
            'success' => $newuser,
            'checkEmail' => $checkEmail,

        ]);
    }
});

Route::get('/activate/{compId}', 'MainController@activate');

//mobile: upload an image during a new case note
//CSRF_Token excluded route
//will work for any file
//returns "" if no file,
// or returns "fail" if too many uploads with the same filename in the request,
// or returns filename on server if succeeds.
//Important: filename cannot have a fullstop or will fail to remove extension accurately
Route::post('/upload', function (Request $request) {
    try {

        if ($request->hasFile('file')) {

            $filename = $request->input('fileName');

            //prior to storing the file, check if file with that filename exists
            $exists = Storage::disk('azure')->exists($filename);

            if($exists != true) {

                $path = $request->file('file')->storeAs('/', $filename);
            }else{
                $path = 'file already exists';
            }

        } else {
            $path = "";
        }

        return response()->json($path);

    }catch(Exception $e){

        return response()->json('exception');

    }catch(ErrorException $err){
        return response()->json('error exception');

    }
});

//called from update markers() see route in api.php
Route::get("/dashboard/{compId}/current-positions", function ($compId) {

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

//route to provide a url to an image stored in azure storage container
Route::get('/download-photo/{filename}', function ($filename) {

    //check if file exists
    $exists = Storage::disk('azure')->exists($filename);

    if($exists) {
        $accountName = config('filesystems.disks.azure.name');
        $container = config('filesystems.disks.azure.container');
        $permissions = 'r';

        $todayTS = Carbon::now();//format eg 2018-02-06 12:00:44.000000
        $subStrYesterday = substr($todayTS->subDay(), 0, 10);
        $subStrTomorrow = substr($todayTS->addDays(2), 0, 10);//add 2 days for tomorrow as date mutated to yesterday

        $start = $subStrYesterday.'T23:59:00Z';//from moments before midnight yesterday
        $expiry = $subStrTomorrow.'T08:00:00Z';//til 8am tomorrow
        $version = '2017-04-17';
        $key = config('filesystems.disks.azure.key');
        $resourceType = 'b';
        $contentType = 'image/jpeg';

        $signature = getSASForBlob($accountName, $container, $filename, $permissions,
            $start, $expiry, $version, $contentType, $key);

        $url = getBlobUrl($accountName, $container, $filename, $permissions, $resourceType, $start, $expiry, $version, $contentType, $signature);

        return response()->json($url);
    }else{
        return response()->json(null);//returns {}empty object
    }

});

//WIP
//possibly problem is not logged in as an authorised user.
Route::post("/error-logging", function (Request $request) {

    $appErrors = new AppErrors;

    //required fields
    $appErrors->event = $request->input('event');
    $appErrors->recipient = $request->input('recipient');

    //nullable field
    if($request->has('description')) {

        $appErrors->description = $request->input('description');
    }

    $appErrors->save();

    //event to notify Odin admin that a new company has registered
    event(new EmailDropped($appErrors));

    return response()->json(['message' => 'post successful']);
});

//archived??? used when azure uploads were stored directly to server
Route::get('/storage/app/public/{file}', function ($file) {

    $url = asset('storage/app/public/'.$file);

    return response()->download($url);

});



/*Test Routes*/


//Route::get("/assignedshifts/{id}/edit/test", function ($id) {
//
//    //verify company first
//    $assignedObject = App\AssignedShift::find($id);
//
//    $verified = verifyCompany($assignedObject);
//
//    if(!$verified){
//
//        return response()->json($verified);//value = false
//    }
//
//
//    //if verified as being the same company, or if no record is returned from the query ie $assigned = {}
//    $assigned = DB::table('assigned_shifts')
//        ->join('assigned_shift_employees', 'assigned_shift_employees.assigned_shift_id', '=', 'assigned_shifts.id')
//        ->join('assigned_shift_locations', 'assigned_shift_locations.assigned_shift_id', '=', 'assigned_shifts.id')
//        ->where('assigned_shifts.id', '=', $id)
//        ->where('assigned_shift_locations.deleted_at', '=', null)
//        ->where('assigned_shift_employees.deleted_at', '=', null)
//        ->orderBy('start', 'asc')
//        ->orderBy('assigned_shift_locations.location_id')
//        ->get();
//
//
//    foreach ($assigned as $i => $details) {
//        $emp = App\User::find($assigned[$i]->mobile_user_id);
//
//        //ensure the assigned_shift_employee record exists in the users table
//        if ($emp != null) {
//            $first_name = $emp->first_name;
//            $last_name = $emp->last_name;
//            $name = $first_name . ' ' . $last_name;
//        } //mobile_user_id does not exist in locations table
//        else {
//            $name = "Employee not in database";
//        }
//        //store location name in the object
//        $assigned[$i]->employee = $name;
//    }
//
//    foreach ($assigned as $i => $item) {
//
//        //find the location_id name if a location exists for that id in the locations table
//        $location = App\Location::find($assigned[$i]->location_id);
//
//        if ($location != null) {
//            $name = $location->name;
//        } //location_id does not exist in locations table
//        else {
//            $name = "Location not in database";
//            $assigned[$i]->checks = 0;
//        }
//        //store location name in the object
//        $assigned[$i]->location = $name;
//    }
//
//    return response()->json($assigned);
//});


//get currently authorized user
//retrieve an assigned shift
//Route::get("/assignedshift/{id}/test", function ($id) {
//
//    $assigned = App\AssignedShift::find($id);
//
//    $verified = verifyCompany($assigned);
//
//    if(!$verified){
//
//        return response()->json($verified);//value = false
//    }
//
//    //if verified as being the same company, or if no record is returned from the query ie $assigned = {}
//
//    return response()->json($assigned);
//
//});
//
////edit
//Route::get("/locations/{id}/edit/test", function ($id) {
//
//    $location = App\Location::find($id);
//
//    $verified = verifyCompany(
//        $location,
//        'locations',
//        'location_companies',
//        'locations.id',
//        'location_companies.location_id'
//    );
//
//    if(!$verified){
//
//        return response()->json($verified);//value = false
//    }
//
//    //if verified as being the same company, or if no record is returned from the query ie $assigned = {}
//    return response()->json($location);
//});
//
//Route::get("/report/{id}/test", function ($id) {
//
//    $report = App\Report::find($id);
//
//    $verified = verifyCompany($report);
//
//    if(!$verified){
//
//        return response()->json($verified);//value = false
//    }
//
//    return response()->json($report);
//});

//Route::get("/webhooks", function () {
//
//    dd(session('event'));
//
//    return view('webhooks');
//});

//so have a route in the console which calls the post which stores the data in the db (if we need the data stored in the db??)
//I started thinking perhaps not so necessary. we really just need the event/nofitication however this relies on the recipient
//of the alert but so would any list on a page etc. I think a list on a page as well as a notification, and then actions against the logs.
//also great to view them all at once.
//actions might not be so necessary if a subsequent event will action the item and archive it, say.




//Route::get("/error-logging/test", function () {
//
//    $event = 'test event';
//
//    $recipient = 'test@test.com.test';
//
//    $description = 'test description';
//
//    $appErrors = new AppErrors;
//
//    $appErrors->event = $event;
//    $appErrors->recipient = $recipient;
//    $appErrors->description = $description;
//
//    $appErrors->save();
//
//    return response()->json(['message' => 'post successful']);
//});

//Route::get("/individualreport/test/{reportId}", 'ReportApiController@getIndividualReport');

//Route::get("/testPostReportLocation", 'ReportApiController@testPostReportLocation');


//Route::get("/alert-admin/test", function () {
//
//    $comp = App\Company::find(444);
//
//    event(new CompanyRegistered($comp));
//    dd('check emails');
//
//});



//Test dynamic notifications
//1374 user id mailspace77
//Route::get("/dynamic-notification/test/{id}", function ($id) {
//
//    $user = App\User::find($id);
//
//        $emailOld = $user->email;
//
//        $emailNew = 'smurfettemum@gmail.com';
//
//
//            //new email address notification mail
//            $recipientNew = new DynamicRecipient($emailNew);
//
////            dd($recipientNew);
//    $compName = Company::where('id', '=', $user->company_id)->pluck('name')->first();
//
//
//    $recipientNew->notify(new ChangeEmailNew($compName));
//
//
////
////            //old email address notification mail
//            $recipientOld = new DynamicRecipient($emailOld);
//            $recipientOld->notify(new ChangeEmailOld($compName, $emailNew));
////
////            $user->email = $emailNew;
//    dd($recipientNew, $compName, $recipientOld);
//
//
//});

//Route::get('/uploadtest/{filename}', function ($filename) {
//    try {
//
////        if ($request->hasFile('file')) {
//
////            $filename = $request->input('fileName');
//
//
//            //prior to storing the file, check if file with that filename exists
//            $exists = Storage::disk('azure')->exists($filename);
//
//            if($exists != true) {
//                $path = "store";
//
////                $path = $request->file('file')->storeAs('/', $filename);
//            }else{
//                $path = 'file already exists';
//            }
//
////        } else {
////            $path = "";
////        }
//dd($path);
//        return response()->json($path);
//
//    }catch(Exception $e){
//
//        return response()->json('exception');
//
//
//    }catch(ErrorException $err){
//        return response()->json('error exception');
//
//    }
//});
//Route::get('/counttest', function () {
//
//    $checks = DB::table('shift_checks')
//        ->where('location_id', '=', 954)
//        ->select('id as shiftCheckId', 'check_duration')
//        ->get();
//
//    $totalChecks = $checks->count('shiftCheckId');
//dd($totalChecks);
//
//});

//Route::get('/testing/filename/exists', function () {
//
//    $filenameWithExt = '1518141908273.jpeg';
//
////    $exists = Storage::disk('azure')->exists('1518141908273'.'a'.$fileNameExt);
//
//    $filename = substr($filenameWithExt, 0, (strlen ($filenameWithExt)) - (strlen (strrchr($filenameWithExt,'.'))));
//    $fileNameExt = substr($filenameWithExt, -(strlen (strrchr($filenameWithExt,'.'))));
//
//    //prior to storing the file, check if file with that filename exists
//    $exists = Storage::disk('azure')->exists($filename.'a');
//
//    if($exists != true){
//        dd($exists);
//
//    } else{
////        dd($exists);
//        dd($filename.'a'.$fileNameExt);
//
//    }
//
//});

//Route::get('/testing/nofitication/fail', function () {
//
////    $newUser = App\User::find(974);//dds a bit of info
//    $newUser = App\User::find(974);//if user doesn't exist, fatal error
//
//    $newUser->notify(new NewMobileUser('test notification'));
//
////    dd($response);
//    return response()->json([
//        'success' => true
//    ]);
//
//});





