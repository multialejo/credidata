<?php

namespace Tests\Feature\Web;

use App\Enums\EstadoRecarga;
use App\Livewire\Recibos;
use App\Models\Cliente;
use App\Models\Recarga;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecibosPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_cliente_puede_descargar_pdf_de_su_recarga_completada(): void
    {
        [$usuario, $cliente] = $this->crearCliente('cliente@test.com');
        $recarga = $this->crearRecarga($cliente, EstadoRecarga::Completada);

        $response = $this->actingAs($usuario)
            ->get(route('dashboard.recibos.pdf', $recarga));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=recibo-recarga-'.$recarga->id.'.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_cliente_no_puede_descargar_recibo_de_otro_cliente(): void
    {
        [$usuario] = $this->crearCliente('cliente@test.com');
        [, $otroCliente] = $this->crearCliente('otro@test.com');
        $recarga = $this->crearRecarga($otroCliente, EstadoRecarga::Completada);

        $this->actingAs($usuario)
            ->get(route('dashboard.recibos.pdf', $recarga))
            ->assertNotFound();
    }

    public function test_recibo_no_se_descarga_para_recargas_no_completadas(): void
    {
        [$usuario, $cliente] = $this->crearCliente('cliente@test.com');
        $recarga = $this->crearRecarga($cliente, EstadoRecarga::Pendiente);

        $this->actingAs($usuario)
            ->get(route('dashboard.recibos.pdf', $recarga))
            ->assertNotFound();
    }

    public function test_tabla_muestra_descarga_solo_para_recargas_completadas(): void
    {
        [$usuario, $cliente] = $this->crearCliente('cliente@test.com');
        $completada = $this->crearRecarga($cliente, EstadoRecarga::Completada);
        $this->crearRecarga($cliente, EstadoRecarga::Pendiente);

        Livewire::actingAs($usuario)
            ->test(Recibos::class)
            ->assertSee('Recibo')
            ->assertSee(route('dashboard.recibos.pdf', $completada))
            ->assertSee('title="Descargar PDF"', false)
            ->assertSee('Descargar PDF')
            ->assertSee('No disponible');
    }

    /** @return array{Usuario, Cliente} */
    private function crearCliente(string $email): array
    {
        $usuario = Usuario::create([
            'uid' => $email,
            'email' => $email,
            'nombre' => 'Cliente de prueba',
            'roles' => ['cliente'],
            'email_verified_at' => now(),
        ]);

        $cliente = Cliente::create([
            'usuario_id' => $usuario->id,
            'saldo_creditos' => 0,
        ]);

        return [$usuario, $cliente];
    }

    private function crearRecarga(Cliente $cliente, EstadoRecarga $estado): Recarga
    {
        return Recarga::create([
            'cliente_id' => $cliente->id,
            'metodo' => 'paypal',
            'monto_usd' => 25,
            'creditos_obtenidos' => 250,
            'estado' => $estado,
            'fecha' => now(),
        ]);
    }
}
