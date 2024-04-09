<div class="container" id="printableArea">
      <div class="row">
        <div class="col-md-6">
            <span class="pull-left">
                <h2>INVOICE</h2>
            </span>
        </div>
        <div class="col-md-6"><span class="pull-right">
            <h2>{{ $lab_name }}</h2>
            <h6>{{ $lab_address }}</h6>
        </span></div>
      </div>
    <hr>
    <div class="col-md-6">
        <div class="table-responsive">          
          <table class="table">
            <tbody>
              <tr>
                <th>Order Id</th>
                <td>{{$order_id}}</td>
              </tr>
              <tr>
                <th>Customer Name</th>
                <td>{{$customer_name}}</td>
              </tr>
              <tr>
                <th>Customer Phone Number</th>
                <td>{{$customer_phone_number}}</td>
              </tr>
              <tr>
                <th>Customer Address</th>
                <td>{{$address}}</td>
              </tr>
              <tr>
                <th>Laboratory Name</th>
                <td>{{$lab_name}}</td>
              </tr>
              <tr>
                <th>Laboratory Phone Number</th>
                <td>{{$lab_phone_number}}</td>
              </tr>
              <tr>
                <th>Collective Person</th>
                <td>{{$collective_person}}</td>
              </tr>
            </tbody>
          </table>
        </div>
    </div>
    <div class="col-md-6">
        <table class="table">
            <tbody>
              <tr>
                <th>Payment Mode</th>
                <td>{{$payment_mode}}</td>
              </tr>
              <tr>
                <th>Sub Total</th>
                <td>{{$sub_total}}</td>
              </tr>
              <tr>
                <th>Discount</th>
                <td>{{$discount}}</td>
              </tr>
              <tr>
                <th>Tax</th>
                <td>{{$tax}}</td>
              </tr>
              <tr>
                <th>Total</th>
                <td>{{$total}}</td>
              </tr>
              <tr>
                <th>Status</th>
                <td>{{$status}}</td>
              </tr>
            </tbody>
          </table>
    </div>
    <div class="col-lg-12">
        <h3>Items</h3>
        <table class="table table-hover">
            <thead>
              <tr>
                <th>S.No</th>
                <th>Package</th>
                <th>Price</th>
              </tr>
            </thead>
            <tbody>
            <?php $i=1; ?>
            @foreach($order_items as $value)
              <tr>
                <td>{{ $i }}</td>
                <td>{{ $value->package_name }}</td>
                <td>{{ $value->price }}</td>
              </tr>
              <?php $i++; ?>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
    function printDiv(divName) {
         var printContents = document.getElementById(divName).innerHTML;
         var originalContents = document.body.innerHTML;
    
         document.body.innerHTML = printContents;
    
         window.print();
    
         document.body.innerHTML = originalContents;
    }
</script>
<div class="col-md-12">
    <span class="pull-right">
        <button class="btn btn-default" onclick="printDiv('printableArea')" >Print</button>
    </span>
</div>
