<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            
            // Confirm Payment Action
            Action::make('confirmPayment')
                ->label('Confirm Payment')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->payment_status === 'pending')
                ->requiresConfirmation()
                ->modalHeading('Confirm Payment')
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
                        ->success()
                        ->send();
                }),
            
            // Mark as Shipped Action
            Action::make('markShipped')
                ->label('Mark Shipped')
                ->icon('heroicon-o-truck')
                ->color('info')
                ->visible(fn (): bool => $this->record->status === 'processing')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['status' => Order::STATUS_SHIPPED]);
                    Notification::make()
                        ->title('Order shipped')
                        ->success()
                        ->send();
                }),
            
            // Cancel Order Action  
            Action::make('cancelOrder')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->canBeCancelled())
                ->requiresConfirmation()
                ->form([
                    Select::make('reason_code')
                        ->label('Reason')
                        ->options([
                            'customer_request' => 'Customer Request',
                            'out_of_stock' => 'Out of Stock',
                            'payment_failed' => 'Payment Failed',
                            'other' => 'Other',
                        ])
                        ->required(),
                    Textarea::make('notes')->label('Notes'),
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
                        ->warning()
                        ->send();
                }),
            
            DeleteAction::make(),
        ];
    }
}
