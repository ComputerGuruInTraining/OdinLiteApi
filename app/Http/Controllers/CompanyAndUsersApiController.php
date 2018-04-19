<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Company as Company;
use App\User as User;
use App\UserRole as Role;
use App\Subscription as Subscription;
use Config;

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

    //return subscriptions == 1 active subscription (will only be one based on design);
    //or return , graceSub if in trialPeriod but cancelled (with the latest trial_ends_at date,
    ///////// as could be 2 if edit primary contact, and cancel first subscription, create 2nd, then user cancels 2nd;
    //or return cancelSub if not in trial period but cancelled (with the latest trial_ends_at date, as with gracePeriod, could be 2.)
    //or returns inTrial false or inTrial true and trial_ends_at date if no subscription created yet ever.
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

        $compUsers = User::where('company_id', '=', $compId)
            ->get();

        //array of userIds that belong to the company
        $userIds = $compUsers->pluck('id');

        //the primary contact starts the free trial
        //but another user may have started the subscription, depending on our policy here.
        $subscriptions = DB::table('subscriptions')
            ->whereIn('user_id', $userIds)
            ->orderBy('ends_at', 'desc')
            ->get();


        //subscription has begun
        if (count($subscriptions) > 0) {

            //check if there is an active subscription (without an ends_at date)
            $active = false;
            $activeSub = null;
        //todo: console deal with in trial subscriptions
            foreach ($subscriptions as $sub) {

                //if any of the subscriptions have not been cancelled
                //the non cancelled subscription will be the active subscription, there should only be 1 of these.
                if ($sub->ends_at == null) {

                    $activeSub = $sub;
                    $active = true;

                }
            }

            if ($active == true) {

                return response()->json([
                    'subscriptions' => $activeSub,//worked for ends_at = null, only 1 subscription. check on console if has a trial period.

                ]);

            } else {
                //no active subscription, need to retrieve cancelled subscription

                $graceCheck = false;
                $graceSub = null;
                $graceSubscription = null;
                $graceCollect = collect();


                //find whether any are onGracePeriod still
                //according to design, could be 2 on grace period considering edit primary contact design
                //if edit primary contact, and cancel first subscription, create 2nd, user cancels 2nd,
                //then have 2 trial ends at dates the same, 2 cancelled subscriptions, but the most recent one needs to be returned to console.
                //the latest ends_at date would be the subscription we require.
                foreach ($compUsers as $compUser) {

                    //if the user has a subscription that is on Grace Period, it will return true and following will return all subscriptions
                    if ($compUser->subscription('main')->onGracePeriod()) {

                        $graceSub = Subscription::where('user_id', $compUser->id)
                            ->where('trial_ends_at', '!=', null)
                            ->orderBy('trial_ends_at', 'desc')
                            ->first();

                        $graceCollect->push($graceSub);

                        $graceCheck = true;

                    }
                }
                if ($graceCheck == true) {

                    $graceCollect->sortBy('trial_ends_at');

                    $graceSubscription = $graceCollect->first();

                    return response()->json([
                        'graceSub' => $graceSubscription,

                    ]);
                }
                else{
                    //user has cancelled and not on grace period

                    $cancelSubscription = null;
                    $cancelCollect = collect();
                    $cancelSub = null;

                    foreach ($compUsers as $compUser) {

                        if ($compUser->subscription('main')->cancelled()) {
                            $cancelSub = Subscription::where('user_id', $compUser->id)
                                ->where('ends_at', '!=', null)
                                ->orderBy('ends_at', 'desc')
                                ->first();

                            $cancelCollect->push($cancelSub);
                        }
                    }

                    $cancelCollect->sortBy('ends_at');//5th june then the april

                    $cancelSubscription = $cancelCollect->first();

                    return response()->json([
                        'cancelSub' => $cancelSubscription,

                    ]);
                }
            }

        } else if (count($subscriptions) == 0) {
            //none of the company user's have started a subscription, check if in trial period
            $inTrial = false;
            $trialEnds = null;

            foreach ($compUsers as $compUser) {
                //check if trial_ends_at date is after current date, if so true.
                if ($compUser->onTrial()) {
                    $inTrial = true;
                    $trialEndsAt = $compUser->trial_ends_at;

                }
            }

            if($inTrial == false){

                //could there be 2 trial_ends_at dates that differ?
                // If say a company cancels account when on trial, and then reinstates account
                //                with a trial (once we bring in remove account, and reinstate account, and if we provide a 2nd trial in this instance),
                //so,to be safe, we'll presume there could be 2 trial_ends_at dates.

                $compUsers->sortBy('trial_ends_at');

                $outOfDate = $compUsers->first();

                $trialEndsAt = $outOfDate->trial_ends_at;
            }

            return response()->json([
                'trial' => $inTrial,//true if inTrial period and subscription has not begun for any of the users, or false if not
                'trial_ends_at' => $trialEndsAt//could be a past date if trial == false, or a future date if trial = true
            ]);
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

    //change the primary contact in companies table
    //also update active tag contact (change contact details and name of current contact so as to keep tags and all else as is)
    public function changePrimaryContact(Request $request){

//        try{

            if ($request->has('primaryContact')) {

                $newPrimaryContactId = $request->primaryContact;

                $user = Auth::user();

                $company = Company::find($user->company_id);

                $oldPrimaryContact = $company->primary_contact;

                $company->primary_contact = $newPrimaryContactId;

                if ($company->save()) {

                    //todo: update active campaign contact to be new primary contact

    //                $newuser = User::find($newPrimaryContactId);
    //1. need to contact_view_email and use the id to then edit

                    return response()->json([
                        'success' => true,
                        'newPrimaryContact' => $newPrimaryContactId,
                        'oldPrimaryContact' => $oldPrimaryContact
                    ]);

                } else {

                    $currentPrimaryContact = $company->primaryContact;

                    return response()->json([
                        'success' => false,
                        'currentPrimaryContact' => $currentPrimaryContact
                    ]);
                }
            } else {

                return response()->json([
                    'success' => false
                ]);

            }
//        }catch(\Exception $exception){
//
//        $errMsg = $exception->getMessage();
//
//        dd($errMsg);
//        }
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

    public function swapSubscription(Request $request){

        $user = Auth::user();

        //verify user is the primary contact
        $checkPrimaryContact = checkPrimaryContact($user);

        //if true ie user is the company primary contact
        if (!$checkPrimaryContact) {

            return response()->json([
                'primaryContact' => false,
                'success' => false
            ]);
        }

        /*the subscribed method returns true if the user has an active subscription,
        even if the subscription is currently within its trial period*/
        if ($user->subscribed('main')) {

            $plan = $request->plan;
            $term = $request->term;

            $stripePlan = stripePlanName($plan, $term);

            $user->subscription('main')->swap($stripePlan);

            if ($user->subscribed('main')) {

                return response()->json([
                    'success' => true
                ]);

            } else {
                return response()->json([
                    'success' => false
                ]);
            }
        }else {
            /*
             * To determine if the user was once an active subscriber,
             * but has cancelled their subscription, you may use the cancelled method
             */
            if ($user->subscription('main')->cancelled()) {

                if ($user->subscription('main')->onGracePeriod()) {
                    return response()->json([
                        'success' => false,
                        'subscriptionStatus' => "cancelled but on grace period"
                    ]);

                }else {
                    return response()->json([
                        'success' => false,
                        'subscriptionStatus' => "cancelled"
                    ]);
                }
            }else {
                return response()->json([
                    'success' => false,
                    'subscriptionStatus' => "not subscribed"
                ]);
            }
        }
    }

    //usage 1: initial subscription for a company instigated by primary contact
    //usage 2: when the primary contact for a company changes, the old subscription is cancelled
    //and when the billing cycle is near ended (1 week for monthly billing, 2 weeks for yearly)
    //the new primary contact is notified that they need to enter credit card details and
    public function createSubscription(Request $request)
    {
        $user = Auth::user();

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
        $trialEndsAt = $request->trialEndsAt;//either user was in trial or primary contact edited so $trialEndsAt == $oldSubscriptionEndsAt

        $stripePlan = stripePlanName($plan, $term);

        //for use with active campaign tags
        $comp = Company::find($user->company_id);

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

        //for all new subscriptions, remove trial tag from the active campaign (whether tag exists or not, request performs smoothly,
        // however a notification will be sent to ourselves if the tag did not exist. todo: optimize so not sent if the error msg was tag did not exist.)
         $removeTag = Config::get('constants.TRIAL_TAG');

         $removeTagUpperCase = ucwords($removeTag);

         removeTag($user, $removeTag, $comp, 'Start of Paid Subscription',
             'Attempted to remove tag: ' . $removeTagUpperCase,
             'Succeeded in removing tag: ' . $removeTagUpperCase);

        //for all new subscriptions, add a tag to the active campaign
        //tag to add depends on the billing cycle...
        if ($term == 'monthly') {
            $addTag = Config::get('constants.PAID_MONTHLY_TAG');
        }else{
            $addTag = Config::get('constants.PAID_YEARLY_TAG');
        }

        $addTagUpperCase = ucwords($addTag);

        addTag($user, $addTag, $comp, 'Start of Paid Subscription',
            'Attempted to add tag: '. $addTagUpperCase,
            'Succeeded in adding tag: '.$addTagUpperCase
        );

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

                //laravel db name for subscription
                $userSubscription->subscription($subscription->name)->cancel();

                if ($userSubscription->subscription($subscription->name)->onGracePeriod()) {

                    //get the subscription model to access the updated ends_at field
                    $cancelledSub = Subscription::find($subscriptionId);

                    $endsAt = $cancelledSub->ends_at;

                    return response()->json([
                        'success' => true,
                        'result' => 'on grace period',
                        'endsAt' => $endsAt
                    ]);
                } else {

                    //rare that would cancel on the same day as bill about to be charged, but could happen.
                    return response()->json([
                        'success' => true,
                        'result' => 'cancelled'
                    ]);

                }

            } else {
                return response()->json([
                    'success' => false,
                    'result' => "unauthorized", //The user is not authorized to cancel this company's subscription.
                ]);

            }


    }

}
