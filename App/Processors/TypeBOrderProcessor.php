<?php

namespace App\Processors;

use App\APIException;
use App\APIResponse;
use App\Interfaces\APIClientInterface;
use App\Interfaces\OrderProcessorInterface;
use App\Order;

class TypeBOrderProcessor implements OrderProcessorInterface
{
    private APIClientInterface $apiClient;

    public function __construct(APIClientInterface $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function process(Order $order): Order
    {
        $order->priority = 'low';

        try {
            $apiResponse = $this->apiClient->callAPI($order->id);

            if ($apiResponse->status === 'success') {
                if ($apiResponse->data >= 50 && $order->amount < 100) {
                    $order->status = 'processed';
                } elseif ($apiResponse->data < 50 || $order->flag) {
                    $order->status = 'pending';
                } else {
                    $order->status = 'error';
                }
            } else {
                $order->status = 'api_error';
            }
        } catch (APIException $e) {
            $order->status = 'api_failure';
        }

        $order->priority = $order->amount > 200 ? 'high' : 'low';

        return $order;
    }
}
