<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
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
            $validated['total_pembayaran'] = 60000;
        } elseif ($request->jenis_lomba === 'science-writing') {
            $validated['total_pembayaran'] = 65000;
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
        $idd = Auth::user()->id;
        $invoice = Invoice::findOrFail($idd);

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
            'upload_bukti' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // validasi file gambar
        ]);

        // Jika ada file upload_bukti
        if ($request->hasFile('upload_bukti')) {
            $file = $request->file('upload_bukti');
            $filename = time() . '_' . $file->getClientOriginalName();

            // Simpan file ke folder public/storage/bukti (bisa sesuaikan)
            $file->storeAs('public/bukti', $filename);

            // Simpan path file di $validated supaya bisa diupdate ke DB
            $validated['upload_bukti'] = 'bukti/' . $filename;
        } else {
            // Jika tidak ada file di request, hapus field upload_bukti dari validated supaya tidak diupdate
            unset($validated['upload_bukti']);
        }

        $invoice->update($validated);

        return response()->json([
            'validated' => $validated,
            'invoice' => $invoice
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
