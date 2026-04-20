@extends('layouts.app')
@section('title', 'Settings - Medisource HRMS')
@section('content')
@include('finance_officer.finance_sidebar')
@include('partials.settings-change-password', ['passwordRoute' => 'finance_officer.settings.password'])
@endsection
