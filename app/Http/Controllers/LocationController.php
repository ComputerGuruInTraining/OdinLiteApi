<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Location as Location;
use App\LocationCompany as LocationCo;

class LocationController extends Controller
{

    public function storeLocation(Request $request) {

        try{
            $location = new Location;

            $location->name = $request->input('name');
            $location->address = $request->input('address');
            $location->latitude = $request->input('latitude');
            $location->longitude = $request->input('longitude');
            $location->notes = $request->input('notes');

            $location->save();
            //retrieve id of last insert
            $id = $location->id;

            //save location as current users company's location
            $locationCo = new LocationCo;
            $locationCo->location_id = $id;
            $locationCo->company_id = $request->input('compId');
            //$locationCo->save();

            if ($locationCo->save()) {
                return response()->json([
                    'success' => true
                ]);
            } else {
                return response()->json([
                    'success' => false
                ]);
            }

        }catch(\Exception $exception){

            $errMsg = $exception->getMessage();

            return response()->json([
                'success' => false,
                'exception' => $errMsg
            ]);
        }

    }

//    functions.php contains getGeoData
    //get some location details: only latitude, longitude
    public function getLocationData($locId)
    {
        $location = DB::table('locations')
            ->where('id', '=', $locId)
            ->select('latitude', 'longitude')
            ->first();

        return $location;

    }



    //gather data and call the withinRangeApi function to determine if withinRange
    public function implementDistance($posId, $locId)
    {
        //gets the geoLongitude, geoLatitude for the current_user_location_id
        $currLocData = getGeoData($posId);

        $geoLat = $currLocData->get('lat');
        $geoLong = $currLocData->get('long');

        $locData = $this->getLocationData($locId);

        $locLat = $locData->latitude;
        $locLong = $locData->longitude;

        $distance = $this->distanceApi($geoLat, $geoLong, $locLat, $locLong);

        return $distance;
    }
//    public function implementWithinRange($posId, $locId)
//    {
//
//        //gets the geoLongitude, geoLatitude for the current_user_location_id
//        $currLocData = getGeoData($posId);
//
//        $geoLat = $currLocData->get('lat');
//        $geoLong = $currLocData->get('long');
//
//        $locData = $this->getLocationData($locId);
//
//        $locLat = $locData->latitude;
//        $locLong = $locData->longitude;
//
//        $withinRange = $this->withinRangeApi($geoLat, $geoLong, $locLat, $locLong);
//
//        return $withinRange;
//    }

    //return $result;//yes, ok, no
//    public function geoRangeApi($distance){
//
//        //200m
//        $goodRange = 0.2;
//        $okRange = 0.5;
//
//        if($distance <= $goodRange){
//
//            $result = 'yes';
//
//        }else if($distance <= $okRange){
//
//            $result = 'ok';
//
//        }else{
//
//            $result = 'no';
//        }
//
//        return $result;
//    }

    /*Code sourced from: https://stackoverflow.com/questions/27928/calculate-distance-between-two-latitude-longitude-points-haversine-formula
    Margin For Error: (under +/-1% error margin).*/
    public function distanceApi($lat1, $lon1, $lat2, $lon2)
    {
        $km = 0;

        if (($lat1 != "") && ($lat2 != "")) {

            $pi80 = M_PI / 180;
            $lat1 *= $pi80;
            $lon1 *= $pi80;
            $lat2 *= $pi80;
            $lon2 *= $pi80;

            $r = 6372.797; // mean radius of Earth in km
            $dlat = $lat2 - $lat1;
            $dlon = $lon2 - $lon1;
            $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $km = $r * $c;

        }

        return $km;
    }
//    public function distanceApi($lat1, $lon1, $lat2, $lon2)
//    {
//
//        $pi80 = M_PI / 180;
//        $lat1 *= $pi80;
//        $lon1 *= $pi80;
//        $lat2 *= $pi80;
//        $lon2 *= $pi80;
//
//        $r = 6372.797; // mean radius of Earth in km
//        $dlat = $lat2 - $lat1;
//        $dlon = $lon2 - $lon1;
//        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
//        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
//        $km = $r * $c;
//
//        return $km;
//
//    }

    //determine if a geoLocation is within Range to another location
    //returns $result;//yes, ok, no
//    public function withinRangeApi($geoLat, $geoLong, $locLat, $locLong)
//    {
//        //if there is geoLocation data for the location check
//        if (($geoLat != "") && ($geoLong != "")) {
//
//            $distance = $this->distanceApi($geoLat, $geoLong,
//                $locLat, $locLong);
//
//            //returns $result;//yes, ok, no
//            $withinRange = $this->geoRangeApi($distance);
//
//        } else {
//
//            $withinRange = "-";
//        }
//
//        return $withinRange;
//    }


//    public function getCurrentUserLocData($posId)
//    {
//        $currUserLoc = DB::table('current_user_locations')
//            ->select('latitude as geoLat', 'longitude as geoLong')
//            ->where('id', '=', $posId)
//            ->get();
//
//        return $currUserLoc;
//    }


}
