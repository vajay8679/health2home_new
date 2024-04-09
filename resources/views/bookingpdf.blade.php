 
<head>

    <style>
        div.border { 
          border: 2px solid;
      }
      .center{
        text-align:center;
    }
    .column {
      float: left;
      width: 50%;
  }

  .right{
    float:right;text-align: right;
}

.padding{
    padding:0px 10px;
}
/* Clear floats after the columns */
.row:after {
  content: "";
  display: table;
  clear: both;
}
.font-p{
    font-size: 23px;
}
table, th, td {
  /*border: 1px solid black;*/
  border-collapse: collapse;
  font-size: 23px;
}
th, td {
  padding: 5px;
  text-align: left;    
}

.text-black{
    color:black;
}

.theme_clr{
    color:#00bcd4;
}
</style>

</head>
<body >
    <div class="row" style="width:70%; margin:auto!important;">
        <div class="row" style="background:#00bcd4!important; padding:0 7% 0 7%">
            <div style="float:left; width:90%;">
                <h1 class="left text-black">{{ $data[0]['header']->doctor_name }} {{ $data[0]['header']->doctor_qualification }}</h1>
                <h2 class="left text-black">{{ $data[0]['header']->doctor_specialist }}</h2>
                <h2 class="left text-black">Experience - {{ $data[0]['header']->doctor_experience }}years</h2>
            </div>
            <div style="float:right; width:10%">
                <img src="{{ env('APP_URL') }}/uploads/{{ $data[0]['header']->doctor_image }}"  alt="doctor_img" width="150" height="150">
            </div>
        </div>

        <div class="row" style="padding:0 7% 0 7%">
            <hr style="margin-top:7%;padding:0 7% 0 7%">

            <div style="float:left; width:50%; ">
                <h3 class="left" >Booking start time : {{ $data[0]['header']->booking_start_time }}</h3>
                <h3 class="left">Booking end time : {{ $data[0]['header']->booking_end_time }}</h3>
            </div>
            <div style="float:right; width:50%;">
                <h3 style="text-align: right">Prescription No.: {{ $data[0]['header']->prescription_id }} </h3>
            </div>
            <div style="clear:both"></div>
             <hr style="padding:0 7% 0 7%">
        </div>
           

         <div class="row" style="padding:0 7% 0 7%; margin-top:4%">
            @php $i=1; @endphp
              @foreach($data[0]['booking_items'] as $value) 
            <div style="float:left; width:20%;">
                <h3 class="left theme_clr">{{ $value->medicine_name }}  </h3>
            </div>
            <div style="float:left; width:80%;">
                <h3 class="left"><span>{{ $value->morning }} - {{ $value->afternoon }} - {{ $value->evening }} - {{ $value->night }}</span></h3>
            </div>
            <div style="clear:both"></div>
              @endforeach

        </div>

         <div class="row" style="padding:0 7% 0 7%; margin-top:7%">
            <div style="float:left; width:20%;">
                <h3 class="left theme_clr">Mr/Mrs </h3>
                <h3 class="left theme_clr">Address</h3>
                <h3 class="left theme_clr">Contact number</h3>
            </div>
            <div style="float:left; width:80%;">
                <h3 class="left">{{ $data[0]['header']->customer_name }} </h3>
                <h3 class="left">{{ $data[0]['header']->customer_address }}</h3>
                <h3 class="left">{{ $data[0]['header']->customer_phone_number }}</h3>
            </div>
        </div>
        <div class="row" style="background:#00bcd4!important; padding:0 7% 0 7%;margin-top:7%">
            <div style="float:left; width:50%;">
                <h3 class="left text-black">Contact number : {{ $data[0]['header']->doctor_phone_number }}</h3>
            </div>
            <div style="float:right; width:50%">
                <h3 class="text-black" style="text-align:right ">Email Id: {{ $data[0]['header']->doctor_email }}</h3>
            </div>
        </div>
</div>
</body>
