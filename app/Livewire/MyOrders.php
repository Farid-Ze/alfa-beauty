<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class MyOrders extends Component
{
    public function render()
    {
        $orders = Auth::user()
            ->orders() // Assuming relationship exists
            ->with(['items.product', 'pointTransactions'])
            ->latest()
            ->get();

        return view('livewire.my-orders', [
            'orders' => $orders
        ]);
    }
}
