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

//route to provide a url to an image stored in azure storage container
Route::get('/download-photo/{foldername}/{filename}', function ($foldername, $filename) {

    //still works with the put
    $url = 'https://' . config('filesystems.disks.azure.name'). '.blob.core.windows.net/' .
        config('filesystems.disks.azure.container') . '/'.$foldername.'/' . $filename;

        return response()->json($url);
});


//    $file = $filename . '.jpeg';

//    $exists = Storage::disk('azure')->exists('images/'.$foldername.'/'.$filename);
//TODO function for date from now
 /*   $end_date = 'st=2018-01-29T22%3A18%3A26Z';

    $signature = getSASForBlob(config('filesystems.disks.azure.name'), config('filesystems.disks.azure.container'), $foldername, $filename,
        'b','r', $end_date,config('filesystems.disks.azure.key'));


    //eg generated by azure

    $azureSasToken = "?sv=2017-04-17
    &ss=b
    &srt=sco
    &sp=r
    &se=2018-01-19T18:45:17Z
    &st=2018-01-12T10:45:17Z
    &spr=https
    &sig=8X6%2FcboOz8wNm52zARyXgwod5sOqq3wjycojmP6OP2s%3D";

    //(note: account level)
    //
    //*authorization errors until parameter error*/
 /*
    // //invalid expiry time: and error was "Signature fields not well formed."
    $azureUrl = "https://odinlitestorage.blob.core.windows.net/?sv=2017-04-17&ss=b&srt=sco&sp=r&se=2018-01-19T18:45:17Z
    &st=2018-01-12T10:45:17Z&spr=https&sig=8X6%2FcboOz8wNm52zARyXgwod5sOqq3wjycojmP6OP2s%3D";

    //then changed the date
//    AuthenticationErrorDetail>
//    Signature not valid in the specified time frame


   //generated by azure, invalid (Value for one of the query parameters specified in the request URI is invalid.)
    //authenitication passed ("InvalidQueryParameterValue is the new error")
   $azureUrlWithoutComp = "https://odinlitestorage.blob.core.windows.net/?sv=2017-04-17&ss=b&srt=sco&sp=r&se=2018-01-12T18:45:17Z&st=2018-01-11T10:45:17Z&spr=https&sig=rV1xgVWu70%2BQbFIuedgxUsTPu%2FakG%2FUs6%2F3YC%2FU4JP4%3D";



    //invalid
    $azureUrlWithComp =  "https://odinlitestorage.blob.core.windows.net/?&comp=properties&sv=2017-04-17&ss=b&srt=sco&sp=r&se=2018-01-12T18:45:17Z&st=2018-01-11T10:45:17Z&spr=https&sig=rV1xgVWu70%2BQbFIuedgxUsTPu%2FakG%2FUs6%2F3YC%2FU4JP4%3D";

//<StorageServiceProperties> were returned, possibly a success but not after properties now. however read is false.??
   $azureWithMore =  "https://odinlitestorage.blob.core.windows.net/?restype=service&comp=properties&sv=2017-04-17&ss=b&srt=sco&sp=r&se=2018-01-12T18:45:17Z&st=2018-01-11T10:45:17Z&spr=https&sig=rV1xgVWu70%2BQbFIuedgxUsTPu%2FakG%2FUs6%2F3YC%2FU4JP4%3D";


//container and object only, not service
    //InvalidQueryParameterValue = comp
    $azureGen = "https://odinlitestorage.blob.core.windows.net/?sv=2017-04-17&ss=b&srt=co&sp=r&se=2018-01-12T18:45:17Z&st=2018-01-11T10:45:17Z&spr=https&sig=%2Bp7PKarV8GBKaBm66M%2F1GrtlDHeQLLnArmTPBTKYuqU%3D";


*/
    //eg
    /*
    $BlobEndpoint="https://storagesample.blob.core.windows.net;
SharedAccessSignature=sv=2015-04-05&sr=b&si=tutorial-policy-635959936145100803&sig=9aCzs76n0E7y5BpEi2GvsSv433BZa22leDOZXX%2BXXIU%3D";

    //eg with encoding
    $BlobEndpoint="https://storagesample.blob.core.windows.net;
SharedAccessSignature=sv=2015-04-05&amp;sr=b&amp;si=tutorial-policy-635959936145100803&amp;sig=9aCzs76n0E7y5BpEi2GvsSv433BZa22leDOZXX%2BXXIU%3D";

    //blob sas service level
    $blobEg = "https://myaccount.blob.core.windows.net/sascontainer/sasblob.txt?sv=2015-04-05&st=2015-04-29T22%3A18%3A26Z
    &se=2015-04-30T02%3A23%3A26Z&sr=b&sp=rw&sip=168.1.5.60-168.1.5.70&spr=https&sig=Z%2FRHIX5Xcg0Mq2rqI3OlWTjEg2tYkboXr1P9ZUXDtkk%3D";
    */


    //blob attempts
