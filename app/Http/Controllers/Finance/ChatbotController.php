<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Actions\Ai\ParseTransactionText;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ParseChatbotTextRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Throwable;

final class ChatbotController extends Controller
{
    public function parse(ParseChatbotTextRequest $request, Company $current_company, ParseTransactionText $parseTransactionText): JsonResponse
    {
        try {
            $result = $parseTransactionText->handle($request->user(), $current_company, $request->validated('text'));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'The AI provider is unavailable right now. Try again in a moment.'], 422);
        }

        return response()->json($result);
    }
}
