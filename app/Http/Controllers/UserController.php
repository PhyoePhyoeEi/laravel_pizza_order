<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Pizza;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {

        $pizza = Pizza::where('publish_status', 1)->paginate(9);
        $category = Category::get();
        $status = count($pizza) == 0 ? 0 : 1;

        return view('user.home')->with(['pizza' => $pizza, 'category' => $category, 'status' => $status]);
    }

    public function details($id)
    {
        $data = Pizza::where('pizza_id', $id)->first();
        Session::put('PIZZA_INFO', $data);
        return view('user.details')->with(['pizza' => $data]);
    }

    public function categorySearch($id)
    {
        $data = Pizza::where('category_id', $id)
            ->where('publish_status', 1)
            ->paginate(9);
        $category = Category::get();
        $status = count($data) == 0 ? 0 : 1;

        return view('user.home')->with(['pizza' => $data, 'category' => $category, 'status' => $status]);
    }

    public function searchItem(Request $request)
    {
        $data = Pizza::where('pizza_name', 'like', '%' . $request->searchData . '%')
            ->where('publish_status', 1)
            ->paginate(9);
        $category = Category::get();
        $status = count($data) == 0 ? 0 : 1;
        $data->appends($request->all());
        return view('user.home')->with(['pizza' => $data, 'category' => $category, 'status' => $status]);

    }

    public function searchPizzaItem(Request $request)
    {

        $min = $request->minPrice;
        $max = $request->maxPrice;

        $startDate = $request->startDate;
        $endDate = $request->endDate;

        $query = Pizza::select('*')
            ->where('publish_status', 1);

        if (!is_null($startDate) && is_null($endDate)) {
            $query = $query->whereDate('created_at', '>=', $startDate);

        } else if (!is_null($startDate) && is_null($endDate)) {
            $query = $query->whereDate('creatd_at', '<=', $endDate);

        } else if (!is_null($startDate) && !is_null($endDate)) {
            $query = $query->whereDate('created_at', '>=', $startDate)
                ->whereDate('creatd_at', '<=', $endDate);

        }

        if (!is_null($min) && is_null($max)) {
            $query = $query->where('price', '>=', $min);

        } else if (!is_null($max) && is_null($min)) {
            $query = $query->where('price', '<=', $max);

        } else if (!is_null($min) && !is_null($max)) {
            $query = $query->where('price', '>=', $min)
                ->where('price', '<=', $max);

        }

        $query = $query->paginate(9);
        $query->appends($request->all());
        $category = Category::get();
        $status = count($query) == 0 ? 0 : 1;
        return view('user.home')->with(['pizza' => $query, 'category' => $category, 'status' => $status]);

    }

    public function order()
    {
        $pizzaInfo = Session::get('PIZZA_INFO');
        return view('user.order')->with(['pizza' => $pizzaInfo]);
    }

    public function placeOrder(Request $request)
    {
        $pizzaInfo = Session::get('PIZZA_INFO');
        $userId = auth()->user()->id;
        $count = $request->pizzaCount;

        $validator = Validator::make($request->all(), [
            'pizzaCount' => 'required',
            'paymentType' => 'required',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $orderData = $this->requestOrderData($pizzaInfo, $userId, $request);

        for ($i = 0; $i < $count; $i++) {
            Order::create($orderData);
        }
        $waitingTime = $pizzaInfo['waiting_time'] * $count;
        return back()->with(['totalTime' => $waitingTime]);
    }

    private function requestOrderData($pizzaInfo, $userId, $request)
    {
        return [
            'customer_id' => $userId,
            'pizza_id' => $pizzaInfo['pizza_id'],
            'carrier_id' => 0,
            'payment_status' => $request->paymentType,
            'order_time' => Carbon::now(),
            'pizza_name' => $pizzaInfo['pizza_name'],
        ];
    }
}
