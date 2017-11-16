@extends('layouts.app_basic')

@section('content')
<img src="{{ asset("/images/ODIN-Logo.png") }}" alt="Odin Logo" height="60px" width="200px"
         style="position: absolute; left:30px; top:30px;"/>
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2" style="padding-top: 50px;">
            <div class="panel panel-default" style="border-color: #663974; margin-top: 100px;">
                <div class="panel-heading" style="color: white; background-color: #663974">{{$title}}</div>

	                <div class="panel-body">
	                        <div>
	                            <p>{{$msg}}</p>
	                        </div>
                        <a href="https://odinlitemgmt.azurewebsites.net/" class="btn" style="
                                        color: white;
                                background-color:#663974;
                                font-size: large; margin: 20px;">Login</a>

                    </div>
	        </div>
            </div>
        </div>
    </div>
</div>
@endsection
