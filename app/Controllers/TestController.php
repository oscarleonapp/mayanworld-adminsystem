<?php

namespace App\Controllers;

use App\Core\BaseController;

class TestController extends BaseController
{
    public function index()
    {
        $this->view('test/simple', ['message' => 'Hello from TestController!']);
    }
}
