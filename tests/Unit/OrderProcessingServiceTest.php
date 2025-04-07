<?php

use App\APIException;
use App\APIResponse;
use App\DatabaseException;
use App\Exporters\CSVExporter;
use App\Factories\OrderProcessorFactory;
use App\FileSystem;
use App\Interfaces\DatabaseServiceInterface;
use App\Interfaces\APIClientInterface;
use App\Interfaces\CSVExporterInterface;
use App\Interfaces\FileSystemInterface;
use App\Order;
use App\OrderProcessingService;

beforeEach(function () {
    $this->dbService = Mockery::mock(DatabaseServiceInterface::class);
    $this->apiClient = Mockery::mock(APIClientInterface::class);
    $this->fileSystem = Mockery::mock(FileSystemInterface::class);
    $this->processorFactory = new OrderProcessorFactory($this->apiClient, new FileSystem);
    $this->service = new OrderProcessingService($this->dbService, $this->processorFactory);
});

// 1. Trường hợp chung
test('processOrders returns list of processed orders', function () {
    $order = new Order(1, 'A', 100, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->dbService->shouldReceive('updateOrderStatus')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->status)->toBe('exported');
});

test('processOrders returns false on general exception', function () {
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andThrow(new \Exception('General error'));

    $result = $this->service->processOrders(1);

    expect($result)->toBeFalse();
});

// 2. Trường hợp không có đơn hàng
test('processOrders handles empty order list', function () {
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([]);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result)->toBeEmpty();
});

// 3. Trường hợp loại đơn hàng A
test('processOrders handles type A orders with successful CSV export', function () {
    $order = new Order(1, 'A', 100, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'exported', 'low')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->status)->toBe('exported');
});

test('processOrders handles type A orders with high value', function () {
    $order = new Order(1, 'A', 250, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'exported', 'high')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->status)->toBe('exported')
        ->and($result[0]->priority)->toBe('high');
});


test('processOrders handles type A orders with low value', function () {
    $order = new Order(1, 'A', 150, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'exported', 'low')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->status)->toBe('exported')
        ->and($result[0]->priority)->toBe('low');
});

test('processOrders handles type A orders with CSV export failure', function () {
    $order = new Order(1, 'A', 100, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'export_failed', 'low')
        ->andReturn(true);

    // Mock FileSystemInterface để simulate fopen failure
    $this->fileSystem->shouldReceive('fopen')
        ->once()
        ->andReturn(false);

    $processorFactory = new OrderProcessorFactory($this->apiClient, $this->fileSystem);
    $service = new OrderProcessingService($this->dbService, $processorFactory);

    $result = $service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->status)->toBe('export_failed');
});

// 4. Trường hợp loại đơn hàng B
test('processOrders handles type B orders with successful API response', function () {
    $order = new Order(1, 'B', 80, false);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->apiClient->shouldReceive('callAPI')
        ->with(1)
        ->andReturn(new APIResponse('success', 60));
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'processed', 'low')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->status)->toBe('processed');
});

test('processOrders handles type B orders with high value', function () {
    $order = new Order(1, 'B', 260, false);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->apiClient->shouldReceive('callAPI')
        ->with(1)
        ->andReturn(new APIResponse('success', 40));
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'pending', 'high')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->priority)->toBe('high');
});

test('processOrders handles type B orders with low value', function () {
    $order = new Order(1, 'B', 80, false);

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->apiClient->shouldReceive('callAPI')
        ->with(1)
        ->andReturn(new APIResponse('success', 60));
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'processed', 'low')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->priority)->toBe('low');
});

test('processOrders handles type B orders with pending status', function () {
    $order = new Order(1, 'B', 80, true);
    $responseOrder = new Order(1, 'B', 80, true);
    $responseOrder->status = 'success';
    $responseOrder->data = 40;

    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->apiClient->shouldReceive('callAPI')
        ->with(1)
        ->andReturn(new APIResponse('success', 40));
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'pending', 'low')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->status)->toBe('pending');
});

test('processOrders handles type B orders with API error', function () {
    $order = new Order(1, 'B', 80, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->apiClient->shouldReceive('callAPI')
        ->with(1)
        ->andThrow(new APIException('API Error'));
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'api_failure', 'low')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->status)->toBe('api_failure');
});

test('processOrders handles type B orders with API success but return status error', function () {
    $order = new Order(1, 'B', 200, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->apiClient->shouldReceive('callAPI')
        ->with(1)
        ->andReturn(new APIResponse('success', 60));
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'error', 'low')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->status)->toBe('error');
});

// 5. Trường hợp loại đơn hàng C
test('processOrders handles type C orders with completed status', function () {
    $order = new Order(1, 'C', 100, true);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'completed', 'low')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->status)->toBe('completed');
});

test('processOrders handles type C orders with in_progress status', function () {
    $order = new Order(1, 'C', 100, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'in_progress', 'low')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->status)->toBe('in_progress');
});

test('processOrders handles type C orders with high value', function () {
    $order = new Order(1, 'C', 300, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'in_progress', 'high')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->priority)->toBe('high');
});

test('processOrders handles type C orders with low value', function () {
    $order = new Order(1, 'C', 100, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'in_progress', 'low')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->priority)->toBe('low');
});

// 6. Trường hợp loại đơn hàng không xác định
test('processOrders handles unknown order type', function () {
    $order = new Order(1, 'D', 100, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'unknown_type', 'low')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->status)->toBe('unknown_type');
});

test('processOrders handles unknown order type with high value', function () {
    $order = new Order(1, 'D', 300, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'unknown_type', 'high')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->priority)->toBe('high');
});

test('processOrders handles unknown order type with low value', function () {
    $order = new Order(1, 'D', 100, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'unknown_type', 'low')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->priority)->toBe('low');
});

// 7. Kiểm tra cập nhật trạng thái đơn hàng vào DB
test('processOrders updates order priority based on amount', function () {
    $order = new Order(1, 'A', 250, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'exported', 'high')
        ->andReturn(true);

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->priority)->toBe('high');
});

test('processOrders handles database exception', function () {
    $order = new Order(1, 'A', 100, false);
    $this->dbService->shouldReceive('getOrdersByUser')
        ->with(1)
        ->andReturn([$order]);
    $this->dbService->shouldReceive('updateOrderStatus')
        ->with(1, 'exported', 'low')
        ->andThrow(new DatabaseException());

    $result = $this->service->processOrders(1);

    expect($result)->toBeArray()
        ->and($result[0]->status)->toBe('db_error');
});
