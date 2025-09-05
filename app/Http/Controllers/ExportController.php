<?php

namespace App\Http\Controllers;

use App\Exports\UsersExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ExportController extends Controller
{
    public function export(Request $request)
    {
        return Excel::download(new UsersExport, 'SWC-Data.xlsx');
    }
}
