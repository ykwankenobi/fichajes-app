<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        if (Auth::user()->is_admin) {
            return redirect('/admin');
        }

        return redirect()->route('work-time.index');
    }
}
