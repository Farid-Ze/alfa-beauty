<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class MyOrders extends Component
{
    use WithPagination;

    /**
     * Number of orders to show per page
     */
    protected int $perPage = 10;

    public function render()
    {
        $orders = Auth::user()
            ->orders()
            ->with(['items.product', 'pointTransactions'])
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.my-orders', [
            'orders' => $orders
        ]);
    }
}
