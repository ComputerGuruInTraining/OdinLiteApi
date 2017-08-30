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
use App\Company;
use App\User;
use App\UserRole;
use App\CaseNote;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
//use Illuminate\Support\Facades\Storage;


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
	    return view('auth/passwords/confirm_reset');
    });

    //register new user and company for console
    //CSRF_Token excluded route
    Route::post('/company', function(Request $request){ 
    	
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
    	$user->email = $request->input('email_user');
    	$user->password = $request->input('pw');
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
	
  	if($userRole->save()) {
		$newuser->notify(new RegisterCompany($compId));
		
           return response()->json([
                'success' => $newuser
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
            
        }  
                    
    });
    
    Route::get('/activate/{compId}', 'MainController@activate');

    //mobile: upload an image during a new case note
    //CSRF_Token excluded route
    Route::post('/upload', function(Request $request){ 

	    if($request->hasFile('file')){
	    
	    	//store the file in the /images directory inside storage/app
	    	$path = $request->file('file')->storeAs('images', $request->input('fileName'));	
	    }
	    else{
	       $path = "";
	    }
	    
	    return response()->json($path);
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



