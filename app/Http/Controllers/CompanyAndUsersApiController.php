<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Company as Company;
use App\User as User;
use App\UserRole as Role;
use App\Subscription as Subscription;

class CompanyAndUsersApiController extends Controller
{

    //receives the userId of the user that will now be the company contact
    public function updateContact($userId)
    {

        //verify company
        $user = User::withTrashed()
            ->where('id', '=', $userId)
            ->first();

        $verified = verifyCompany($user);

        if (!$verified) {

            return response()->json($verified);//value = false
        }

//        dd($verified);

        //new user instance for the update, as cannot be a deleted user
        $contact = User::find($userId);

        //if contact exists and is not deleted
        if ($contact != null) {

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
    //or returns inTrial false or inTrial true and trial_ends_at date
    public function getSubscription($compId)
    {

        //find a user that belongs to the company to verify the compId and the current user belong to the same company
        $user = User::where('company_id', '=', $compId)->first();

        //verify company
        $verified = verifyCompany($user);

        if (!$verified) {

            return response()->json($verified);//value = false
        }

        //need to check all of the company's users records to see if a subscription exists.
        //only primary contacts can update subscriptions but the primary contact could change, so subscription
        //could be attached to old primary contact.
        //TODO: when edit the primary contact, copy in the subscription (except it is associated with a different customer)
        //so how will this work???

        $compUsers = User::where('company_id', '=', $compId)
            ->get();

        //array of userIds that belong to the company
        $userIds = $compUsers->pluck('id');

        //the primary contact starts the free trial
        //but another user may have started the subscription, depending on our policy here.
        $subscriptions = DB::table('subscriptions')
            ->whereIn('user_id', $userIds)
            ->orderBy('updated_at')
            ->get();

        //subscription has begun
        if (count($subscriptions) > 0) {

            //todo: check details such as end date of subscription before returning subscription details
            return response()->json([
                'subscriptions' => $subscriptions,

            ]);

        } else if (count($subscriptions) == 0) {
            //none of the company user's have started a subscription, check if in trial period
            $inTrial = false;

            foreach ($compUsers as $compUser) {
                if ($compUser->onTrial()) {
                    $inTrial = true;
                    $trialEnds = $compUser->trial_ends_at;
                }
            }

            if ($inTrial == true) {
                return response()->json([
                    'trial' => $inTrial,//true if inTrial period and subscription has not begun for any of the users, or false if not
                    'trial_ends_at' => $trialEnds//either null or a date
                ]);
            } else {
                return response()->json([
                    'trial' => $inTrial,//true if inTrial period and subscription has not begun for any of the users, or false if not
                ]);
            }
        }

    }

    //wip, atm deletes company, primary contact from user and user roles
    public function removeAccount($compId, $userId)
    {

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

        if ($comp->status == "incomplete") {
            $comp->delete();

            $this->deletePrimaryContact($userId);

        }

        return response()->json([
            'success' => true
        ]);
    }

    //called during login
    public function getSession()
    {

        $user = Auth::user();

        $session = DB::table('users')
            ->join('companies', 'users.company_id', 'companies.id')
            ->join('user_roles', 'users.id', 'user_roles.user_id')
            ->where('users.id', '=', $user->id)
            ->select('users.id as userId', 'users.first_name', 'users.last_name', 'users.trial_ends_at',
                'companies.id as compId', 'companies.name', 'companies.primary_contact', 'companies.status',
                'user_roles.role')
            ->get();

        return response()->json([
            'session' => $session
        ]);

    }

    //pm is userId
    public function deleteUser($id)
    {

        $user = User::find($id);

        $verified = verifyCompany($user);

        if (!$verified) {

            return response()->json($verified);//value = false
        }

        //verify the employee is not the primary contact of the company (note: users can be employees)
        $checkPrimaryContact = checkPrimaryContact($user);

        //if true ie user is the company primary contact
        if ($checkPrimaryContact) {
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

    public function deletePrimaryContact($userId)
    {

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

    //Usage: check if have subscription then create subscription or swap subscription
//    public function upgradeSubscription(Request $request){
//
//
//    }

//    public function swapSubscription($request){
//
//    }

    //ARCHIVED. NOW ABSORBED BY CREATESUBSCRIPTION()
//    public function createSubscriptionTrial($request){
//
//        $user = Auth::user();
//
//        //verify company
//        $verified = verifyCompany($user);
//
//        if(!$verified){
//
//            return response()->json($verified);//value = false
//        }
//
//        //verify user is the primary contact
//        $checkPrimaryContact = checkPrimaryContact($user);
//
//        //if true ie user is the company primary contact
//        if(!$checkPrimaryContact){
//            return response()->json([
//                'primaryContact' => false,
//                'success' => false
//            ]);
//        }
//
//        $stripeToken = $request->stripeToken;//either will hold a value or will be null
//        $plan = $request->plan;
//        $term = $request->term;
//        $trialEndsAt = $request->trialEndsAt;
//
//        $stripePlan = stripePlanName($plan, $term);
//
//        //eg format input = 5th June 2018 returns 2018-06-05 02:42:27
//        $dateTrialEndsAt = Carbon::createFromFormat('jS F Y', $trialEndsAt); // 1975-05-21 22:00:00
//        $now = Carbon::now();
//        $trialDays = $now->diffInDays($dateTrialEndsAt);
//
//        //The first argument passed to the newSubscription method should be the name of the subscription.
//        // If your application only offers a single subscription, you might call this main or  primary.
//        // The second argument is the specific Stripe / Braintree plan the user is subscribing to.
//        $user->newSubscription('main', $stripePlan)
//            ->trialDays($trialDays)
//            ->create($stripeToken);
//
//        if ($user->subscribed('main')) {
//
//            return response()->json([
//                'success' => true
//            ]);
//
//        }else{
//            return response()->json([
//                'success' => false
//            ]);
//        }
//    }


    //usage 1: initial subscription for a company instigated by primary contact
    //usage 2: when the primary contact for a company changes, the old subscription is cancelled
    //and when the billing cycle is near ended (1 week for monthly billing, 2 weeks for yearly)
    //the new primary contact is notified that they need to enter credit card details and
    public function createSubscription(Request $request)
    {
//
        $user = Auth::user();
//
//        //verify company
//        $verified = verifyCompany($user);
//
//        if (!$verified) {
//
//            return response()->json($verified);//value = false
//        }

        //verify user is the primary contact
        $checkPrimaryContact = checkPrimaryContact($user);

        //if true ie user is the company primary contact
        if (!$checkPrimaryContact) {
            return response()->json([
                'primaryContact' => false,
                'success' => false
            ]);
        }

        $stripeToken = $request->stripeToken;//either will hold a value or will be null
        $plan = $request->plan;
        $term = $request->term;
        $trialEndsAt = $request->trialEndsAt;

        $stripePlan = stripePlanName($plan, $term);

        if (isset($trialEndsAt)) {

            $trialDays = trialDays($trialEndsAt);
            //The first argument passed to the newSubscription method should be the name of the subscription.
            // If your application only offers a single subscription, you might call this main or  primary.
            // The second argument is the specific Stripe / Braintree plan the user is subscribing to.
            $user->newSubscription('main', $stripePlan)
                ->trialDays($trialDays)
                ->create($stripeToken);
        } else {
            //The first argument passed to the newSubscription method should be the name of the subscription.
            // If your application only offers a single subscription, you might call this main or  primary.
            // The second argument is the specific Stripe / Braintree plan the user is subscribing to.
            $user->newSubscription('main', $stripePlan)->create($stripeToken);

        }

        if ($user->subscribed('main')) {

            return response()->json([
                'success' => true
            ]);

        } else {
            return response()->json([
                'success' => false
            ]);
        }
    }

    //Usage: 1) when the primary contact is changed, the current subscription will be cancelled
    // with the ends_at date being the normal subscription end date
    // (end of month if billed monthly, end of year if billed yearly)
    //this scenario could be instigated by any role == Manager or perhaps by new primary contact????
    //Usage: 2) the primary contact opts to cancel the subscription altogether
    //NOTE: change subscription is not a cancel it is a swap
    //this scenario will be instigated by primary contact only

    //Cancel btn pressed on console, pass through subscription id in request
    //Primary Contact must make the request
    public function cancelMySubscription(Request $request)
    {

        $user = Auth::user();

        //check primary contact and authorised to cancel the subscription
        // (safeguard even though the console btn will not be accessible to non primary contacts)
        $contact = checkPrimaryContact($user);

        if (!$contact) {
            return response()->json([
                'success' => false,
                'result' => "This user is not the primary contact for the company and as such cannot cancel the subscription."
            ]);
        }

        //else... user is primary contact...

        $subscriptionId = $request->subId;

        //get subscription
        $subscription = Subscription::find($subscriptionId);

        //find the user associated with subscription
        $userSubscription = User::find($subscription->user_id);

        //as a safeguard, check the userSubscription belongs to the same company as the logged in user
        if ($user->company_id == $userSubscription->company_id) {

            $user->subscription($subscription->name)->cancel();

            if ($user->subscription($subscription->name)->onGracePeriod()) {

                //get the subscription model to access the updated ends_at field
                $cancelledSub = Subscription::find($subscriptionId);

                $endsAt = $cancelledSub->ends_at;

                return response()->json([
                    'success' => true,
                    'result' => "The subscription has been cancelled, with a grace period ending on: " . $endsAt//tested :)
                ]);
            } else {

                return response()->json([
                    'success' => true,
                    'result' => "The subscription has been cancelled."
                ]);

            }

        } else {
            return response()->json([
                'success' => false,
                'result' => "The user is not authorized to cancel this company's subscription."//tested :)
            ]);

        }
    }

}
