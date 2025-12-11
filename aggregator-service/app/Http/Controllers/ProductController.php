<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AggregatorBaseController;
use Illuminate\Http\Request;

class ProductController extends AggregatorBaseController
{
    public function index(Request $request)
    {
        return $this->sendToService(
            $request,
            'get',
            env('PRODUCT_SERVICE_URL') . '/products'
        );
    }

    public function show(Request $request, $id)
    {
        return $this->sendToService(
            $request,
            'get',
            env('PRODUCT_SERVICE_URL') . "/products/$id"
        );
    }

    public function store(Request $request)
    {
        return $this->sendToService(
            $request,
            'post',
            env('PRODUCT_SERVICE_URL') . '/products',
            $request->all()
        );
    }

    public function update(Request $request, $id)
    {
        return $this->sendToService(
            $request,
            'put',
            env('PRODUCT_SERVICE_URL') . "/products/$id",
            $request->all()
        );
    }

    public function destroy(Request $request, $id)
    {
        return $this->sendToService(
            $request,
            'delete',
            env('PRODUCT_SERVICE_URL') . "/products/$id"
        );
    }
}
