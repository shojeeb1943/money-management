<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Actions\Transactions\CreateCrossCompanyTransfer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SaveCrossCompanyTransferRequest;
use App\Models\Company;
use App\Models\Wallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;

final class CrossCompanyTransferController extends Controller
{
    public function store(SaveCrossCompanyTransferRequest $request, CreateCrossCompanyTransfer $createTransfer): RedirectResponse
    {
        $createTransfer->handle(
            Company::query()->whereKey($request->validated('from_company_id'))->firstOrFail(),
            Wallet::query()->whereKey($request->validated('from_wallet_id'))->firstOrFail(),
            Company::query()->whereKey($request->validated('to_company_id'))->firstOrFail(),
            Wallet::query()->whereKey($request->validated('to_wallet_id'))->firstOrFail(),
            $request->validated('amount'),
            Date::parse($request->validated('date')),
            $request->validated('description'),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transfer recorded.')]);

        return back();
    }
}
