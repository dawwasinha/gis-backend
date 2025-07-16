<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Invoice::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'jenis_lomba' => 'required|string', // Tambahkan validasi untuk jenis_lomba
        ]);

        // Tentukan total pembayaran berdasarkan jenis_lomba
        if ($request->jenis_lomba === 'science-competition') {
            $validated['total_pembayaran'] = 65000;
        } elseif ($request->jenis_lomba === 'science-writing') {
            $validated['total_pembayaran'] = 70000;
        } else {
            return response()->json(['error' => 'Jenis lomba tidak valid'], 400);
        }

        // Generate kode_bayar dengan format INV + random string
        $validated['kode_bayar'] = 'INV' . strtoupper(uniqid());

        $invoice = Invoice::create($validated);

        return response()->json($invoice, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user_id = User::where('id', $id)->value('id');
        $invoice = Invoice::where('user_id', $user_id)->get();

        return response()->json($invoice);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $invoice = Invoice::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'kode_bayar' => 'sometimes|unique:invoices,kode_bayar,' . $id,
            'status' => 'sometimes|string',
            'total_pembayaran' => 'sometimes|numeric',
            'upload_bukti' => 'nullable|mimes:jpeg,png,jpg,gif,pdf',
        ]);

        if ($request->hasFile('upload_bukti')) {
            $path = $request->file('upload_bukti')->store('bukti', 'public');
            $validated['upload_bukti'] = $path;
        }

        $invoice->update($validated);

        return response()->json([
            'validated' => $validated,
            'invoice' => $invoice->fresh()
        ]);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully']);
    }

    public function byAuth()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $invoices = Invoice::where('user_id', $user->id)->get();

        return response()->json($invoices);
    }
}
