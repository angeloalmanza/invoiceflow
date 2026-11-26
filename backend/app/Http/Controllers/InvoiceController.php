<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $user = $request->user();

        $data = $request->validate([
            'client_id' => ['required', Rule::exists('clients', 'id')->where('user_id', $user->id)],
            'date'      => 'required|date',
            'due_date'  => 'nullable|date|after_or_equal:date',
            'notes'     => 'nullable|string',
            'tax_rate'  => 'nullable|numeric|min:0|max:100',
            'items'     => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ]);

        $invoice = DB::transaction(function () use ($user, $data) {
            $invoice = $user->invoices()->create([
                'client_id' => $data['client_id'],
                'number'    => $this->nextNumber($user),
                'date'      => $data['date'],
                'due_date'  => $data['due_date'] ?? null,
                'notes'     => $data['notes'] ?? null,
            ]);

            $this->syncItems($invoice, $data['items'], $data['tax_rate'] ?? 0);

            return $invoice;
        });

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
            'client_id' => ['sometimes', Rule::exists('clients', 'id')->where('user_id', $request->user()->id)],
            'date'      => 'sometimes|date',
            'status'    => 'sometimes|in:draft,sent,paid',
            'due_date'  => 'nullable|date',
            'notes'     => 'nullable|string',
            'tax_rate'  => 'nullable|numeric|min:0|max:100',
            'items'     => 'sometimes|array|min:1',
            'items.*.description' => 'required_with:items|string',
            'items.*.quantity'    => 'required_with:items|numeric|min:0.01',
            'items.*.unit_price'  => 'required_with:items|numeric|min:0',
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $items = $data['items'] ?? null;
            $taxRate = $data['tax_rate'] ?? $invoice->tax_rate;
            unset($data['items']);

            $invoice->update($data);

            // Totali ricalcolati ogni volta che cambiano righe o aliquota.
            if ($items !== null) {
                $this->syncItems($invoice, $items, $taxRate);
            } elseif (array_key_exists('tax_rate', $data)) {
                $this->syncItems($invoice, $invoice->items->map->only(['description', 'quantity', 'unit_price'])->all(), $taxRate);
            }
        });

        return response()->json($invoice->fresh()->load(['client', 'items']));
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

    /** Prossimo numero libero dell'utente: massimo esistente + 1 (non il conteggio, che si rompe dopo le eliminazioni). */
    private function nextNumber($user): string
    {
        $max = $user->invoices()->pluck('number')
            ->map(fn ($n) => (int) preg_replace('/\D/', '', $n))
            ->max() ?? 0;

        return 'INV-' . str_pad($max + 1, 4, '0', STR_PAD_LEFT);
    }

    /** Sostituisce le righe e ricalcola subtotale, IVA e totale lato server. */
    private function syncItems(Invoice $invoice, array $items, $taxRate): void
    {
        $invoice->items()->delete();

        $subtotal = 0;
        foreach ($items as $item) {
            $lineTotal = round($item['quantity'] * $item['unit_price'], 2);
            $subtotal += $lineTotal;
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'total'       => $lineTotal,
            ]);
        }

        $taxAmount = round($subtotal * $taxRate / 100, 2);
        $invoice->update([
            'subtotal'   => $subtotal,
            'tax_rate'   => $taxRate,
            'tax_amount' => $taxAmount,
            'total'      => $subtotal + $taxAmount,
        ]);
    }
}
