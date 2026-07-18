<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Exports\CompanyDataExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ExportController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/export', [
            'hasCompany' => $request->user()->currentCompany !== null,
        ]);
    }

    public function download(Request $request): BinaryFileResponse
    {
        $company = $request->user()->currentCompany;

        abort_if($company === null, 404);

        return Excel::download(new CompanyDataExport($company), "finance-export-{$company->slug}.xlsx");
    }
}