/*sr is mandatory. Cannot be empty
    https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?sv=2015-04-05&se=2018-01-12T18:45:17Z&sp=r&sig=griHMWYEhsTAv1qqNZYt6nmFLcO9d/p4xLKS+xoSdMU=

Signature did not match. String to sign used was r 2018-01-12T18:45:17Z /blob/odinlitestorage/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg 2015-04-05

   sv=2015-04-05&se=2018-01-12T18:45:17Z&sr=b&sp=r

    https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?
sv=2015-04-05&se=2018-01-12T18:45:17Z&sr=b&sp=r
&sig=AckMwZobnjViRvGn/qbyLYdhUSKhuJ3Pa5SIs0yliMU=


Signature fields not well formed.
        $StringToSign = "https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?
        sv=2015-04-05&se=2018-01-12T18:45:17Z&sr=b&sp=r";
KXfBCjLRLEoaRXOZDTs+TTjsh+JCRqE0q2Mxx2V57Ts=

https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?sv=2015-04-05&se=2018-01-12T18:45:17Z&sr=b&sp=r&sig=KXfBCjLRLEoaRXOZDTs+TTjsh+JCRqE0q2Mxx2V57Ts=


Signature fields not well formed.
hUCmjDH+kQaaY8O62l4K7BwrUgoXUTsTIKqjpvIcS4E=
$StringToSign = "odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?
        sv=2015-04-05&se=2018-01-12T18:45:17Z&sr=b&sp=r";


https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?sv=NOTHISTONE&se=2018-01-12T18:45:17Z&sr=b&sp=r&sig=hUCmjDH+kQaaY8O62l4K7BwrUgoXUTsTIKqjpvIcS4E=


sv=2015-04-05

2012-02-12

sv=2015-07-08




//we have lift off
//when public downloads yep, but when private, The specified resource does not exist.
https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?sv=2017-04-17&se=2018-01-12T18:45:17Z&sr=b&sp=r&sig=R/ZYyeeQwuMgHenwmVVMF9Lr7cJvWchPXk7dsJtXPsg=

R/ZYyeeQwuMgHenwmVVMF9Lr7cJvWchPXk7dsJtXPsg=
sv=2017-04-17&se=2018-01-12T18:45:17Z&sr=b&sp=r



R/ZYyeeQwuMgHenwmVVMF9Lr7cJvWchPXk7dsJtXPsg=
2017-04-17
https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?sv=2017-04-17&se=2018-01-12T18:45:17Z&sr=b&sp=r&sig=R/ZYyeeQwuMgHenwmVVMF9Lr7cJvWchPXk7dsJtXPsg=


lease
images
6ea70c2d-5b2a-4934-bdd0-b35ea562c765


//using this:
StringToSign = signedpermissions + "\n" +
               signedstart + "\n" +
               signedexpiry + "\n" +
               canonicalizedresource + "\n" +
               signedidentifier + "\n" +
               signedIP + "\n" +
               signedProtocol + "\n" +
               signedversion + "\n" +
               rscc + "\n" +
               rscd + "\n" +
               rsce + "\n" +
               rscl + "\n" +
               rsct

Sm5cucbFMrVB9C1XgRyn8UX1EoeoAH+akYBzuB3m3as=
Signature fields not well formed. using string to sign as above w canonicalized resource
https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?sv=2017-04-17&se=2018-01-12T18:45:17Z&sr=b&sp=r&sig=Sm5cucbFMrVB9C1XgRyn8UX1EoeoAH+akYBzuB3m3as=

Signature did not match. (following stored access policy creatin()
https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?sv=2017-04-17&se=2018-01-12T18:45:17Z&sr=b&sp=r&sig=R/ZYyeeQwuMgHenwmVVMF9Lr7cJvWchPXk7dsJtXPsg=



<AuthenticationErrorDetail>Signature fields not well formed.
        $StringToSign = "sv=2017-04-17&sr=b&si=12345";

7LTjELIDeO7EUyff54Ztaktn+91H87Qe5A6tpRLpvVo=

https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?sv=2017-04-17&sr=b&si=12345&sig=7LTjELIDeO7EUyff54Ztaktn+91H87Qe5A6tpRLpvVo=


//container si
//Signature did not match. String to sign used was /blob/odinlitestorage/images 12345 2017-04-17
X7kdwXvCsG4F8uF5aygywz3/OvV37oufnYBtXCwgADo=
https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?sv=2017-04-17&sr=c&si=12345&sig=X7kdwXvCsG4F8uF5aygywz3/OvV37oufnYBtXCwgADo=

//container
sv=2017-04-17&sr=c&si=12348aur

ZQLtNSzRfhbDthjichur6/1lMptXptcovQyeKHm/aGA=


https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?sv=2017-04-17&sr=c&si=12348aur&sig=ZQLtNSzRfhbDthjichur6/1lMptXptcovQyeKHm/aGA=


The requested URI does not represent any resource on the server.

//string to sign using service sas eg
//psosisble error due to blob not container canonialiszed resource
V15zFK0eUc6R9JXJrc8V4a3eSz7GRcO/E+QU3tQ//vo=

 $signedpermissions = "r";
        $signedstart = "2018-01-12T01:00:00Z";
        $signedexpiry = "2018-01-12T23:00:00Z";
        $canonicalizedresource = "blob/odinlitestorage/images/";
        $signedidentifier = "12348aur";
        $signedIP = "";
        $signedProtocol = "";
        $signedversion = "2017-04-17";
        $rscc = "";
        $rscd = "";
        $rsce = "";
        $rscl = "";
        $rsct = "";


        $StringToSign = $signedpermissions . "\n" .
            $signedstart . "\n" .
            $signedexpiry . "\n" .
            $canonicalizedresource . "\n" .
            $signedidentifier . "\n" .
            $signedIP . "\n" .
            $signedProtocol . "\n" .
            $signedversion . "\n" .
            $rscc . "\n" .
            $rscd . "\n" .
            $rsce . "\n" .
            $rscl . "\n" .
            $rsct;


//        GET https://myaccount.blob.core.windows.net/pictures/profile.jpg?sv=2013-08-15&st=2013-08-16&se=2013-08-17&sr=c&sp=r&rscd=file;%20attachment&rsct=binary &sig=YWJjZGVmZw%3d%3d&sig=a39%2BYozJhGp6miujGymjRpN8tsrQfLo9Z3i8IRyIpnQ%3d HTTP/1.1
Signature fields not well formed.
https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?sv=2017-04-17&st=2018-01-12T01:00:00Z&se=2018-01-12T23:00:00Z&sr=c&si=12348aur&sig=V15zFK0eUc6R9JXJrc8V4a3eSz7GRcO/E+QU3tQ//vo=

Signature fields not well formed.
https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?sv=2017-04-17&st=2018-01-12T01:00:00Z&se=2018-01-12T23:00:00Z&sr=c&sig=V15zFK0eUc6R9JXJrc8V4a3eSz7GRcO/E+QU3tQ//vo=


//signature did not match signat=ure vused was blob/images/account erc
https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?sv=2017-04-17&st=2018-01-12T01:00:00Z&se=2018-01-12T23:00:00Z&sr=c&sp=r&sig=o0qi0t9v41lGih4lWBCYSG0cZhXZAotPc53g9DqPufU=



o0qi0t9v41lGih4lWBCYSG0cZhXZAotPc53g9DqPufU=

*/






