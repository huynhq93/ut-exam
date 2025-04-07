<?php

namespace App\Interfaces;

use App\APIResponse;

interface APIClientInterface
{
    public function callAPI(int $orderId): APIResponse;
} 