<?php
//File Order.php
namespace App;

class Order
{
    public $id;
    public $type;
    public $amount;
    public $flag;
    public $status;
    public $priority;

    public function __construct($id, $type, $amount, $flag)
    {
        $this->id = $id;
        $this->type = $type;
        $this->amount = $amount;
        $this->flag = $flag;
        $this->status = 'new';
        $this->priority = 'low';
    }
}

//File APIResponse.php
namespace App;

class APIResponse
{
    public $status;
    public $data;

    public function __construct($status, Order $data)
    {
        $this->status = $status;
        $this->data = $data;
    }
}

//File APIException.php
namespace App;

class APIException extends \Exception {}

//File DatabaseException.php
namespace App;

class DatabaseException extends \Exception {}

//File DatabaseService.php
namespace App;

interface DatabaseService
{
    public function getOrdersByUser($userId): array;
    public function updateOrderStatus($orderId, $status, $priority): bool;
}

//File APIClient.php
namespace App;

interface APIClient
{
    public function callAPI($orderId): APIResponse;
}

//File OrderProcessingService.php
namespace App;

class OrderProcessingService
{
    private $dbService;
    private $apiClient;

    public function __construct(DatabaseService $dbService, APIClient $apiClient)
    {
        $this->dbService = $dbService;
        $this->apiClient = $apiClient;
    }

    public function processOrders(int $userId)
    {
        try {
            $orders = $this->dbService->getOrdersByUser($userId);

            foreach ($orders as $order) {
                switch ($order->type) {
                    case 'A':
                        $csvFile = 'orders_type_A_' . $userId . '_' . time() . '.csv';
                        $fileHandle = fopen($csvFile, 'w');
                        if ($fileHandle !== false) {
                            fputcsv($fileHandle, ['ID', 'Type', 'Amount', 'Flag', 'Status', 'Priority']);

                            fputcsv($fileHandle, [
                                $order->id,
                                $order->type,
                                $order->amount,
                                $order->flag ? 'true' : 'false',
                                $order->status,
                                $order->priority
                            ]);

                            if ($order->amount > 150) {
                                fputcsv($fileHandle, ['', '', '', '', 'Note', 'High value order']);
                            }

                            fclose($fileHandle);
                            $order->status = 'exported';
                        } else {
                            $order->status = 'export_failed';
                        }
                        break;

                    case 'B':
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
                        break;

                    case 'C':
                        if ($order->flag) {
                            $order->status = 'completed';
                        } else {
                            $order->status = 'in_progress';
                        }
                        break;

                    default:
                        $order->status = 'unknown_type';
                        break;
                }

                if ($order->amount > 200) {
                    $order->priority = 'high';
                } else {
                    $order->priority = 'low';
                }

                try {
                    $this->dbService->updateOrderStatus($order->id, $order->status, $order->priority);
                } catch (DatabaseException $e) {
                    $order->status = 'db_error';
                }
            }
            return $orders;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