//    sv=2015-04-05&se=2018-01-12T18:45:17Z&sp=r
//
//    se=st%3D2018-01-29T22%253A18%253A26Z&sr=b&sp=r&sig=0s0QnzcSqmrwLxnFoP1IASup1l47bl%2BU10aHn8WdfTY%3D&sv=2014-02-14


    //$comp=list&restype=container = AuthorizationResourceTypeMismatch
//     $compList =  "https://odinlitestorage.blob.core.windows.net/?comp=list&restype=container&sv=2017-04-17&ss=b&srt=sco&sp=r&se=2018-01-12T18:45:17Z&st=2018-01-11T10:45:17Z&spr=https&sig=rV1xgVWu70%2BQbFIuedgxUsTPu%2FakG%2FUs6%2F3YC%2FU4JP4%3D";
//
//    https://odinlitestorage.blob.core.windows.net/?comp=list&restype=container&sv=2017-04-17&ss=b&srt=co&sp=rl&se=2018-01-12T18:45:17Z&st=2018-01-11T10:45:17Z&spr=https&sig=48S42QSVEZlDm4Ur8r8EdTfbZoIJW4qm67P5eHOy2Is%3D
//    https://odinlitestorage.blob.core.windows.net/?comp=list&restype=container&sv=2017-04-17&ss=b&srt=c&sp=rl&se=2018-01-12T18:45:17Z&st=2018-01-11T10:45:17Z&spr=https&sig=RV2g6f6VqMZJKHFBAzL621pKIShY8ahkxCtf9fHMh4E%3D





