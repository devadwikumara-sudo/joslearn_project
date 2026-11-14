<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ImportGuruController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.import-data', ['tab' => 'guru']);
    }
}
