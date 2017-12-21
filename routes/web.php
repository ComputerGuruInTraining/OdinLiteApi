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
//use MicrosoftAzure\Storage\Common\ServiceException;


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

//        if ($userRole->save()) {
            $newuser->notify(new RegisterCompany($compId));

            return response()->json([
                'success' => $newuser,
                'checkEmail' => $checkEmail,

            ]);
//        }
    }
});

Route::get('/activate/{compId}', 'MainController@activate');

//mobile: upload an image during a new case note
//CSRF_Token excluded route
Route::post('/upload', function (Request $request) {

    if ($request->hasFile('file')) {


        //store the file in the /images directory inside storage/app
//        $path = $request->file('file')->storeAs('casenotes', $request->input('fileName'));
        $path = $request->file('file')->storeAs('/', 'image1.jpeg');

        $success = azureContentType();

        //filename in the format timestamp.jpeg

//        $filepath = 'casenotes';

        //override the content type and store on disk
//        changeContentType($filepath, $filename);
//
//        $path = $filename;

//        $file_handle = fopen($filepath, 'r');

//        Storage::disk('azure')
//            ->getDriver()
//            ->put( $filepath,
//                $file_handle,
//                [
//                    'visibility' => 'public',
//                    'ContentType' => 'image/jpeg'
//                ]
//            );


    } else {
        $success = "nofile";
//        $path = "";
    }

    return response()->json($success);
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
Route::get('/download-photo/{foldername}/{filename}', function ($foldername, $filename) {

//    $file = $filename . '.jpeg';

//    $exists = Storage::disk('azure')->exists('images/'.$foldername.'/'.$filename);

    $url = 'https://' . config('filesystems.disks.azure.name'). '.blob.core.windows.net/' .
        config('filesystems.disks.azure.container') . '/'.$foldername.'/' . $filename;


//    $connectionString = "DefaultEndpointsProtocol=https;AccountName=<config('filesystems.disks.azure.name')>;
//        AccountKey=<config('filesystems.disks.azure.key')>";
//
//// Create blob REST proxy.
//    $blobRestProxy = ServicesBuilder::getInstance()->createBlobService($connectionString);

    return response()->json($url);

//    $pathToFile = 'images/' . $file;
////    $storagePathToFile = base_path('storage/app/images/'. $file);//works on localhost
//    $storagePathToFile =  storage_path('app/images/'. $file);
//
//    //check if file exists
//    $fileExists = Storage::exists($pathToFile);
//
//    if ($fileExists) {
//         return response()->download($storagePathToFile);
//
//    } else {
//        return response()->json($fileExists);//false
//
//    }

});

Route::get('/storage/app/public/{file}', function ($file) {

    $url = asset('storage/app/public/'.$file);

    return response()->download($url);

});

//Work in progress
Route::get('/testing/nofitication/fail', function () {

//    $newUser = App\User::find(974);//dds a bit of info
    $newUser = App\User::find(974);//if user doesn't exist, fatal error

    $newUser->notify(new NewMobileUser('test notification'));

//    dd($response);
    return response()->json([
        'success' => true
    ]);

});

//TODO: 
//check to see if access token, assigned via oauth2, exists and is not expired
//and also used for initial login to mobile
/* Route::post('/user/verify', function (Request $request) {

     $userId = DB::table('users')
         ->where('email', $request->input('email'))
         ->pluck('id');

 /*$token = DB::table('oauth_access_tokens')
       ->where('user_id', '=', $user->id)
       ->get();*/

// if($user !=null) {
/*
       return response()->json([
            'success' => $userId
        ]);
}); */



