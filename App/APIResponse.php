<?php

namespace App;

class APIResponse
{
    public $status;
    public $data;

    public function __construct($status, $data)
    {
        $this->status = $status;
        $this->data = $data;
    }
}
