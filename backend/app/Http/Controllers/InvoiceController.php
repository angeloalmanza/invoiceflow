<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invoices = $request->user()
            ->invoices()
            ->with('client')
            ->orderByDesc('date')
            ->get();

        return response()->json($invoices);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'date'      => 'required|date',
            'due_date'  => 'nullable|date',
            'notes'     => 'nullable|string',
            'tax_rate'  => 'nullable|numeric|min:0|max:100',
            'items'     => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $number   = 'INV-' . str_pad($request->user()->invoices()->count() + 1, 4, '0', STR_PAD_LEFT);
        $subtotal = collect($data['items'])->sum(fn($i) => $i['quantity'] * $i['unit_price']);
        $taxRate  = $data['tax_rate'] ?? 0;
        $taxAmount = round($subtotal * $taxRate / 100, 2);

        $invoice = $request->user()->invoices()->create([
            'client_id'  => $data['client_id'],
            'number'     => $number,
            'date'       => $data['date'],
            'due_date'   => $data['due_date'] ?? null,
            'notes'      => $data['notes'] ?? null,
            'tax_rate'   => $taxRate,
            'subtotal'   => $subtotal,
            'tax_amount' => $taxAmount,
            'total'      => $subtotal + $taxAmount,
        ]);

        foreach ($data['items'] as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'total'       => $item['quantity'] * $item['unit_price'],
            ]);
        }

        return response()->json($invoice->load(['client', 'items']), 201);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        return response()->json($invoice->load(['client', 'items']));
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'status'    => 'sometimes|in:draft,sent,paid',
            'due_date'  => 'nullable|date',
            'notes'     => 'nullable|string',
            'tax_rate'  => 'nullable|numeric|min:0|max:100',
            'items'     => 'sometimes|array|min:1',
            'items.*.description' => 'required_with:items|string',
            'items.*.quantity'    => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price'  => 'required_with:items|numeric|min:0',
        ]);

        if (isset($data['items'])) {
            $invoice->items()->delete();
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $subtotal += $lineTotal;
                $invoice->items()->create([...$item, 'total' => $lineTotal]);
            }
            $taxRate   = $data['tax_rate'] ?? $invoice->tax_rate;
            $taxAmount = round($subtotal * $taxRate / 100, 2);
            $data      = array_merge($data, [
                'subtotal'   => $subtotal,
                'tax_rate'   => $taxRate,
                'tax_amount' => $taxAmount,
                'total'      => $subtotal + $taxAmount,
            ]);
        }

        $invoice->update($data);

        return response()->json($invoice->load(['client', 'items']));
    }

    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);
        $invoice->delete();

        return response()->json(null, 204);
    }

    public function pdf(Request $request, Invoice $invoice): Response
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);
        $invoice->load(['client', 'items', 'user']);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));

        return $pdf->download("fattura-{$invoice->number}.pdf");
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'total_invoiced' => $user->invoices()->sum('total'),
            'total_paid'     => $user->invoices()->where('status', 'paid')->sum('total'),
            'total_pending'  => $user->invoices()->where('status', 'sent')->sum('total'),
            'count_draft'    => $user->invoices()->where('status', 'draft')->count(),
            'count_sent'     => $user->invoices()->where('status', 'sent')->count(),
            'count_paid'     => $user->invoices()->where('status', 'paid')->count(),
        ]);
    }
}
