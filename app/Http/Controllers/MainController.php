<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;


class MainController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }

    public function activate($compId)
    {
        $numUpdated = DB::table('companies')
            ->where('id', '=', $compId)
            ->update(['status' => 'active']);

        if ($numUpdated == 1) {
            $title = 'Confirmation of Success';
            $msg = 'The registration process is complete and you may now log into Odin Lite Management Console.';
        } else {
            $title = 'Notification of Failure';
            $msg = 'The activation did not complete successfully. Possibly the activation has already been completed. 
	   	Please try logging into the account.';
        }

        return view('activated')->with(array('msg' => $msg, 'title' => $title));

    }
    public function download($filename)
    {
        $file = $filename.'.jpeg';

        $pathToFile = 'images\/'.$file;

        //check if file exists
//        Storage::exists($file);

//        $pathToFile
        return response()->download($pathToFile);

    }
}
