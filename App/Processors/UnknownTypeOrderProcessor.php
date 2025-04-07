<?php

namespace App\Processors;

use App\Interfaces\OrderProcessorInterface;
use App\Order;

class UnknownTypeOrderProcessor implements OrderProcessorInterface
{
    public function process(Order $order): Order
    {
        $order->priority = $order->amount > 200 ? 'high' : 'low';
        $order->status = 'unknown_type';
        return $order;
    }
}
