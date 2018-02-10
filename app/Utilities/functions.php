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
        dd($seconds, $mins, $hours);

        $totalHours = floor($hours * 100) / 100;//hours to 2 decimal places

        return $totalHours;
    }
}


/*archived*/
//$url = 'https://' . config('filesystems.disks.azure.name'). '.blob.core.windows.net/' .
//    config('filesystems.disks.azure.container') . '/'.$foldername.'/' . $filename;
//if (!function_exists('changeContentType')) {
//
//    function changeContentType($filepath, $filename)
//    {
//
//    // Return MIME type ala mimetype extension
////        $finfo = finfo_open(FILEINFO_MIME_TYPE);
//
//    // Get the MIME type of the file
////        $file_mime = finfo_file($finfo, $filepath.'/'.$filename);
////        finfo_close($finfo);
//
//        $file_handle = fopen('/'.$filepath.'/'.$filename, 'r');
//
//    // Here is the magic. getDriver() allows us to over-ride the default request config
//        Storage::disk('azure')
//            ->getDriver()
//            ->put( $filename,
//                $file_handle,
//                [
//                    'visibility' => 'public',
//                    'ContentType' => 'image/jpeg'
//                ]
//            );
//    }
//}

//if (!function_exists('azureContentType')) {
//    function azureContentType()
//    {
//        try {
//                Storage::extend('azure', function ($app, $config) {
//
//                    $endpoint = sprintf(
//                        'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s',
//                        'odinlitestorage',
//                        'hPL5J+cWfa98ousjU/24eZaCxjpCIFxQlnAIQU9KvbHDWapwMeEUXJ9u5ePBXTebEj8NeW227SXQgk64woPJog=='
//                    );
//
//                    $blobRestProxy = ServicesBuilder::getInstance()->createBlobService($endpoint);
//
//                    $blob = $blobRestProxy->getBlob("images", "image1.jpeg");
//
//
//                    $name = $blob->getName();
//
//                    return $name;
//                    });
//            } catch (ServiceException $e) {
//                $code = $e->getCode();
//                $error_message = $e->getMessage();
//                $error = $code . ": " . $error_message . "<br />";
//                return $error;
//
//            }
//
//
//    }
//}





