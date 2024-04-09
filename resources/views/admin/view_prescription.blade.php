<div class="container" id="printableArea">
    <div class="col-md-12 row">
        <h1>{{ $vendor_name }}</h1>
        <h6>{{ $vendor_address }}</h6>
        <h6>Order ID : {{ $order_id }}</h6>
    </div>
    <hr>
    @if($prescription)
        <center>
            <img src="{{ env('IMG_URL').$prescription }}" />
        </center>
    @else
        <div class="col-lg-12">
            <h3>Items</h3>
            <table class="table table-hover">
                <thead>
                  <tr>
                    <th>S.No</th>
                    <th>Product</th>
                  </tr>
                </thead>
                <tbody>
                <?php $i=1; ?>
                @foreach($prescription_items as $value)
                  <tr>
                    <td>{{ $i }}</td>
                    <td>{{ $value->medicine_name }}</td>
                  </tr>
                  <?php $i++; ?>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
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