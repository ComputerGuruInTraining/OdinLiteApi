<?php
/**
 * Created by PhpStorm.
 * User: bernie
 * Date: 7/11/17
 * Time: 1:30 PM
 */
// !IMPORTANT! Database queries must interact directly with the table, not via the model

//Parameter: a single location id
//Usage: includes deleted records
//Returns: an object of location address, latitude and longitude
if (!function_exists('locationAddressDetails')) {

    function locationAddressDetails($locId)
    {
        $location = DB::table('locations')
            ->where('id', '=', $locId)
            ->select('address', 'latitude', 'longitude')
            ->first();

        return $location;
    }
}

//Parameter: an array of user_ids
//Usage: includes deleted records
//Returns: an array of user_first_names and user_last_names
if (!function_exists('userFirstLastName')) {

    function userFirstLastName($userIds)
    {
        $users = DB::table('users')
            ->whereIn('id', $userIds)
            ->select('id', 'first_name', 'last_name')
            ->get();

        return $users;
    }
}

//generic get id from table using id from another table
if (!function_exists('getTable2Id')) {

    function getTable2Id($table2, $table1id, $column)
    {
        $records = DB::table($table2)
            ->where($column, '=', $table1id)
            ->where('deleted_at', '=', null)
            ->first();

        $table2Id = $records->id;

        return $table2Id;

    }
}

//for a current_user_location_id, select latitude and longitude and return as a collection
if (!function_exists('getGeoData')) {

    function getGeoData($userLocId)
    {
        //returns an object
        if(($userLocId != 0)&&($userLocId != null)){
            //check ins
            //returns single object
            $currentLoc = DB::table('current_user_locations')
                ->where('id', '=', $userLocId)
                ->select('latitude', 'longitude', 'id')
                ->first();

            if($currentLoc != null){
                $objectLat = $currentLoc->latitude;
                $objectLong = $currentLoc->longitude;
                //for testing purposes
                $objectGeoId = $currentLoc->id;
            }else{
                $objectLat = '';
                $objectLong = '';
                //for testing purposes
                $objectGeoId = '';
            }
        }else{
            $objectLat = '';
            $objectLong = '';
            //for testing purposes
            $objectGeoId = '';
        }

        $collection = collect([
            'lat' => $objectLat,
            'long' => $objectLong,
            'geoId' => $objectGeoId]);

        return $collection;
    }
}

if (!function_exists('getSASForBlob')) {

    function getSASForBlob($accountName, $container, $filename, $permissions, $start, $expiry, $version, $contentType, $key)
    {

            $signedpermissions = $permissions;
            $signedstart = $start;
            $signedexpiry = $expiry;
            $canonicalizedresource = '/blob/'.$accountName.'/'.$container.'/'.$filename;
            $signedidentifier = "";
            $signedIP = "";
            $signedProtocol = "";
            $signedversion = $version;
            $rscc = "";
            $rscd = "";
            $rsce = "";
            $rscl = "";
            $rsct = $contentType;


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

        return base64_encode(
            hash_hmac('sha256', urldecode(utf8_encode($StringToSign)), base64_decode($key), true)
        );
    }
}

if (!function_exists('getBlobUrl')) {

    function getBlobUrl($accountName, $container, $filename, $permissions, $resourceType, $start, $expiry, $version, $contentType, $signature)
    {
        /* Create the signed query part */
        $_parts = array();
        $_parts[] = (!empty($start)) ? 'st=' . urlencode($start) : '';
        $_parts[] = (!empty($expiry)) ? 'se=' . urlencode($expiry) : '';
        $_parts[] = 'sr=' . $resourceType;
        $_parts[] = (!empty($permissions)) ? 'sp=' . $permissions : '';
        $_parts[] = 'sv=' . $version;
        $_parts[] = 'rsct=' . $contentType;
        $_parts[] = 'sig=' . urlencode($signature);

        /* Create the signed blob URL */
        $url = 'https://'
            . $accountName . '.blob.core.windows.net/'
            . $container . '/'
            . $filename . '?'
            . implode('&', $_parts);

        return $url;
    }
}

