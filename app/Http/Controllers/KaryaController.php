<?php

namespace App\Http\Controllers;

use App\Models\Karya;
use App\Models\User;
use Illuminate\Http\Request;

class KaryaController extends Controller
{
    public function index()
    {
        $karya = Karya::all();
        return response()->json($karya); 
    }

    public function show($id)
    {
        $user = User::find($id);
        $karya = Karya::where('user_id', $user->id)->first();

        if (!$karya) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return response()->json($karya);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'link_karya' => 'required|file|mimes:pdf,doc,docx',
        ]);

        // Store the uploaded file
        if ($request->hasFile('link_karya')) {
            $path = $request->file('link_karya')->store('karya', 'public');
            $validated['link_karya'] = $path;
        }
        // Create a new Karya record

        $karya = Karya::create([
            'user_id' => $validated['user_id'],
            'link_karya' => $validated['link_karya'],
        ]);

        User::where('id', $karya->user_id)->update(['status' => 'karya']);
        return response()->json($karya, 201);
    }

    public function update(Request $request, $id)
    {
        // Logic to update an existing Karya record by ID
    }

    public function destroy($id)
    {
        // Logic to delete a Karya record by ID
    }
}
