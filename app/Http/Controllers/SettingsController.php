<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings');
    }

    public function general()
    {
        return view('settings.general');
    }

    public function permissions()
    {
        return view('settings.permissions');
    }

    public function notifications()
    {
        return view('settings.notifications');
    }

    public function security()
    {
        return view('settings.security');
    }

    public function email()
    {
        return view('settings.email');
    }
}