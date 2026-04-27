@extends('layouts.app')
@section('title', 'Settings - Medisource HRMS')
@section('content')
@include('partials.settings-change-password', ['passwordRoute' => 'hr.settings.password'])
@endsection
