<?php

namespace Tests\Feature\Jobs;

use App\Enums\EstadoRecarga;
use App\Jobs\NotifyStaffTransferSubmitted;
use App\Mail\TransferenciaPendiente;
use App\Models\Cliente;
use App\Models\Recarga;
use App\Models\Staff;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotifyStaffTransferSubmittedTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifies_admin_and_support_but_not_the_customer(): void
    {
        Storage::fake('local');
        Mail::fake();

        $admin = $this->crearStaff('admin@test.com', 'admin');
        $support = $this->crearStaff('support@test.com', 'support');
        $this->crearStaff('other-staff@test.com', 'auditor');
        $clienteUsuario = Usuario::create(['uid' => 'cliente-uid', 'email' => 'cliente@test.com', 'nombre' => 'Cliente', 'roles' => ['cliente']]);
        $cliente = Cliente::create(['usuario_id' => $clienteUsuario->id, 'saldo_creditos' => 0]);
        $path = UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf')->store('recargas/comprobantes', 'local');
        $recarga = Recarga::create([
            'cliente_id' => $cliente->id,
            'metodo' => 'transferencia',
            'monto_usd' => 10,
            'creditos_obtenidos' => 100,
            'estado' => EstadoRecarga::Pendiente,
            'referencia_externa' => 'BANK-NOTIFY-001',
            'comprobante_url' => $path,
        ]);

        (new NotifyStaffTransferSubmitted($recarga))->handle();

        Mail::assertSent(TransferenciaPendiente::class, function (TransferenciaPendiente $mail) use ($admin): bool {
            $html = $mail->render();

            return $mail->hasTo($admin->email)
                && ! str_contains($html, '<a ')
                && ! str_contains($html, 'href=');
        });
        Mail::assertSent(TransferenciaPendiente::class, fn (TransferenciaPendiente $mail) => $mail->hasTo($support->email));
        Mail::assertSentCount(2);
        Mail::assertNotSent(TransferenciaPendiente::class, fn (TransferenciaPendiente $mail) => $mail->hasTo($clienteUsuario->email));
    }

    private function crearStaff(string $email, string $rol): Usuario
    {
        $usuario = Usuario::create([
            'uid' => $rol.'-uid',
            'email' => $email,
            'nombre' => ucfirst($rol),
            'roles' => ['staff'],
        ]);
        Staff::create(['usuario_id' => $usuario->id, 'rol_staff' => $rol, 'fecha_asignacion' => now()]);

        return $usuario;
    }
}
