<?php

use App\Mcp\Servers\FinanceServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::web('/mcp', FinanceServer::class)
    ->middleware(['throttle:60,1', 'auth:api'])
    ->name('mcp.finance');
