<?php

namespace App;

use App\Factories\OrderProcessorFactory;
use App\Interfaces\DatabaseServiceInterface;

class OrderProcessingService
{
    private DatabaseServiceInterface $dbService;
    private OrderProcessorFactory $processorFactory;

    public function __construct(
        DatabaseServiceInterface $dbService,
        OrderProcessorFactory $processorFactory
    ) {
        $this->dbService = $dbService;
        $this->processorFactory = $processorFactory;
    }

    public function processOrders(int $userId): array|false
    {
        try {
            $orders = $this->dbService->getOrdersByUser($userId);
            $processedOrders = [];

            foreach ($orders as $order) {
                $processor = $this->processorFactory->getProcessor($order->type);
                $processedOrder = $processor->process($order);

                try {
                    $this->dbService->updateOrderStatus($order->id, $order->status, $order->priority);
                } catch (DatabaseException $e) {
                    $processedOrder->status = 'db_error';
                }

                $processedOrders[] = $processedOrder;
            }

            return $processedOrders;
        } catch (\Exception $e) {
            return false;
        }
    }
}
