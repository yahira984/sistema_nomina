<?php

namespace App\Http\Controllers;

use App\Models\UserPreference;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme' => ['nullable', 'in:light,dark,system'],
            'density' => ['nullable', 'in:comfortable,compact'],
            'sidebar_collapsed' => ['nullable', 'boolean'],
            'saved_filters' => ['nullable', 'array'],
            'filter_key' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9_.-]+$/'],
            'filter_value' => ['nullable', 'array'],
        ]);

        $preference = UserPreference::firstOrNew(['user_id' => $request->user()->id]);
        if (!empty($validated['filter_key'])) {
            $filters = $preference->saved_filters ?? [];
            data_set($filters, $validated['filter_key'], $validated['filter_value'] ?? []);
            $preference->saved_filters = $filters;
        }
        unset($validated['filter_key'], $validated['filter_value']);
        $preference->fill(array_filter($validated, fn ($value) => $value !== null));
        $preference->save();

        if ($request->expectsJson()) {
            return response()->json(['saved' => true]);
        }

        return back()->with('success', 'Preferencias guardadas.');
    }
}
