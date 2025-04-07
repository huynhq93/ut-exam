<?php

namespace App\Processors;

use App\Interfaces\OrderProcessorInterface;
use App\Order;

class TypeCOrderProcessor implements OrderProcessorInterface
{
    public function process(Order $order): Order
    {
        $order->status = $order->flag ? 'completed' : 'in_progress';
        $order->priority = $order->amount > 200 ? 'high' : 'low';
        return $order;
    }
}
