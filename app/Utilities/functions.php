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

if (!function_exists('azureContentType')) {
    function azureContentType()
    {
        try {
                Storage::extend('azure', function ($app, $config) {


        //        $connectionString = "DefaultEndpointsProtocol=https;AccountName=<odinlitestorage>;AccountKey=<hPL5J.cWfa98ousjU/24eZaCxjpCIFxQlnAIQU9KvbHDWapwMeEUXJ9u5ePBXTebEj8NeW227SXQgk64woPJog==>";

                    $endpoint = sprintf(
                        'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s',
                        'odinlitestorage',
                        'hPL5J+cWfa98ousjU/24eZaCxjpCIFxQlnAIQU9KvbHDWapwMeEUXJ9u5ePBXTebEj8NeW227SXQgk64woPJog=='
                    );

                    $blobRestProxy = ServicesBuilder::getInstance()->createBlobService($endpoint);

                    //access file
        //            $url = 'https://' . config('filesystems.disks.azure.name'). '.blob.core.windows.net/' .
        //                config('filesystems.disks.azure.container') . '/image1.jpeg';
        //            $content = fopen($url, "r");
        //
        //            //blob details
        //            $blob_name = "image2.jpeg";
        //            $options = new CreateBlobOptions();
        //            $options->setBlobContentType("image/jpeg");


                        $blob = $blobRestProxy->getBlob("images", "image1.jpeg");


                        $name = $blob->getName();
        //                $blob->setContentType('image/jpeg');
                        $success = 'true';
        //                return $success;

                        return $name;

                        //Upload blob
        //                $blobRestProxy->createBlockBlob('images',
        //                    $blob_name,
        //                    $content,
        //                    $options);
        //
                    });
            } catch (ServiceException $e) {
                $code = $e->getCode();
                $error_message = $e->getMessage();
                $error = $code . ": " . $error_message . "<br />";
                return $error;

            }


    }
}

