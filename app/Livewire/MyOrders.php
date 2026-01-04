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
    
    /**
     * Success message from checkout
     */
    public string $successMessage = '';
    
    /**
     * WhatsApp URL for WhatsApp orders
     */
    public string $whatsappUrl = '';

    public function mount()
    {
        // Handle order success from checkout redirect
        $orderSuccess = request()->query('order_success');
        if ($orderSuccess) {
            $via = request()->query('via');
            if ($via === 'whatsapp') {
                $this->successMessage = '✅ Pesanan #' . $orderSuccess . ' berhasil dibuat! Silakan hubungi kami via WhatsApp untuk konfirmasi pembayaran.';
                $this->whatsappUrl = session()->pull('whatsapp_url', '');
            } else {
                $this->successMessage = '✅ Pesanan #' . $orderSuccess . ' berhasil dibuat! Kami akan segera memproses pesanan Anda.';
            }
            // Set flash for toast display
            session()->flash('success', $this->successMessage);
        }
    }

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
