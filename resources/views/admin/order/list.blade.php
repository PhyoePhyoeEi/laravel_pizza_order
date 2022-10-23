
@extends('admin.layout.app')
@section('content')

<div class="content-wrapper">

    <!-- Main content -->
    <section class="content">
      
      
      <div class="container-fluid">
        <div class="row mt-4">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">
                  <a href="{{ route('admin#addCategory') }}"><button class="btn btn-sm btn-outline-dark">Add Category</button></a>
                </h3>
                <span class="fs-5 ml-5">Total - {{ $order->total() }}</span>

                <div class="card-tools">
                  <form action="{{ route('admin#orderSearch') }}" method="get">
                    @csrf
                    <div class="input-group input-group-sm" style="width: 150px;">
                    <input type="text" name="searchData" class="form-control float-right" placeholder="Search">

                    <div class="input-group-append">
                      <button type="submit" class="btn btn-default">
                        <i class="fas fa-search"></i>
                      </button>
                    </div>
                  </div>
                  </form>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body table-responsive p-0">

                
                <table class="table table-hover text-nowrap text-center">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Customer Name</th>
                      <th>Pizzza Name</th>
                      <th>Pizza Count</th>
                      <th>Order Time</th>
                     
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($order as $item)
                      <tr>
                      <td>{{ $item->order_id }}</td>
                      <td>{{ $item->customer_name}}</td>
                      <td>{{ $item->pizza_name}}</td>
                      <td>{{ $item->count}}</td>
                      <td>{{ $item->order_time}}</td>

                      {{-- <td>
                      <a href="{{ route('admin#editCategory', $item->category_id) }}"><button class="btn btn-sm bg-dark text-white"><i class="fas fa-edit"></i></button></a>
                      <a href="{{ route('admin#deleteCategory', $item->category_id) }}"><button class="btn btn-sm bg-danger text-white"><i class="fas fa-trash-alt"></i></button></a>
                      </td> --}}
                    </tr>
                    @endforeach
                        
                    
                  </tbody>
                </table>

                <div class="mt-4 ms-3">
                  {{ $order->links() }}
                </div>
                
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
        </div>

      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>

    
@endsection