<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\ConfigParametro;
use App\Models\LogActividad;
use App\Models\Usuario;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.Usuario::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $creditosBienvenida = ConfigParametro::where('modulo', 'financiero')
            ->where('clave', 'creditosBienvenida')
            ->value('valor');
        $creditosBienvenida = json_decode($creditosBienvenida ?? '0');
        $creditosBienvenida = is_int($creditosBienvenida) && $creditosBienvenida >= 0 && $creditosBienvenida <= 99_999_999
            ? $creditosBienvenida
            : 0;

        $usuario = DB::transaction(function () use ($request, $creditosBienvenida): Usuario {
            $usuario = Usuario::create([
                'email' => $request->email,
                'nombre' => $request->name,
                'password' => Hash::make($request->password),
                'estado' => 'activo',
                'roles' => ['cliente'],
            ]);

            Cliente::create([
                'usuario_id' => $usuario->id,
                'saldo_creditos' => $creditosBienvenida,
                'metodo_pago_preferido' => 'paypal',
            ]);

            LogActividad::create([
                'accion' => 'CLIENTE_REGISTRADO',
                'actor_id' => $usuario->id,
                'detalle' => [
                    'email' => $request->email,
                    'nombre' => $request->name,
                    'creditos_bienvenida' => $creditosBienvenida,
                ],
                'ip_origen' => $request->ip(),
            ]);

            return $usuario;
        });

        event(new Registered($usuario));

        Auth::login($usuario);

        return redirect(route('verification.notice', absolute: false));
    }
}
