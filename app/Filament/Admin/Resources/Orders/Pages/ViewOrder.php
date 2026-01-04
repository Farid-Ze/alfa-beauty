<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            
            // Confirm Payment Action
            Action::make('confirmPayment')
                ->label('Confirm Payment')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->payment_status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Confirm Payment')
                ->modalDescription('This will mark the order as paid and award loyalty points to the customer.')
                ->form([
                    TextInput::make('reference_number')
                        ->label('Reference Number')
                        ->placeholder('Bank transfer reference, etc.'),
                ])
                ->action(function (array $data): void {
                    app(OrderService::class)->confirmWhatsAppPayment(
                        $this->record,
                        auth()->id(),
                        $data['reference_number'] ?? null
                    );
                    Notification::make()
                        ->title('Payment confirmed')
                        ->body("Order #{$this->record->order_number} has been marked as paid.")
                        ->success()
                        ->send();
                    $this->refreshFormData(['status', 'payment_status']);
                }),
            
            // Mark as Shipped Action
            Action::make('markShipped')
                ->label('Mark Shipped')
                ->icon('heroicon-o-truck')
                ->color('info')
                ->visible(fn (): bool => $this->record->status === 'processing')
                ->requiresConfirmation()
                ->modalHeading('Mark as Shipped')
                ->action(function (): void {
                    $this->record->update(['status' => Order::STATUS_SHIPPED]);
                    Notification::make()
                        ->title('Order shipped')
                        ->body("Order #{$this->record->order_number} has been marked as shipped.")
                        ->success()
                        ->send();
                    $this->refreshFormData(['status']);
                }),
            
            // Mark as Delivered Action
            Action::make('markDelivered')
                ->label('Mark Delivered')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'shipped')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['status' => Order::STATUS_DELIVERED]);
                    Notification::make()
                        ->title('Order delivered')
                        ->body("Order #{$this->record->order_number} has been marked as delivered.")
                        ->success()
                        ->send();
                    $this->refreshFormData(['status']);
                }),
            
            // Cancel Order Action
            Action::make('cancelOrder')
                ->label('Cancel Order')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->canBeCancelled())
                ->requiresConfirmation()
                ->modalHeading('Cancel Order')
                ->modalDescription('This will cancel the order and restore inventory. This action cannot be undone.')
                ->form([
                    Select::make('reason_code')
                        ->label('Cancellation Reason')
                        ->options([
                            'customer_request' => 'Customer Request',
                            'out_of_stock' => 'Out of Stock',
                            'payment_failed' => 'Payment Failed',
                            'duplicate_order' => 'Duplicate Order',
                            'fraud_suspected' => 'Fraud Suspected',
                            'other' => 'Other',
                        ])
                        ->required(),
                    Textarea::make('notes')
                        ->label('Additional Notes'),
                ])
                ->action(function (array $data): void {
                    app(OrderService::class)->cancelOrder(
                        $this->record,
                        $data['reason_code'],
                        $data['notes'] ?? null,
                        auth()->id()
                    );
                    Notification::make()
                        ->title('Order cancelled')
                        ->body("Order #{$this->record->order_number} has been cancelled.")
                        ->warning()
                        ->send();
                    $this->refreshFormData(['status', 'payment_status']);
                }),
        ];
    }
}
