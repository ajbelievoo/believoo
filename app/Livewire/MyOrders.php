<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MyOrders extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?int $viewingOrderId = null;

    public function viewOrder(?int $id): void
    {
        $this->viewingOrderId = $id;
    }

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Auth::user()->orders()->with(['service', 'hosting']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('order_number', 'like', '%' . $this->search . '%')
                  ->orWhere('service_name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $orders = $query->latest()->paginate(10);

        $viewingOrder = null;
        if ($this->viewingOrderId) {
            $viewingOrder = $orders->firstWhere('id', $this->viewingOrderId);
        }

        return view('livewire.my-orders', [
            'orders' => $orders,
            'viewingOrder' => $viewingOrder,
            'statuses' => [
                'pending' => 'Pending',
                'paid' => 'Paid',
                'failed' => 'Failed',
                'cancelled' => 'Cancelled',
                'refunded' => 'Refunded',
            ],
        ])->layout('components.layouts.believoo');
    }
}
