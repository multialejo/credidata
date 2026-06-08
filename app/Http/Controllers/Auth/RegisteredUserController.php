<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\LogActividad;
use App\Models\Usuario;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        $uid = (string) Str::uuid();

        $usuario = Usuario::create([
            'uid' => $uid,
            'email' => $request->email,
            'nombre' => $request->name,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
            'estado' => 'activo',
            'roles' => ['cliente'],
        ]);

        Cliente::create([
            'uid' => $uid,
            'saldo_creditos' => 0,
            'metodo_pago_preferido' => 'paypal',
        ]);

        LogActividad::create([
            'accion' => 'CLIENTE_REGISTRADO',
            'actor_id' => $uid,
            'detalle' => ['email' => $request->email, 'nombre' => $request->name],
            'ip_origen' => $request->ip(),
        ]);

        event(new Registered($usuario));

        Auth::login($usuario);

        return redirect(route('dashboard', absolute: false));
    }
}
