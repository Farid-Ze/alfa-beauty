<?php

namespace App\Filament\Admin\Resources\Orders\Tables;

use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->placeholder('Guest'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed', 'paid', 'delivered' => 'success',
                        'processing', 'shipped' => 'info',
                        'pending', 'pending_payment' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('total_amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'pending_payment' => 'Pending Payment',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ]),
                SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                
                // Confirm Payment Action
                Action::make('confirmPayment')
                    ->label('Confirm Payment')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->payment_status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Payment')
                    ->modalDescription('This will mark the order as paid and award loyalty points to the customer.')
                    ->form([
                        TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->placeholder('Bank transfer reference, etc.'),
                    ])
                    ->action(function (Order $record, array $data): void {
                        app(OrderService::class)->confirmWhatsAppPayment(
                            $record,
                            auth()->id(),
                            $data['reference_number'] ?? null
                        );
                        Notification::make()
                            ->title('Payment confirmed')
                            ->body("Order #{$record->order_number} has been marked as paid.")
                            ->success()
                            ->send();
                    }),
                
                // Mark as Shipped Action
                Action::make('markShipped')
                    ->label('Mark Shipped')
                    ->icon('heroicon-o-truck')
                    ->color('info')
                    ->visible(fn (Order $record): bool => $record->status === 'processing')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Shipped')
                    ->modalDescription('This will update the order status to shipped.')
                    ->action(function (Order $record): void {
                        $record->update(['status' => Order::STATUS_SHIPPED]);
                        Notification::make()
                            ->title('Order shipped')
                            ->body("Order #{$record->order_number} has been marked as shipped.")
                            ->success()
                            ->send();
                    }),
                
                // Mark as Delivered Action
                Action::make('markDelivered')
                    ->label('Mark Delivered')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === 'shipped')
                    ->requiresConfirmation()
                    ->action(function (Order $record): void {
                        $record->update(['status' => Order::STATUS_DELIVERED]);
                        Notification::make()
                            ->title('Order delivered')
                            ->body("Order #{$record->order_number} has been marked as delivered.")
                            ->success()
                            ->send();
                    }),
                
                // Cancel Order Action
                Action::make('cancelOrder')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Order $record): bool => $record->canBeCancelled())
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
                            ->label('Additional Notes')
                            ->placeholder('Optional notes about the cancellation'),
                    ])
                    ->action(function (Order $record, array $data): void {
                        app(OrderService::class)->cancelOrder(
                            $record,
                            $data['reason_code'],
                            $data['notes'] ?? null,
                            auth()->id()
                        );
                        Notification::make()
                            ->title('Order cancelled')
                            ->body("Order #{$record->order_number} has been cancelled.")
                            ->warning()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
