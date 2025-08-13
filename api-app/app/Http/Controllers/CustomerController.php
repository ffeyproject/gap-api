<?php

namespace App\Http\Controllers;

use App\MstCustomer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = MstCustomer::all();

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $customer = new MstCustomer();
        $customer->code = $request->input('code');
        $customer->name = $request->input('name');
        $customer->address = $request->input('address');
        $customer->phone = $request->input('phone');
        $customer->email = $request->input('email');
        $customer->save();

        return response()->json($customer);
    }

    public function show($id)
    {
        $customer = MstCustomer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        return response()->json($customer);
    }

    public function update(Request $request, $id)
    {
        $customer = MstCustomer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $customer->code = $request->input('code');
        $customer->name = $request->input('name');
        $customer->address = $request->input('address');
        $customer->phone = $request->input('phone');
        $customer->email = $request->input('email');
        $customer->save();

        return response()->json($customer);
    }

    public function destroy($id)
    {
        $customer = MstCustomer::find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $customer->delete();

        return response()->json(['message' => 'Customer deleted successfully']);
    }
}