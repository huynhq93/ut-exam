<?php

namespace App\Factories;

use App\Interfaces\APIClientInterface;
use App\Interfaces\FileSystemInterface;
use App\Interfaces\OrderProcessorInterface;
use App\Processors\TypeAOrderProcessor;
use App\Processors\TypeBOrderProcessor;
use App\Processors\TypeCOrderProcessor;
use App\Processors\UnknownTypeOrderProcessor;

class OrderProcessorFactory
{
    private array $processors = [];
    private UnknownTypeOrderProcessor $unknownTypeProcessor;

    public function __construct(
        APIClientInterface $apiClient,
        FileSystemInterface $fileSystem
    ) {
        $this->processors = [
            'A' => new TypeAOrderProcessor($fileSystem),
            'B' => new TypeBOrderProcessor($apiClient),
            'C' => new TypeCOrderProcessor(),
        ];
        $this->unknownTypeProcessor = new UnknownTypeOrderProcessor();
    }

    public function getProcessor(string $type): OrderProcessorInterface
    {
        return $this->processors[$type] ?? $this->unknownTypeProcessor;
    }
}
