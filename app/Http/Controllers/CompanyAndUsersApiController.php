<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Company as Company;
use App\User as User;
use App\UserRole as Role;

class CompanyAndUsersApiController extends Controller
{

    //receives the userId of the user that will now be the company contact
    public function updateContact($userId){

        //verify company
        $user = User::withTrashed()
            ->where('id', '=', $userId)
            ->first();

        $verified = verifyCompany($user);

        if(!$verified){

            return response()->json($verified);//value = false
        }

//        dd($verified);

        //new user instance for the update, as cannot be a deleted user
        $contact = User::find($userId);

        //if contact exists and is not deleted
        if($contact != null){

            $company = Company::find($contact->company_id);

            $company->primary_contact = $contact->id;

            $company->save();

            return response()->json([
                'success' => true
            ]);
        }

        return response()->json([
            'success' => false,
            'reason' => 'user has been deleted or does not exist'
        ]);
    }

    //return subscription if it has begun,
    //or returns inTrial false or true and trial_ends_at null or date
    public function getSubscription($compId){

        //find a user that belongs to the company to verify the compId and the current user belong to the same company
        $user = User::where('company_id', '=', $compId)->first();

        //verify company
        $verified = verifyCompany($user);

        if(!$verified){

            return response()->json($verified);//value = false
        }

        $compUsers = User::where('company_id', '=', $compId)
                        ->get();

        //array of userIds that belong to the company
        $userIds = $compUsers->pluck('id');

        //the primary contact starts the free trial
        //but another user may have started the subscription, depending on our policy here.
        $subscription = DB::table('subscriptions')
            ->whereIn('user_id', $userIds)
            ->orderBy('updated_at')
            ->get();

        //subscription has begun
        if(count($subscription) > 0){

            //todo: check details such as end date of subscription before returning subscription details
            return response()->json(['subscriptions' => $compUsers]);

        }else if(count($subscription) == 0){
            //none of the company user's have started a subscription, check if in trial period
            $inTrial = false;
            $trialEnds = null;

            foreach($compUsers as $compUser){
                if ($compUser->onGenericTrial()) {
                    $inTrial = true;
                    $trialEnds = $compUser->trial_ends_at;
                }
            }

            return response()->json([
                'trial' => $inTrial,//true if inTrial period and subscription has not begun for any of the users, or false if not
                'trial_ends_at' => $trialEnds//either null or a date
            ]);
        }

    }

    //wip, atm deletes company, primary contact from user and user roles
    public function removeAccount($compId, $userId){

        //verify user
//        $user = User::find($userId);

        //verify company
//        $verified = verifyCompany($user);
//
//        if(!$verified){
//
//            return response()->json($verified);//value = false
//        }

        //todo: also perhaps verify user is the account owner aka primary contact

        //todo: soft delete company users from users table, even if they are a primary contact
        //for loop
//        $result = $this->deleteUser($userId);

//        if(isset($result->primaryContact)){
//
//            dd($result);
//        }


        $comp = Company::find($compId);

        if($comp->status == "incomplete"){
            $comp->delete();

            $this->deletePrimaryContact($userId);

        }

        return response()->json([
            'success' => true
        ]);
    }

    //pm is userId
    public function deleteUser($id){

        $user = User::find($id);

        $verified = verifyCompany($user);

        if(!$verified){

            return response()->json($verified);//value = false
        }

        //verify the employee is not the primary contact of the company (note: users can be employees)
        $checkPrimaryContact = checkPrimaryContact($user);

        //if true ie user is the company primary contact
        if($checkPrimaryContact){
            return response()->json(['primaryContact' => "This user is the primary contact for the company and as such cannot be deleted at this stage."]);
        }

        //change email to include the words "OdinDeleted" before soft deleting the user.
        markEmailAsDeleted($user);

        User::where('id', $id)->delete();

        Role::where('user_id', $id)->delete();

        return response()->json([
            'success' => true
        ]);
    }

    public function deletePrimaryContact($userId){

        $user = User::find($userId);
//
//        $verified = verifyCompany($user);
//
//        if(!$verified){
//
//            return response()->json($verified);//value = false
//        }

        //change email to include the words "OdinDeleted" before soft deleting the user.
        markEmailAsDeleted($user);

        User::where('id', $userId)->delete();

        Role::where('user_id', $userId)->delete();

    }

}
