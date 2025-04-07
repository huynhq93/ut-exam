<?php

namespace App\Interfaces;

use App\Order;

interface OrderProcessorInterface
{
    public function process(Order $order): Order;
} 