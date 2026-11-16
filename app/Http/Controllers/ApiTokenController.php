<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The personal API tokens of the signed-in person.
 *
 * A token is shown once, when it is created. After that only its name and
 * last use remain visible.
 */
class ApiTokenController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request): Response
    {
        $tokens = $request->user()->tokens()->latest()->get();

        return Inertia::render('Settings/ApiTokens', [
            'tokens' => $tokens->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'created_at' => $token->created_at?->toIso8601String(),
            ])->values(),
            'issuedToken' => session('issued_token'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $user = $request->user();

        $token = $user->createToken($validated['name']);

        $this->audit->record(
            'api_token.create',
            $user,
            User::class,
            $user->id,
            null,
            [],
            ['name' => $validated['name']],
        );

        return redirect()
            ->route('settings.api-tokens.index')
            ->with('issued_token', $token->plainTextToken);
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $user = $request->user();

        $personalToken = $user->tokens()->findOrFail($token);

        $this->audit->record(
            'api_token.revoke',
            $personalToken,
            'Laravel\\Sanctum\\PersonalAccessToken',
            $personalToken->id,
            null,
            ['name' => $personalToken->name],
            [],
        );

        $personalToken->delete();

        return back()->with('success', 'Token revoked.');
    }
}
