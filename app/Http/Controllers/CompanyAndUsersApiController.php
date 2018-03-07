<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Company as Company;
use App\User as User;
use Illuminate\Support\Facades\DB;

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

    public function getSubscription($compId){

        //find a user that belongs to the company to verify the compId and the current user belong to the same company
        $user = User::where('company_id', '=', $compId)->first();

        //verify company
        $verified = verifyCompany($user);

        if(!$verified){

            return response()->json($verified);//value = false
        }

//        $compUsers = DB::table('users')
//            ->join('companies', 'users.company_id', '=', 'companies.id')
//            ->where('users.company_id', '=', $compId)
//            ->select('users.id as id')
//            ->get();

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
//            dd($subscription);//empty array as expected
            response()->json(['subscriptions' => $compUsers]);

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
//            dd($subscription, $inTrial);//empty array, false as expected when a company that has not started subscription nor trial tested
            response()->json([
                'trial' => $inTrial,
                'trial_ends_at' => $trialEnds
            ]);

        }
    }

}
