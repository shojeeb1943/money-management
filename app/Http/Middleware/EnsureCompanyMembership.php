<?php

namespace App\Http\Middleware;

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyMembership
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $minimumRole = null): Response
    {
        [$user, $company] = [$request->user(), $this->company($request)];

        abort_if(! $user || ! $company || ! $user->belongsToCompany($company), 403);

        $this->ensureCompanyMemberHasRequiredRole($user, $company, $minimumRole);

        if ($request->route('current_company') && ! $user->isCurrentCompany($company)) {
            $user->switchCompany($company);
        }

        return $next($request);
    }

    /**
     * Ensure the given user has at least the given role, if applicable.
     */
    protected function ensureCompanyMemberHasRequiredRole(User $user, Company $company, ?string $minimumRole): void
    {
        if ($minimumRole === null) {
            return;
        }

        $role = $user->companyRole($company);

        $requiredRole = CompanyRole::tryFrom($minimumRole);

        abort_if(
            $requiredRole === null ||
            $role === null ||
            ! $role->isAtLeast($requiredRole),
            403,
        );
    }

    /**
     * Get the company associated with the request.
     */
    protected function company(Request $request): ?Company
    {
        $company = $request->route('current_company') ?? $request->route('company');

        if (is_string($company)) {
            $company = Company::where('slug', $company)->first();
        }

        return $company;
    }
}
