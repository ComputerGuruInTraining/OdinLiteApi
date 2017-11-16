@extends('layouts.app_basic')

@section('content')
    <img src="{{ asset("/images/ODIN-Logo.png") }}" alt="Odin Logo" height="60px" width="200px"
         style="position: absolute; left:30px; top:30px;"/>
<div class="container" style="padding-top: 150px;">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default" style="border-color: #4d2970;">
                <div class="panel-heading"  style="color: white; background-color: #4d2970;">Create/Update Password</div>

                <div class="panel-body">
                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form class="form-horizontal" role="form" method="POST" action="{{ url('/password/reset') }}">
                        {{ csrf_field() }}

                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                            <label for="email" class="col-md-4 control-label">E-Mail Address</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control" name="email" value="{{ $email or old('email') }}" required autofocus 
                                style="border: 1px solid #4d2970;
                                    -webkit-text-fill-color: grey;
                                    -webkit-box-shadow: 0 0 0px 1000px white inset;
                                    transition: background-color 5000s ease-in-out 0s;">

                                @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                            <label for="password" class="col-md-4 control-label">Password</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control" name="password" required 
                                style="border: 1px solid #4d2970;
                                    -webkit-text-fill-color: grey;
                                    -webkit-box-shadow: 0 0 0px 1000px white inset;
                                    transition: background-color 5000s ease-in-out 0s;">

                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('password_confirmation') ? ' has-error' : '' }}">
                            <label for="password-confirm" class="col-md-4 control-label">Confirm Password</label>
                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required
                                    style="border: 1px solid #4d2970;
                                    -webkit-text-fill-color: grey;
                                    -webkit-box-shadow: 0 0 0px 1000px white inset;
                                    transition: background-color 5000s ease-in-out 0s;">

                                @if ($errors->has('password_confirmation'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password_confirmation') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <button type="submit" class="btn btn-primary" 
                                style="color: white;
                                background-color: #4d2970;
                                font-size: large;">
                                    Submit
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
