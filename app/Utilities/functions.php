<?php
/**
 * Created by PhpStorm.
 * User: bernie
 * Date: 7/11/17
 * Time: 1:30 PM
 */


//Parameter: a single location id
//Usage: includes deleted records
//Returns: an object of location address, latitude and longitude
if(! function_exists('locationAddressDetails')) {

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
if(! function_exists('userFirstLastName')) {

    function userFirstLastName($userIds)
    {
        $users = User::withTrashed()->whereIn('id', $userIds)->select('id', 'first_name', 'last_name');
        return $users;
    }
}

//generic get id from table using id from another table
if(! function_exists('getTable2Id')) {

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