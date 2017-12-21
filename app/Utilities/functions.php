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
//        Storage::extend('azure', function ($app, $config) {


        $connectionString = "DefaultEndpointsProtocol=https;AccountName=<odinlitestorage>;AccountKey=<hPL5J+cWfa98ousjU/24eZaCxjpCIFxQlnAIQU9KvbHDWapwMeEUXJ9u5ePBXTebEj8NeW227SXQgk64woPJog==>";

//        $endpoint = sprintf(
//                'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s',
//                'odinlitestorage',
//                'hPL5J+cWfa98ousjU/24eZaCxjpCIFxQlnAIQU9KvbHDWapwMeEUXJ9u5ePBXTebEj8NeW227SXQgk64woPJog=='
//            );

            $blobRestProxy = ServicesBuilder::getInstance()->createBlobService($connectionString);

            //access file
            $url = 'https://' . config('filesystems.disks.azure.name'). '.blob.core.windows.net/' .
                config('filesystems.disks.azure.container') . '/image1.jpeg';
            $content = fopen($url, "r");

            //blob details
            $blob_name = "image2.jpeg";
            $options = new CreateBlobOptions();
            $options->setBlobContentType("image/jpeg");

            try {
                //Upload blob
                $blobRestProxy->createBlockBlob('images',
                    $blob_name,
                    $content,
                    $options);
                $success = 'true';
                return $blobRestProxy;

            } catch (ServiceException $e) {
                $code = $e->getCode();
                $error_message = $e->getMessage();
                $error = $code . ": " . $error_message . "<br />";
                return $error;

            }
//        });

    }
}