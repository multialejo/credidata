<?php

namespace App\Livewire;

use App\Models\Recarga;
use Livewire\Component;
use Livewire\WithPagination;

class Recibos extends Component
{
    use WithPagination;

    public function render()
    {
        $cliente = auth()->user()->cliente;
        $recargas = Recarga::where('cliente_id', $cliente->id)
            ->latest('fecha')->paginate(20);

        return view('livewire.recibos', ['recargas' => $recargas]);
    }
}
