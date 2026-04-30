<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Bootstrap 4 Admin &amp; Dashboard Template">
    <meta name="author" content="Bootlab">

    <title>{{$site_title.' | '.$site_name}}</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    <link class="js-stylesheet" href="{{ asset('css/light.css') }}" rel="stylesheet">
    <link class="js-stylesheet" href="{{ asset('css/custom.css') }}" rel="stylesheet">
    <link rel="icon" href="{{ asset('public/favicon.ico') }}">
    @yield('css')
</head>
<!--
  HOW TO USE: 
  data-theme: default (default), dark, light
  data-layout: fluid (default), boxed
  data-sidebar-position: left (default), right
  data-sidebar-behavior: sticky (default), fixed, compact
-->

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-behavior="sticky">
    <div class="wrapper">
        @include('backend.layout.sidebar')
        <?php header("Access-Control-Allow-Origin: *"); ?>
        <div class="main">
            <nav class="navbar navbar-expand navbar-light navbar-bg">
                <a class="sidebar-toggle">
                    <i class="hamburger align-self-center"></i>
                </a>
                <div class="navbar-collapse collapse">
                    <ul class="navbar-nav navbar-align">
                        <li class="nav-item dropdown">
                            <a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#" data-bs-toggle="dropdown">
                                <i class="align-middle" data-feather="settings"></i>
                            </a>

                            <a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#" data-bs-toggle="dropdown">
                                @php($name = Auth::guard('admin')->user()->firstname.' '.Auth::guard('admin')->user()->lastname)
                                @php($avtar = Auth::guard('admin')->user()->avtar)
                                <img src="{{ asset('front/images/logo.png') }}" class="avatar img-fluid rounded-circle mr-1" alt="{{ucwords($name)}}" /> <span class="text-dark">{{ucwords($name)}}</span>
<!--                                DAMANFX-->
                            </a>
                            @php($roles = Session::get('roles'))
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="{{route('admin_change-password')}}"><i class="align-middle mr-1" data-feather="stop-circle"></i> {{ __('Change Password')}}</a>
                                
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{route('admin_logout')}}">{{ __('Sign out')}}</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>