if (!function_exists('getSASForBlob')) {

    function getSASForBlob($accountName, $container, $foldername, $blob, $resourceType, $permissions, $expiry, $key)
    {
/*
        /* Create the signature */
//        $_arraysign = array();
//        $_arraysign[] = $permissions;
//        $_arraysign[] = '';
//        $_arraysign[] = $expiry;
//        $_arraysign[] = '/' . $accountName . '/' . $container . '/' . $foldername . '/' .$blob;
//        $_arraysign[] = '';
//        $_arraysign[] = "api-version=2012-02-12"; //the API version is now required//changed//fixme: error here
//        $_arraysign[] = '';
//        $_arraysign[] = '';
//        $_arraysign[] = '';
//        $_arraysign[] = '';
//        $_arraysign[] = '';




            //required
//            signed resource??
//sr=b; //blob
        $signedpermissions = "sp=r";
        $signedexpiry = "se=2018-01-31";
        $signedversion= "sv=2015-04-05";


        //optional
        $signedIP = "";
        $signedProtocol = "";
//        $signedIP = "sip=13.85.82.0";
//        $signedProtocol = "spr=https";

        $signedstart = "";
        $canonicalizedresource = "";
        $signedidentifier = "sr=b";
        $rscc = "";
        $rscd = "";
        $rsce = "";
        $rscl = "";
        $rsct = "";


        $signedstart='2013-08-16';
$signedexpiry='2013-08-17';
$signedresource='c';
$signedpermissions='r';
$signedidentifier='YWJjZGVmZw==';
$signedversion='2013-08-15';
//$responsecontent-disposition='file'; 'attachment';
//$responsecontent-type='binary' ;


//StringToSign = 'r . \n
//               2013-08-16 . \n
//               2013-08-17 . \n
//               /myaccount/pictures . \n
//               YWJjZGVmZw== . \n
//               2013-08-15 . \n
//               . \n
//                . \n
//    . \n
//    . \n ';
//               binary


                           /*'r'."\n".
                           '2018-01-01' . "\n" .
                           '2018-01-31' . "\n" .
                           'odinlitestorage.blob.core.windows.net/'.$foldername."\n"
                            ."\n".
                           '2013-08-15'. "\n"
                           ."\n".
                           'file; attachment' ."\n"
                           ."\n"
                       ."\n".
                       'image/jpeg';
                           */
//
//        sv=2015-04-05
//        &st=2015-04-29T22%3A18%3A26Z
//    &se=2015-04-30T02%3A23%3A26Z
//    &sr=b
//    &sp=rw
//    &sip=168.1.5.60-168.1.5.70
//    &spr=https


//        r + \n
//               2013-08-16 + \n
//               2013-08-17 + \n
//               /myaccount/pictures + \n
//               YWJjZGVmZw== + \n
//               2013-08-15 + \n
//               + \n
//               file; attachment + \n
//    + \n
//    + \n
//               binary

//        $StringToSign = $signedpermissions."\n".
//            $signedstart . "\n" .
//            $signedexpiry . "\n" .
//            $canonicalizedresource . "\n" .
//            $signedidentifier . "\n" .
//            $signedIP . "\n" .
//            $signedProtocol . "\n" .
//            $signedversion . "\n" .
//            $rscc . "\n" .
//            $rscd . "\n" .
//            $rsce . "\n" .
//            $rscl . "\n" .
//            $rsct;

//        $_str2sign = implode("\n", $_arraysign);*/

//        $StringToSign = "sv=2015-04-05&se=2018-01-12T18:45:17Z&sr=b&sp=r";
//
//        $StringToSign = "https://odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?
//        sv=2015-04-05&se=2018-01-12T18:45:17Z&sr=b&sp=r";


        //worked before made pirvate, then resource not found, then mismatch or signature not fwell formed
//        $StringToSign = "sv=2017-04-17&se=2018-01-12T18:45:17Z&sr=b&sp=r";

//with expiry and si
//        $StringToSign = "sv=2017-04-17&se=2018-01-12T18:45:17Z&sr=b&sp=r&si=12345";

//        Tr4d+FvjMIaSl+qNdT/URIesmpxqNnQI7ArqjfUUaGE=

//        $StringToSign = "sv=2017-04-17&sr=b&si=12345";
//        $StringToSign = "sv=2017-04-17&sr=c&si=12345";

//        $StringToSign = "sv=2017-04-17&sr=c&si=12348aur";

//        StringToSign = r + \n
//               2013-08-16 + \n
//               2013-08-17 + \n
//               /myaccount/pictures + \n
//               YWJjZGVmZw== + \n
//               2013-08-15 + \n
//               + \n
//               file; attachment + \n
//    + \n
//    + \n
//               binary
//
//        GET https://myaccount.blob.core.windows.net/pictures/profile.jpg?sv=2013-08-15&st=2013-08-16&se=2013-08-17&sr=c&sp=r&rscd=file;%20attachment&rsct=binary &sig=YWJjZGVmZw%3d%3d&sig=a39%2BYozJhGp6miujGymjRpN8tsrQfLo9Z3i8IRyIpnQ%3d HTTP/1.1


        $signedpermissions = "r";
        $signedstart = "2018-01-12T01:00:00Z";
        $signedexpiry = "2018-01-12T23:00:00Z";
        $canonicalizedresource = "/odinlitestorage/images/";
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

//        odinlitestorage.blob.core.windows.net/images/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg?





        return base64_encode(
            hash_hmac('sha256', urldecode(utf8_encode($StringToSign)), base64_decode($key), true)
        );
    }
}

if (!function_exists('getBlobUrl')) {

    function getBlobUrl($accountName, $container, $foldername, $blob, $resourceType, $permissions, $expiry, $_signature)
    {
        /* Create the signed query part */
        $_parts = array();
        $_parts[] = (!empty($expiry)) ? 'se=' . urlencode($expiry) : '';
        $_parts[] = 'sr=' . $resourceType;
        $_parts[] = (!empty($permissions)) ? 'sp=' . $permissions : '';
        $_parts[] = 'sig=' . urlencode($_signature);
        $_parts[] = 'sv=2014-02-14';

        /* Create the signed blob URL */
        $_url = 'https://'
            . $accountName . '.blob.core.windows.net/'
            . $container . '/'
            . $foldername . '/'
            . $blob . '?'
            . implode('&', $_parts);

        return $_url;
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

//a singular check in time in minutes
if (!function_exists('checkDuration')) {

    function checkDuration($checkInTime, $checkOutTime)
    {
        $carbonStart = Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $checkInTime);
        $carbonEnd = Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $checkOutTime);
        //calculate duration based on start date and time and end date and time
        $lengthM = $carbonStart->diffInMinutes($carbonEnd);//calculate in minutes
        return $lengthM;
    }
}





