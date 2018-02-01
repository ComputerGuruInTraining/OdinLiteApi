@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">Odin Database Management Service</div>

                <div class="panel-body">
                    You are logged in!
                </div>


                <a href="/reports/list/404">Get Report List for Comp 404</a>
                <br/>

                <a href="/individualreport/test/1254">Get Individual Report 1254 user_id = 1374</a>
                <br/>
                {{--get took a count of 7 secs, 25 sec once added for loop, also at different hourse with nbn other house had adsl2+--}}
                <a href="/individualreport/test/1234">Get Individual Report 1234 user_id = 1384</a>
                <br/>

                <a href="/post/reports/individual/test/2018-01-01 00:00:00/2018-01-31 00:00:00/1384">Post Individual Report 1384</a>
                {{--1384 returns 33 records when the get is conducted. Post took a count of 40 seconds--}}
                <br/>

                <a href="/post/reports/individual/test/2018-01-01 00:00:00/2018-01-31 00:00:00/1374">Post Individual Report 1374</a>
                {{--1374 returns 21 records when the get is conducted. Post took 95 secs!!!--}}
                <br/>

                <a href="/commencedshiftdetails/test/814/1374">Get Commenced Shift Details</a>
                {{--1374 returns 21 records when the get is conducted. Post took 95 secs!!!--}}
                <br/>


                <a href="/putshifttest/1054">Edit Shift 1054</a>
                <br/>



                {{--<a href="/post/shiftcheckouts/test/4144/40244">Test store check out</a>--}}

                {{--<a href="download-photo/1515638198829.jpeg/2e4dca1c24076df03586a19c48e2e6c7.jpeg">Download</a>--}}

                {{--<a href="/reports/individual/test/2018-01-01 00:00:00/2018-01-31 00:00:00/1374">Test Shift Ids when select, Pluck ok</a>--}}

                {{--<a href="/reports/individual/testNotes/1374">Test query report case notes</a>--}}
                {{--<a href=" /post/shiftchecks/test/41064/684/3814/2">Test store check in</a>--}}


                {{--<a href="/casenotes/testlist/404">Test Case Notes w FIles</a>--}}

            </div>
        </div>
    </div>
</div>
@endsection