//calculate the total hours (double) based on many shift durations (int)
if (!function_exists('totalHoursWorked')) {

    function totalHoursWorked($shifts)
    {
        //calculate the total hours
        $totalMins = $shifts->sum('duration');//duration is in minutes
        $hours = $totalMins / 60;
        $totalHours = floor($hours * 100) / 100;//hours to 2 decimal places

        return $totalHours;
    }
}

//calculate the number of guards
if (!function_exists('numGuards')) {

    function numGuards($shifts)
    {
        $numGuards = $shifts->groupBy('mobile_user_id')->count();

        return $numGuards;
    }
}

//a singular check in time in seconds
if (!function_exists('checkDuration')) {

    function checkDuration($checkInTime, $checkOutTime)
    {
        $carbonStart = Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $checkInTime);
        $carbonEnd = Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $checkOutTime);
        //calculate duration based on start date and time and end date and time
        $lengthS = $carbonStart->diffInSeconds($carbonEnd);//calculate in seconds
        return $lengthS;
    }
}

//calculate the total hours (double) spent monitoring a premis from the total check_durations which are in seconds
if (!function_exists('totalHoursMonitored')) {

    function  totalHoursMonitored($checks)
    {
        //calculate the total hours
        $seconds = $checks->sum('check_duration');//duration is in seconds
        $mins = $seconds/60;
        $hours = $mins / 60;

        $totalHours = floor($hours * 100) / 100;//hours to 2 decimal places

        return $totalHours;
    }
}

if (!function_exists('currentYear')) {

    function currentYear()
    {
        $year = date("Y");
        return $year;
    }
}

//Usage: when model either has a company_id
// or need to go via another table to get company_id (if can use the $model->id in the where clause)
//Purpose: ensure the current user belongs to the same company of the model/object
// that the route returns to the user
//returns true, false or null
if (!function_exists('verifyCompany')) {

    function verifyCompany($model, $table1 = null, $table2 = null, $table1col = null, $table2col = null)
    {
        //if empty model ie {}, $model instance == null
        if($model == null) {
            return "empty";
        }

        $user = Auth::user();

        //need to check the user's company
        if ($user->company_id == $model->company_id) {
            return true;
        }

        //model doesn't have a company_id property and must go via another table
        if($model->company_id == null){

            $record = DB::table($table1)
                ->join($table2, $table1col, '=', $table2col)
                ->where($table1col, '=', $model->id)
                ->first();

            if ($user->company_id == $record->company_id) {

                return true;
            }
        }

        return false;
    }
}

//Purpose: to check the user is not the primary contact for the company
if (!function_exists('checkPrimaryContact')) {
    function checkPrimaryContact($user)
    {
        if($user == null) {
            return false;
        }

        $comp = App\Company::find($user->company_id);

        if ($user->id == $comp->primary_contact) {
            return true;//ie they are the primary contact and do not proceed
        }

        //ie not the primary contact
        return false;
    }
}

if (!function_exists('resizeToThumb')) {

    function resizeToThumb($file)
    {
        $img = Image::make($file);

        // resize image
        $img->resize(250, 250, function ($constraint) {
            $constraint->aspectRatio();
        });

        return $img;
    }
}

//Purpose: change email to include the words "OdinDeleted" before soft deleting the user.
//this allows the user to be readded by the company should they wish
// and ensures the user cannot login once deleted
if (!function_exists('markEmailAsDeleted')) {

    function markEmailAsDeleted($user)
    {

        $email = $user->email;

        $user->email = ($email.'.OdinDeleted');

        $user->save();
    }
}

//active campaign: adds a contact or updates an existing contact
//scope for more updates as required
//$result->message = "Contact added" or "contact updated" if successfully added
if (!function_exists('addUpdateContactActiveCampaign')) {

    function addUpdateContactActiveCampaign($newuser, $tag1, $comp, $feature, $attempting, $succeeded)
    {
        $url = Config::get('constants.ACTIVE_URL');

        $request = 'api_action=contact_sync&api_output=json&api_key='.Config::get('constants.ACTIVE_API_KEY');

        //url_encode the body, especially in case a user input of first_name contains spaces
        $body = urlEncodeBody($newuser->email, $newuser->first_name, $newuser->last_name, $tag1);

        $client = new GuzzleHttp\Client;

        $response = $client->post($url.$request,
            array(
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
                'body' => $body
            )
        );

        $result = json_decode((string)$response->getBody());

        //works, just need to implement sending of email
        if(($result->result_message == "Contact added")||($result->result_message == "Contact updated")) {

            notifyActiveCampaign($result->result_message, 'Success', $newuser, $comp, $feature, null, null, $succeeded);

        }else {
            notifyActiveCampaign($result->result_message, 'Failed', $newuser, $comp, $feature,
                'This event did not complete successfully.', $attempting);
        }
    }
}

