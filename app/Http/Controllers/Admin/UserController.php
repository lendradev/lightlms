<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->when($request->string('q')->toString() !== '', fn ($query) => $query
                ->where('name', 'like', '%'.$request->string('q')->toString().'%')
                ->orWhere('email', 'like', '%'.$request->string('q')->toString().'%'))
            ->when($request->string('role')->toString() !== '', fn ($query) => $query
                ->where('role', $request->string('role')->toString()))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $this->withPlainLabels($users),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'role' => $request->string('role')->toString(),
            ],
            'roles' => array_map(fn (UserRole $role) => ['value' => $role->value, 'label' => $role->label()], UserRole::cases()),
        ]);
    }

    /**
     * Laravel's paginator labels carry HTML entities such as `&laquo;`. They
     * travel as plain text through JSON, so decode them once on the server.
     *
     * @param  LengthAwarePaginator<int, User>  $paginator
     * @return array<string, mixed>
     */
    private function withPlainLabels(LengthAwarePaginator $paginator): array
    {
        $data = $paginator->toArray();

        $data['links'] = array_map(static function (array $link): array {
            $link['label'] = html_entity_decode((string) $link['label']);

            return $link;
        }, $data['links']);

        return $data;
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
            'role' => ['required', 'in:student,instructor,admin'],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', 'in:student,instructor,admin'],
        ]);

        if ($user->id === $request->user()->id && $validated['role'] !== $user->role->value) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $user->update([
            'name' => $validated['name'],
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'User updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted.');
    }
}
