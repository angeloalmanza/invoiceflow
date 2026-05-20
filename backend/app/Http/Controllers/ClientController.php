<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clients = $request->user()->clients()->withCount('invoices')->orderBy('name')->get();

        return response()->json($clients);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'nullable|email',
            'phone'      => 'nullable|string|max:50',
            'address'    => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:100',
            'country'    => 'nullable|string|max:100',
            'vat_number' => 'nullable|string|max:50',
        ]);

        $client = $request->user()->clients()->create($data);

        return response()->json($client, 201);
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        abort_unless($client->user_id === $request->user()->id, 403);

        return response()->json($client->load('invoices'));
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        abort_unless($client->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'name'       => 'sometimes|string|max:255',
            'email'      => 'nullable|email',
            'phone'      => 'nullable|string|max:50',
            'address'    => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:100',
            'country'    => 'nullable|string|max:100',
            'vat_number' => 'nullable|string|max:50',
        ]);

        $client->update($data);

        return response()->json($client);
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        abort_unless($client->user_id === $request->user()->id, 403);
        $client->delete();

        return response()->json(null, 204);
    }
}
