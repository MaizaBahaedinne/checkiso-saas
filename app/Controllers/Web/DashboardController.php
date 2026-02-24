<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index(): string
    {
        return view('dashboard/index', ['title' => 'Dashboard — CheckISO']);
    }
}
