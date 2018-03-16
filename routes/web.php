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

//Test Route Imports
use App\User as User;
use App\Company as Company;

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
//todo: check CSRF_Token excluded route??
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
        $company->owner = $request->input('owner');//either will hold a value or will be null
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

        //start trial period, nothing else required at this stage
        $user->trial_ends_at = Carbon::now()->addDays(90);

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

        /****send email to new company requesting activation of the company which enables login****/
        //and validates address belongs to the new user
        $newuser->notify(new RegisterCompany($compId));

        /****event to notify Odin admin that a new company has registered****/
        event(new CompanyRegistered($comp));

        /****add primary contact to active campaign contacts and add the trial tag to contact****/
        $tag1 = Config::get('constants.TRIAL_TAG');

        $tagUpperCase = ucwords($tag1);

        addUpdateContactActiveCampaign($newuser, $tag1, $comp, 'New Company Registration',
            'Attempted to add contact with tag: '.$tagUpperCase, 'Succeeded in adding contact with tag: '.$tagUpperCase);

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

                $file = $request->file('file');

                $path = $file->storeAs('/', $filename);

                //make a thumbnail and store in azure storage
                $img = resizeToThumb($file);

                Storage::put('thumb'.$filename, (string) $img->encode());

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

        $url = getBlobUrl($accountName, $container, $filename, $permissions, $resourceType, $start,
            $expiry, $version, $contentType, $signature);

        return response()->json($url);
    }else{
        return response()->json(null);//returns {}empty object
    }

});

//CSRF_Token excluded route
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

    event(new EmailDropped($appErrors));

    return response()->json(['message' => 'post successful']);
});

//Ready to implement once billing set up
///active/test/1374/404
Route::get("/active/test/{id}/{compId}", function ($id, $compId) {
    $tag1 = Config::get('constants.TRIAL_TAG');

    $newuser = App\User::find($id);

    $comp = App\Company::find($compId);
    $comp->name = 'Testing Active Campaign';
    $comp->save();

    $tagUpperCase = ucwords($tag1);

    addUpdateContactActiveCampaign($newuser, $tag1, $comp, 'New Company Registration',
        'Attempted to add contact with tag: '.$tagUpperCase, 'Succeeded in adding contact with tag: '.$tagUpperCase);

    return response()->json(['success' => true]);

});

Route::get("/active/test/start-paid-subscription", function () {

    $user = App\User::find(1374);

    $comp = App\Company::find(404);

    $removeTag = Config::get('constants.TRIAL_TAG');

    $removeTagUpperCase = ucwords($removeTag);

    removeTag($user, $removeTag, $comp, 'Start of Paid Subscription',
        'Attempted to remove tag: '. $removeTagUpperCase,
        'Succeeded in removing tag: '.$removeTagUpperCase);

    $addTag = Config::get('constants.PAID_CUSTOMER_TAG');

    $addTagUpperCase = ucwords($addTag);

    addTag($user, $addTag, $comp, 'Start of Paid Subscription',
        'Attempted to add tag: '. $addTagUpperCase,
        'Succeeded in adding tag: '.$addTagUpperCase
    );

    return response()->json(['success' => true]);

});

//archived??? used when azure uploads were stored directly to server
Route::get('/storage/app/public/{file}', function ($file) {

    $url = asset('storage/app/public/'.$file);

    return response()->download($url);

});


//WIP - will soft delete some aspects of company account, but incomplete
//fixme: change to delete method
//Route::get('/company/account/remove/{compId}/{userId}', 'CompanyAndUsersApiController@removeAccount');

/*Test Routes*/
//todo: remove by end of March
Route::get("/test/runtimes", function(){

    $ds = time();

    $result = app('App\Http\Controllers\JobsController')->getAssignedShiftsList(404);

    $de = time();

    $diff = $ds - $de;

    dd($diff);

});

Route::get("/misc/test", function () {

    echo url()->current();
//    dd($result);

});

