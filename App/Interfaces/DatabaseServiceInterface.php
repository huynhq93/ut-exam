<?php

namespace App\Interfaces;

interface DatabaseServiceInterface
{
    public function getOrdersByUser(int $userId): array;
    public function updateOrderStatus(int $orderId, string $status, string $priority): bool;
} 