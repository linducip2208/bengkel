<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TechnicianSkill;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TechnicianSkillController extends Controller
{
    public function index()
    {
        $technicians = User::role('mekanik')
            ->where('is_active', true)
            ->with('skills')
            ->orderBy('name')
            ->get();

        $skills = TechnicianSkill::SKILLS;
        $levels = TechnicianSkill::LEVELS;

        return view('technician-skills.index', compact('technicians', 'skills', 'levels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'skill' => ['required', 'string', 'max:100'],
            'level' => ['required', Rule::in(TechnicianSkill::LEVELS)],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $exists = TechnicianSkill::where('user_id', $validated['user_id'])
            ->where('skill', $validated['skill'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Skill ini sudah terdaftar untuk teknisi tersebut.');
        }

        TechnicianSkill::create($validated);

        return back()->with('success', 'Skill berhasil ditambahkan.');
    }

    public function update(Request $request, TechnicianSkill $skill)
    {
        $validated = $request->validate([
            'level' => ['required', Rule::in(TechnicianSkill::LEVELS)],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $skill->update($validated);

        return back()->with('success', 'Skill berhasil diperbarui.');
    }

    public function destroy(TechnicianSkill $skill)
    {
        $skill->delete();

        return back()->with('success', 'Skill berhasil dihapus.');
    }
}
