<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AuditAdminService;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(AuditAdminService $service): View
    {
        return view('admin.audit.index', ['entries' => $service->list()]);
    }
}
