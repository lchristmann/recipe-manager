<?php

namespace App\Http\Controllers;

use App\Models\Cookbook;
use Illuminate\Http\RedirectResponse;

class HomeRedirectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $userId = auth()->id();

        // first community cookbook (by position)
        $cookbook = Cookbook::query()
            ->where('community', true)
            ->orderBy('position')
            ->first();

        // first personal cookbook of user
        if (!$cookbook) {
            $cookbook = Cookbook::query()
                ->where('user_id', $userId)
                ->orderBy('position')
                ->first();
        }

        // first public cookbook of anyone else
        if (!$cookbook) {
            $cookbook = Cookbook::query()
                ->where('private', false)
                ->where('community', false)
                ->where('user_id', '!=', $userId)
                ->whereHas('user') // ensures user exists
                ->with('user:id,name') // eager load for sorting consistency
                ->get()
                ->sortBy([
                    ['user.name', 'asc'],
                    ['position', 'asc'],
                ])
                ->first();
        }

        // fallback
        if (!$cookbook) {
            return redirect()->route('cookbooks.index');
        }

        return redirect()->route('cookbooks.show', $cookbook);
    }
}
