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

use App\Notifications\RegisterCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Notifications\NewMobileUser;
use Illuminate\Support\Facades\Storage;
use MicrosoftAzure\Storage\Common\ServicesBuilder;

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
        //$newcomp = App\Company::find($compId);

        $newuser->notify(new RegisterCompany($compId));

        //notify Odin admin that a new company has registered

        return response()->json([
            'success' => $newuser,
            'checkEmail' => $checkEmail,

        ]);
    }
});

Route::get('/activate/{compId}', 'MainController@activate');

//mobile: upload an image during a new case note
//CSRF_Token excluded route
//WIP for SAS, works for public urls
Route::post('/upload', function (Request $request) {
    try {

        if ($request->hasFile('file')) {

                    $path = $request->file('file')->storeAs('casenotes', $request->input('fileName'));

                    //start works, but download doesn't because jsut filename returned so leave as for the moment.
//            $file = $request->file('file');
//            $fileName = $request->input('fileName');
            //            $path = $fileName;


            //works with folder and file both being created because file object.
//            Storage::put($fileName,
//                $file);



            //end works

            //didn't work:
//            Storage::put(
//                $request->input('fileName'),
//                file_get_contents($file),
//                [
//
////                    'visibility' => 'public',
//                    'ContentType' => 'image/jpeg'
//
//                ]
//            );

            //didn't work
//            Storage::put(
//                $request->input('fileName'),
//                file_get_contents($file),
//                [
//
////                    'visibility' => 'public',
//                    'ContentType' => 'image/jpeg'
//
//                ]
//            );

//didn't work
//            Storage::put(
//                $request->input('fileName'),
//                $file,
//                [
//
////                    'visibility' => 'public',
//                    'ContentType' => 'image/jpeg'
//
//                ]
//            );
//
//            Storage::disk('azure')
//                ->getDriver()
//                ->put(
//                    $fileName,
//                    file_get_contents($file),
//                    [
//
////                    'visibility' => 'public',
//                        'ContentType' => 'image/jpeg'
//
//                    ]
//
//
//            );



        } else {
            $path = "";
        }

        return response()->json($path);

    }catch(Exception $e){

        return response()->json('failed to set options');


    }catch(ErrorException $err){
        return response()->json('options not set');

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

Route::get('/storage/app/public/{file}', function ($file) {

    $url = asset('storage/app/public/'.$file);

    return response()->download($url);

});

//route to provide a url to an image stored in azure storage container
Route::get('/download-photo/{foldername}/{filename}', function ($foldername, $filename) {

    //still works with the put
//    $url = 'https://' . config('filesystems.disks.azure.name'). '.blob.core.windows.net/' .
//        config('filesystems.disks.azure.container') . '/'.$foldername.'/' . $filename;

    $accountName = config('filesystems.disks.azure.name');
    $container = config('filesystems.disks.azure.container');
    $permissions = 'r';
    $start = '2018-02-05T09:00:00Z';
    $expiry = '2018-02-09T17:00:00Z';
    $version = '2017-04-17';
    $key = config('filesystems.disks.azure.key');
    $resourceType = 'b';


//    ($accountName, $container, $filename, $permissions, $start, $expiry, $version, $signature)
//    $signature = getSASForBlob($accountName, $container, $filename, $permissions, $start, $expiry, $version, $key);

    $signature = getSASForBlob($accountName, $container, '1513735785025.jpeg', $permissions,
        $start, $expiry, $version, $key);

//dd($signature);

    $url = getBlobUrl($accountName, $container, '1513735785025.jpeg', $permissions, $resourceType, $start, $expiry, $version, $signature);

    dd($url);

//        $blobUrl = getBlobUrl(config('filesystems.disks.azure.name'), config('filesystems.disks.azure.container'), $foldername, $filename,
//        'b','r',$end_date, $signature);


//    return response()->json($url);
});


/*Test Routes*/
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
//
//Route::get("/individualreport/test/{reportId}", 'ReportApiController@getIndividualReport');
//
//Route::get("/post/reports/individual/test/{dateFrom}/{dateTo}/{userId}", 'ReportApiController@postIndividualTest');
//
//Route::get("/reports/list/{compId}", 'ReportApiController@getReportList');
//
//Route::get("/commencedshiftdetails/test/{assignedid}/{mobileuserid}", 'JobsController@getCommencedShiftDetails');
//
//Route::get("/notdeletedcasenotestest/", 'ReportApiController@getShiftCheckCasesTest');
//
//Route::get("/putshifttest/{mobileuserid}", 'JobsController@putShift');




