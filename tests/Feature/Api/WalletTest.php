<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase; // Resetea la base de datos en cada prueba

    protected function setUp(): void
    {
        parent::setUp();
        // Creamos usuarios de prueba
        $this->sender = User::factory()->create(['saldo' => 1000, 'name' => 'Emisor']);
        $this->receiver = User::factory()->create(['saldo' => 500, 'name' => 'Receptor']);
    }

    /** @test */
    public function una_transferencia_exitosa_actualiza_saldos_y_crea_registro()
    {
        $response = $this->actingAs($this->sender)
            ->postJson('/api/transfer', [
                'email' => $this->receiver->email,
                'amount' => 200,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Transferencia realizada con éxito');

        // Verificar saldos actualizados
        $this->assertEquals(800, $this->sender->fresh()->saldo);
        $this->assertEquals(700, $this->receiver->fresh()->saldo);

        // Verificar que la transacción existe en la BD
        $this->assertDatabaseHas('transactions', [
            'from_user_id' => $this->sender->id,
            'to_user_id' => $this->receiver->id,
            'amount' => 200,
        ]);
    }

    /** @test */
    public function no_se_puede_transferir_si_el_saldo_es_insuficiente()
    {
        $response = $this->actingAs($this->sender)
            ->postJson('/api/transfer', [
                'email' => $this->receiver->email,
                'amount' => 5000, // Monto mayor al saldo de 1000
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Saldo insuficiente');
            
        // El saldo debe permanecer igual
        $this->assertEquals(1000, $this->sender->fresh()->saldo);
    }

    /** @test */
    public function no_se_puede_superar_el_limite_diario_de_5000()
    {
        // 1. Simular que el usuario ya envió 4900 hoy
        Transaction::factory()->create([
            'from_user_id' => $this->sender->id,
            'to_user_id' => $this->receiver->id,
            'amount' => 4900,
            'created_at' => now(),
        ]);

        // 2. Intentar enviar 200 más (Total: 5100)
        $response = $this->actingAs($this->sender)
            ->postJson('/api/transfer', [
                'email' => $this->receiver->email,
                'amount' => 200,
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Límite diario excedido']);
    }

    /** @test */
    public function un_usuario_no_puede_transferirse_a_si_mismo()
    {
        $response = $this->actingAs($this->sender)
            ->postJson('/api/transfer', [
                'email' => $this->sender->email,
                'amount' => 100,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'No puedes enviarte saldo a ti mismo');
    }
}