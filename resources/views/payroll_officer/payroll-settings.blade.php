@extends('layouts.app')
@section('title', 'Settings - Medisource HRMS')
@section('content')
@include('payroll_officer.payroll_sidebar')
@include('partials.settings-change-password', ['passwordRoute' => 'payroll_officer.settings.password'])
@endsection
