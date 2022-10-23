<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Pizza;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class PizzaController extends Controller
{
    // direct pizza page
    public function pizza()
    {
        if (Session::has('PIZZA_SEARCH')) {
            Session::forget('PIZZA_SEARCH');
        }

        $data = Pizza::paginate(7);
        if (count($data) == 0) {
            $emptyStatus = 0;
        } else {
            $emptyStatus = 1;
        }

        return view('admin.pizza.list')->with(['pizza' => $data, 'status' => $emptyStatus]);
    }

    // direct create pizza page
    public function createPizza()
    {
        $category = Category::get();
        return view('admin.pizza.create')->with(['category' => $category]);

    }

    // insert pizza
    public function insertPizza(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'image' => 'required',
            'price' => 'required',
            'publish' => 'required',
            'category' => 'required',
            'discount' => 'required',
            'buyOneGetOne' => 'required',
            'waitingTime' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $file = $request->file('image');
        $fileName = uniqid() . '_' . $file->getClientOriginalName();

        $file->move(public_path() . '/uploads/', $fileName); // save into public folder

        $data = $this->requestPizza($request, $fileName); // image saving into db

        Pizza::create($data);
        return redirect()->route('admin#pizza')->with(['createSuccess' => 'Pizza Created']);

    }

    public function deletePizza($id)
    {
        $data = Pizza::select('image')->where('pizza_id', $id)->first(); // select image
        $fileName = $data['image'];
        if (File::exists(public_path() . '/uploads/' . $fileName)) {
            File::delete(public_path() . '/uploads/' . $fileName); // delete image from project
        }

        Pizza::where('pizza_id', $id)->delete();
        return back()->with(['deleteSuccess' => 'Delete Success']);
    }

    // view detail
    public function pizzaInfo($id)
    {
        $data = Pizza::where('pizza_id', $id)->first();
        return view('admin.pizza.info')->with(['pizza' => $data]);
    }

    // edit pizza
    public function editPizza($id)
    {
        $category = Category::get();

        $data = Pizza::select('pizzas.*', 'categories.category_id', 'categories.category_name')
            ->join('categories', 'pizzas.category_id', '=', 'categories.category_id')
            ->where('pizza_id', $id)
            ->first();

        return view('admin.pizza.edit')->with(['pizza' => $data, 'category' => $category]);
    }

    // update pizza
    public function updatePizza($id, Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'price' => 'required',
            'publish' => 'required',
            'category' => 'required',
            'discount' => 'required',
            'buyOneGetOne' => 'required',
            'waitingTime' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $updatedData = $this->requestUpdateData($request);
        if (isset($updatedData['image'])) { // image delete and replace new

            // get old image name
            $data = Pizza::select('image')->where('pizza_id', $id)->first(); // select image
            $fileName = $data['image'];

            // delete old image
            if (File::exists(public_path() . '/uploads/' . $fileName)) {
                File::delete(public_path() . '/uploads/' . $fileName); // delete image from project
            }

            // get new image
            $file = $request->file('image');
            $fileName = uniqid() . '_' . $file->getClientOriginalName();
            $file->move(public_path() . '/uploads/', $fileName); // update into public path

            $updatedData['image'] = $fileName;

        }

        Pizza::where('pizza_id', $id)->update($updatedData);
        return redirect()->route('admin#pizza')->with(['updateSuccess' => 'Pizza Updated']);

    }

    // search pizza
    public function searchPizza(Request $request)
    {
        $searchKey = $request->table_search;
        $searchData = Pizza::orWhere('pizza_name', 'like', '%' . $searchKey . '%')
            ->orWhere('price', $searchKey)
            ->paginate(7);
        Session::put('PIZZA_SEARCH', $request->table_search);

        $searchData->appends($request->all);

        // $data = Pizza::paginate(7);

        if (count($searchData) == 0) {
            $emptyStatus = 0;
        } else {
            $emptyStatus = 1;
        }

        return view('admin.pizza.list')->with(['pizza' => $searchData, 'status' => $emptyStatus]);

    }

    // look category item
    public function categoryItem($id)
    {
        $data = Pizza::select('pizzas.*', 'categories.category_name as categoryName')
            ->join('categories', 'categories.category_id', 'pizzas.category_id')
            ->where('pizzas.category_id', $id)
            ->paginate(5);
        return view('admin.category.item')->with(['pizza' => $data]);
    }

    public function pizzaDownload()
    {
        if (Session::has('PIZZA_SEARCH')) {

            $pizza = Pizza::orWhere('pizza_name', 'like', '%' . Session::get('PIZZA_SEARCH') . '%')
                ->orWhere('price', Session::get('PIZZA_SEARCH'))
                ->get();

        } else {
            $pizza = Pizza::get();

        }

        $csvExporter = new \Laracsv\Export();

        $csvExporter->build($pizza, [
            'pizza_id' => 'ID',
            'pizza_name' => 'Pizza Name',
            'price' => 'Price',
            'publish_status' => 'Publish Date',
            'buy_one_get_one_status' => 'Buy One Get One',
            'created_at' => 'Created Date',
            'updated_at' => 'Updated Date',

        ]);

        $csvReader = $csvExporter->getReader();
        $csvReader->setOutputBOM(\League\Csv\Reader::BOM_UTF8);

        $filename = 'pizzaList.csv';

        return response((string) $csvReader)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

    }

    private function requestUpdateData($request)
    {

        $arr = [
            'pizza_name' => $request->name,
            'price' => $request->price,
            'publish_status' => $request->publish,
            'category_id' => $request->category,
            'discount_price' => $request->discount,
            'buy_one_get_one_status' => $request->buyOneGetOne,
            'waiting_time' => $request->waitingTime,
            'description' => $request->description,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        if (isset($request->image)) {
            $arr['image'] = $request->image;
        }

        return $arr;

    }

    private function requestPizza($request, $fileName)
    {
        return [
            'pizza_name' => $request->name,
            'image' => $fileName,
            'price' => $request->price,
            'publish_status' => $request->publish,
            'category_id' => $request->category,
            'discount_price' => $request->discount,
            'buy_one_get_one_status' => $request->buyOneGetOne,
            'waiting_time' => $request->waitingTime,
            'description' => $request->description,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