//$result->message = "Contact tags deleted" if successfully deleted,
//$result->message = "Contact tags deleted"  if tag never existed
// and therefore not actually deleted but request operates successfully
//fails: $result->message = "Contact does not exist" if that is the case
if (!function_exists('removeTag')) {

    function removeTag($user, $removeTag, $comp, $feature, $attempting, $succeeded)
    {

        $url = Config::get('constants.ACTIVE_URL');

        $request = Config::get('constants.REMOVE_TAG_REQUEST').Config::get('constants.ACTIVE_API_KEY');

        //url_encode the body, especially in case a user input of first_name contains spaces
        $body = urlEncodeBody('mailspace70@testfail.com', null, null, $removeTag);

        $client = new GuzzleHttp\Client;

        $response = $client->post($url.$request,
            array(
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
                'body' => $body
            )
        );

        $result = json_decode((string)$response->getBody());

        if($result->result_message == "Contact tags deleted"){
            notifyActiveCampaign($result->result_message, 'Success', $user, $comp, $feature, null, null, $succeeded);

        } else{
                notifyActiveCampaign($result->result_message, 'Failed', $user, $comp, $feature,
                    'This event did not complete successfully.', $attempting);

        }
    }
}

//$result->message = "Contact tags added" if successfully added
//IMPORTANT! will create a tag if doesn't exist, or add an existing tag.
//format: all lowercase and exact same as existing tag (including spaces)
// or a new tag will be created.
//fails: $result->message = "Contact does not exist" if that is the case
if (!function_exists('addTag')) {

    function addTag($user, $addTag, $comp, $feature, $attempting, $succeeded)
    {

        $url = Config::get('constants.ACTIVE_URL');

        $request = Config::get('constants.ADD_TAG_REQUEST').Config::get('constants.ACTIVE_API_KEY');

        //url_encode the body, especially in case a user input of first_name contains spaces
        $body = urlEncodeBody($user->email, null, null, $addTag);

        $client = new GuzzleHttp\Client;

        $response = $client->post($url.$request,
            array(
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
                'body' => $body
            )
        );

        $result = json_decode((string)$response->getBody());

        if($result->result_message == "Contact tags added"){
            notifyActiveCampaign($result->result_message, 'Success', $user, $comp, $feature, null, null, $succeeded);

        } else{
            notifyActiveCampaign($result->result_message, 'Failed', $user, $comp, $feature,
                'This event did not complete successfully.', $attempting);

        }
    }
}

if (!function_exists('urlEncodeBody')) {

    function urlEncodeBody($email, $firstName = null, $lastName = null, $tag1 = null)
    {
        $parts = array();

        $parts[] = 'email=' . $email;

        if($firstName != null) {
            $parts[] = 'first_name=' . urlencode($firstName);
        }

        if($lastName != null) {

            $parts[] = 'last_name=' . urlencode($lastName);
        }

        if($tag1 != null) {

            $parts[] = 'tags=' . urlencode($tag1);
        }

        $body = implode('&', $parts);

        return $body;
    }
}

if (!function_exists('notifySuccessActiveCampaign')) {

    function notifyActiveCampaign($msg, $result, $contact, $comp, $feature, $failedmsg = null, $attempting = null, $succeeded = null)
    {

        $odinTeam = new App\Recipients\DynamicRecipient(Config::get('constants.COMPANY_EMAIL2'));
        $odinTeam->notify(new App\Notifications\ActiveCampaign($msg, $result, $contact, $comp, $feature, $failedmsg, $attempting, $succeeded));

        $odinEmail = new App\Recipients\DynamicRecipient(Config::get('constants.COMPANY_EMAIL'));
        $odinEmail->notify(new App\Notifications\ActiveCampaign($msg, $result, $contact, $comp, $feature, $failedmsg, $attempting));

    }
}