/*

   //generated by signature fn in functions.php
$sign = "R70nJFWXRwrjU6zU0d/2Agthk7JhCVxfHXRrp36n548=";

//    $blobUrl = getBlobUrl(config('filesystems.disks.azure.name'), config('filesystems.disks.azure.container'), $foldername, $filename,
//        'b','r',$end_date, $signature);
//dd($blobUrl);



//    $connectionString = "DefaultEndpointsProtocol=https;AccountName=<config('filesystems.disks.azure.name')>;
//        AccountKey=<config('filesystems.disks.azure.key')>";
//
//// Create blob REST proxy.
//    $blobRestProxy = ServicesBuilder::getInstance()->createBlobService($connectionString);
//    return response()->json($url);
dd($signature);
//    VPUMOgdI9nqtGc99vPvihaiyflX6xHZle3rlFTZ8PJs=

      $windowsURL =   "https://odinlitestorage.blob.core.windows.net/images/'.$foldername.'/'.$filename.'?sv=2013-08-15&st=2018-01-01&se=2018-01-31&sr=c
        &sp=r&rscd=file;%20attachment &sig=YWJjZGVmZw%3d%3d&sig=R70nJFWXRwrjU6zU0d/2Agthk7JhCVxfHXRrp36n548= HTTP/1.1";

    $authUrl = "https://odinlitestorage.blob.core.windows.net/images?restype=container&comp=list&sv=
    2015-04-05&si=readpolicy&sig=rPKnr0oAkDZLMPdoEVGkSxsXCzrbfu7m0a0SlbFduA4=";

    https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?
    //se=st%3D2018-01-29T22%253A18%253A26Z&sr=b&sp=r&sig=0s0QnzcSqmrwLxnFoP1IASup1l47bl%2BU10aHn8WdfTY%3D&sv=2014-02-14

*/

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


//            $stream = fopen($_FILES[$uploadname]['tmp_name'], 'r+');
//            $filesystem->writeStream('uploads/'.$_FILES[$uploadname]['name'], $stream);
//            fclose($stream);

            //store the file in the /images directory inside storage/app



//            $path = $request->file('file')->storeAs('/', 'image1.jpeg');

//            $success = azureContentType();

//            if ($success == 'true') {
//                $success = $request->input('fileName');
//            }

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







/*Test Routes*/
Route::get("/individualreport/test/{reportId}", 'ReportApiController@getIndividualReport');

Route::get("/post/reports/individual/test/{dateFrom}/{dateTo}/{userId}", 'ReportApiController@postIndividualTest');

///reports/individual/test/2018-01-01 00:00:00/2018-01-31 00:00:00/1374
//Route::get("/post/shiftcheckouts/test/{shiftCheckId}/{posId}", 'JobsController@storeCheckOutTest');

//Route::get("/casenotes/testlist/{compId}", 'CaseNoteApiController@getCaseNotes');

//Route::get("/reports/individual/test/{dateFrom}/{dateTo}/{userId}", 'ReportApiController@queryReportUser');


//Route::get("/reports/individual/testNotes/{userId}", 'ReportApiController@queryCaseNotesUserTest');

//storeCheckInTest($posId, $locId, $shiftId, $checks)
//Route::get("/post/shiftchecks/test/{posId}/{locId}/{shiftId}/{checks}", 'JobsController@storeCheckInTest');

////user = 1164
//Route::get('/testMail/{id}', function ($id) {
//
//$newUser = App\User::find($id);
//
//$compName = App\Company::where('id', '=', 404)->pluck('name')->first();
//
//    $newUser->notify(new NewMobileUser($compName));
//
//});

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



