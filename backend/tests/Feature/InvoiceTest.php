<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function payload(Client $client, array $override = []): array
    {
        return array_merge([
            'client_id' => $client->id,
            'date' => '2026-05-01',
            'tax_rate' => 22,
            'items' => [
                ['description' => 'Consulenza', 'quantity' => 2, 'unit_price' => 100],
                ['description' => 'Hosting', 'quantity' => 1, 'unit_price' => 50],
            ],
        ], $override);
    }

    /** SQLite restituisce numeri, PostgreSQL stringhe "250.00": confronto tollerante. */
    private function assertMoney($res, float $subtotal, float $tax, float $total): void
    {
        $this->assertEqualsWithDelta($subtotal, (float) $res->json('subtotal'), 0.001);
        $this->assertEqualsWithDelta($tax, (float) $res->json('tax_amount'), 0.001);
        $this->assertEqualsWithDelta($total, (float) $res->json('total'), 0.001);
    }

    private function actingUser(): array
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return [$user, $user->clients()->create(['name' => 'Cliente Srl'])];
    }

    public function test_totals_are_computed_by_the_server(): void
    {
        [, $client] = $this->actingUser();

        $res = $this->postJson('/api/invoices', $this->payload($client))
            ->assertCreated()
            ->assertJsonPath('number', 'INV-0001')
            ->assertJsonCount(2, 'items');

        $this->assertMoney($res, 250, 55, 305);
    }

    public function test_number_does_not_collide_after_deleting_an_invoice(): void
    {
        [, $client] = $this->actingUser();

        $first = $this->postJson('/api/invoices', $this->payload($client))->json('id');
        $this->postJson('/api/invoices', $this->payload($client))->assertJsonPath('number', 'INV-0002');
        $this->deleteJson("/api/invoices/{$first}")->assertNoContent();

        $this->postJson('/api/invoices', $this->payload($client))
            ->assertCreated()->assertJsonPath('number', 'INV-0003');
    }

    public function test_two_users_can_both_have_inv_0001(): void
    {
        [, $clientA] = $this->actingUser();
        $this->postJson('/api/invoices', $this->payload($clientA))->assertJsonPath('number', 'INV-0001');

        [, $clientB] = $this->actingUser();
        $this->postJson('/api/invoices', $this->payload($clientB))
            ->assertCreated()->assertJsonPath('number', 'INV-0001');
    }

    public function test_cannot_use_another_users_client(): void
    {
        $other = User::factory()->create()->clients()->create(['name' => 'Altrui']);
        $this->actingUser();

        $this->postJson('/api/invoices', $this->payload($other))
            ->assertUnprocessable()->assertJsonValidationErrors('client_id');
    }

    public function test_cannot_move_invoice_to_another_users_client(): void
    {
        $other = User::factory()->create()->clients()->create(['name' => 'Altrui']);
        [, $client] = $this->actingUser();
        $id = $this->postJson('/api/invoices', $this->payload($client))->json('id');

        $this->putJson("/api/invoices/{$id}", ['client_id' => $other->id])
            ->assertUnprocessable()->assertJsonValidationErrors('client_id');
    }

    public function test_changing_only_tax_rate_recalculates_totals(): void
    {
        [, $client] = $this->actingUser();
        $id = $this->postJson('/api/invoices', $this->payload($client))->json('id');

        $res = $this->putJson("/api/invoices/{$id}", ['tax_rate' => 10])->assertOk();

        $this->assertMoney($res, 250, 25, 275);
    }

    public function test_replacing_items_recalculates_totals_and_can_change_date_and_client(): void
    {
        [$user, $client] = $this->actingUser();
        $newClient = $user->clients()->create(['name' => 'Nuovo']);
        $id = $this->postJson('/api/invoices', $this->payload($client))->json('id');

        $res = $this->putJson("/api/invoices/{$id}", [
            'client_id' => $newClient->id,
            'date' => '2026-06-15',
            'items' => [['description' => 'Solo questo', 'quantity' => 1, 'unit_price' => 100]],
        ])->assertOk()
            ->assertJsonPath('client_id', $newClient->id)
            ->assertJsonCount(1, 'items');

        $this->assertMoney($res, 100, 22, 122);
    }

    public function test_status_can_advance_and_stats_follow(): void
    {
        [, $client] = $this->actingUser();
        $sent = $this->postJson('/api/invoices', $this->payload($client))->json('id');
        $paid = $this->postJson('/api/invoices', $this->payload($client))->json('id');
        $this->postJson('/api/invoices', $this->payload($client));

        $this->putJson("/api/invoices/{$sent}", ['status' => 'sent'])->assertOk();
        $this->putJson("/api/invoices/{$paid}", ['status' => 'paid'])->assertOk();

        $this->getJson('/api/invoices/stats')->assertOk()->assertJson([
            'count_draft' => 1, 'count_sent' => 1, 'count_paid' => 1,
            'total_paid' => 305, 'total_pending' => 305, 'total_invoiced' => 915,
        ]);
    }

    public function test_invalid_status_is_rejected(): void
    {
        [, $client] = $this->actingUser();
        $id = $this->postJson('/api/invoices', $this->payload($client))->json('id');

        $this->putJson("/api/invoices/{$id}", ['status' => 'boh'])->assertUnprocessable();
    }

    public function test_items_are_required_and_due_date_cannot_precede_date(): void
    {
        [, $client] = $this->actingUser();

        $this->postJson('/api/invoices', $this->payload($client, ['items' => []]))->assertUnprocessable();
        $this->postJson('/api/invoices', $this->payload($client, ['due_date' => '2026-04-01']))
            ->assertUnprocessable()->assertJsonValidationErrors('due_date');
    }

    public function test_user_cannot_read_edit_delete_or_download_another_users_invoice(): void
    {
        $owner = User::factory()->create();
        $invoice = Invoice::create([
            'user_id' => $owner->id,
            'client_id' => $owner->clients()->create(['name' => 'X'])->id,
            'number' => 'INV-0001', 'date' => '2026-05-01',
        ]);
        $this->actingUser();

        $this->getJson("/api/invoices/{$invoice->id}")->assertForbidden();
        $this->putJson("/api/invoices/{$invoice->id}", ['status' => 'paid'])->assertForbidden();
        $this->deleteJson("/api/invoices/{$invoice->id}")->assertForbidden();
        $this->get("/api/invoices/{$invoice->id}/pdf")->assertForbidden();
    }

    public function test_pdf_is_generated_for_the_owner(): void
    {
        [, $client] = $this->actingUser();
        $id = $this->postJson('/api/invoices', $this->payload($client))->json('id');

        $this->get("/api/invoices/{$id}/pdf")
            ->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_template_shows_status_in_italian(): void
    {
        [$user, $client] = $this->actingUser();
        $invoice = $user->invoices()->create([
            'client_id' => $client->id, 'number' => 'INV-0001', 'date' => '2026-05-01', 'status' => 'sent',
        ]);

        $html = view('invoices.pdf', ['invoice' => $invoice->load(['client', 'items', 'user'])])->render();

        $this->assertStringContainsString('Inviata', $html);
        $this->assertStringNotContainsString('Sent', $html);
    }
}
