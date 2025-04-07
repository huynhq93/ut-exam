<?php

namespace App\Processors;

use App\Interfaces\FileSystemInterface;
use App\Interfaces\OrderProcessorInterface;
use App\Order;

class TypeAOrderProcessor implements OrderProcessorInterface
{
    private FileSystemInterface $fileSystem;

    public function __construct(FileSystemInterface $fileSystem)
    {
        $this->fileSystem = $fileSystem;
    }

    public function process(Order $order): Order
    {
        try {
            $this->export($order);
            $order->status = 'exported';
        } catch (\Exception $e) {
            $order->status = 'export_failed';
        }

        $order->priority = $order->amount > 200 ? 'high' : 'low';
        return $order;
    }

    private function export(Order $order): void
    {
        $csvFile = 'orders_type_A_' . $order->id . '_' . time() . '.csv';
        $fileHandle = $this->fileSystem->fopen($csvFile, 'w');

        if ($fileHandle === false) {
            throw new \Exception('Failed to open CSV file');
        }

        $this->fileSystem->fputcsv($fileHandle, ['ID', 'Type', 'Amount', 'Flag', 'Status', 'Priority']);
        $this->fileSystem->fputcsv($fileHandle, [
            $order->id,
            $order->type,
            $order->amount,
            $order->flag ? 'true' : 'false',
            $order->status,
            $order->priority
        ]);

        if ($order->amount > 150) {
            $this->fileSystem->fputcsv($fileHandle, ['', '', '', '', 'Note', 'High value order']);
        }

        $this->fileSystem->fclose($fileHandle);
    }
}
