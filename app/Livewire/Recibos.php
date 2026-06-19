<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Recarga;

class Recibos extends Component
{
    use WithPagination;

    public function render()
    {
        $cliente = auth()->user()->cliente;
        $recargas = Recarga::where('cliente_id', $cliente->uid)
            ->latest('fecha')->paginate(20);

        return view('livewire.recibos', ['recargas' => $recargas]);
    }
}
