<?php
namespace App;

class Order
{
    public $id;
    public $type;
    public $amount;
    public $flag;
    public $status;
    public $priority;
    public $data;

    public function __construct($id, $type, $amount, $flag)
    {
        $this->id = $id;
        $this->type = $type;
        $this->amount = $amount;
        $this->flag = $flag;
        $this->status = null;
        $this->priority = null;
        $this->data = null;
    }
} 