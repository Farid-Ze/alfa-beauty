<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\PointTransaction;
use Livewire\Attributes\Layout;
use Livewire\Component;

class OrderSuccess extends Component
{
    public Order $order;
    public int $earnedPoints = 0;

    public function mount($order)
    {
        $this->order = Order::findOrFail($order);
        
        // Verify order belongs to the authenticated user
        if ($this->order->user_id !== auth()->id()) {
            abort(403, 'You are not authorized to view this order.');
        }
        
        // Get points earned from this order
        $pointTransaction = PointTransaction::where('order_id', $this->order->id)
            ->where('type', 'earn')
            ->first();
            
        $this->earnedPoints = $pointTransaction?->amount ?? 0;
    }

    public function render()
    {
        return view('livewire.order-success');
    }
}
