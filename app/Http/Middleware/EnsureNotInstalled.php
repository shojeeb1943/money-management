<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureNotInstalled
{
    public function __construct(private InstallationState $state) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->state->installed()) {
            return redirect('/');
        }

        return $next($request);
    }
}
