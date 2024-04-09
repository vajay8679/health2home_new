<div class="container" id="printableArea">
    <div class="col-md-12 row">
        <h1>{{ $vendor_name }}</h1>
        <h6>{{ $vendor_address }}</h6>
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
                <th>Phone Number</th>
                <td>{{$phone_number}}</td>
              </tr>
              <tr>
                <th>Address</th>
                <td>{{$address}}</td>
              </tr>
              <tr>
                <th>Vendor Name</th>
                <td>{{$vendor_name}}</td>
              </tr>
              <tr>
                <th>Vendor Phone Number</th>
                <td>{{$vendor_phone_number}}</td>
              </tr>
              <tr>
                <th>Delivered By</th>
                <td>{{$delivered_by}}</td>
              </tr>
            </tbody>
          </table>
        </div>
    </div>
    <div class="col-md-6">
        <div class="table-responsive">          
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
                <th>Delivery Charge</th>
                <td>{{$delivery_charge}}</td>
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
    </div>
    <div class="col-lg-12">
        <h3>Items</h3>
        <table class="table table-hover">
            <thead>
              <tr>
                <th>S.No</th>
                <th>Product</th>
                <th>Qty</th>
              </tr>
            </thead>
            <tbody>
            <?php $i=1; ?>
            @foreach($order_items as $value)
              <tr>
                <td>{{ $i }}</td>
                <td>{{ $value->product_name }}</td>
                <td>{{ $value->qty }}</td>
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