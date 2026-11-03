<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();

        // Safety net – middleware handles the redirect normally,
        // but guard here too in case route is accessed directly.
        if ($user->wajib_ganti_password) {
            redirect()->route('ganti-password')->send();
        }

        return view('dashboard.index', compact('user'));
    }
}
