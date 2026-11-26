<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_crud_and_invoices_count(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->postJson('/api/clients', ['name' => 'Rossi Srl', 'vat_number' => 'IT123'])
            ->assertCreated()->json('id');

        $this->putJson("/api/clients/{$id}", ['city' => 'Palermo'])->assertOk()->assertJsonPath('city', 'Palermo');
        $this->getJson('/api/clients')->assertOk()->assertJsonPath('0.invoices_count', 0);
        $this->deleteJson("/api/clients/{$id}")->assertNoContent();
        $this->getJson('/api/clients')->assertOk()->assertJsonCount(0);
    }

    public function test_name_is_required_and_email_must_be_valid(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/clients', [])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson('/api/clients', ['name' => 'A', 'email' => 'no'])->assertUnprocessable();
    }

    public function test_clients_are_isolated_per_user(): void
    {
        $other = User::factory()->create()->clients()->create(['name' => 'Altrui']);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/clients')->assertJsonCount(0);
        $this->getJson("/api/clients/{$other->id}")->assertForbidden();
        $this->putJson("/api/clients/{$other->id}", ['name' => 'Hack'])->assertForbidden();
        $this->deleteJson("/api/clients/{$other->id}")->assertForbidden();
    }
}
