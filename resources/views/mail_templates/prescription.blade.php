 
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
    font-size: 15px;
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
            <div style="float:left; width:100%;">
                <center>
                <h1>{{ env('APP_NAME') }}</h1>
                <p class="left text-black">Dr.{{ $data['doctor_name'] }} ( {{ $data['doctor_qualification'] }} )</p>
                <p class="left text-black">{{ $data['doctor_specialist'] }}</p>
                </center>
            </div>
        </div>

        <div class="row" style="padding:0 7% 0 7%">
            <hr style="margin-top:7%;padding:0 7% 0 7%">

            <div class="row" style="padding:0 7% 0 7%;">
                <div style="float:left; width:40%;">
                    <p class="left theme_clr">Mr/Mrs </p>
                    <p class="left theme_clr">Blood Group</p>
                    <p class="left theme_clr">Contact number</p>
                </div>
                <div style="float:left; width:60%;">
                    <p class="left">{{ $data['customer_name'] }} </p>
                    <p class="left">{{ $data['customer_blood_group'] }}</p>
                    <p class="left">{{ $data['customer_phone_number'] }}</p>
                </div>
            </div>

             <hr style="padding:0 7% 0 7%">
        </div>
           
        <div class="row" style="padding:0 7% 0 7%; margin-top:4%">
            <div style="float:left; width:40%;">
                <p class="ptheme_clr">Medicine Name</p>
            </div>
            <div style="float:left; width:15%;">
                <p class="left"><span>Morning</span></h3>
            </div>
            <div style="float:left; width:15%;">
                <p class="left"><span>Noon</span></h3>
            </div>
            <div style="float:left; width:15%;">
                <p class="left"><span>Evening</span></h3>
            </div>
            <div style="float:left; width:15%;">
                <p class="left"><span>Night</span></h3>
            </div>
            <div style="clear:both"></div>
        </div>
        <div class="row" style="padding:0 7% 0 7%; margin-top:4%">
            @php $i=1; @endphp
              @foreach($data['items'] as $value) 
            <div style="float:left; width:40%;">
                <p >{{ $value->medicine_name }}</p>
            </div>
            <div style="float:left; width:15%;">
                @if($value->morning == 1)
                <p class="left"><span>Yes</span></h3>
                @else
                <p class="left"><span>No</span></h3>
                @endif
            </div>
            <div style="float:left; width:15%;">
                @if($value->morning == 1)
                <p class="left"><span>Yes</span></h3>
                @else
                <p class="left"><span>No</span></h3>
                @endif
            </div>
            <div style="float:left; width:15%;">
                @if($value->morning == 1)
                <p class="left"><span>Yes</span></h3>
                @else
                <p class="left"><span>No</span></h3>
                @endif
            </div>
            <div style="float:left; width:15%;">
                @if($value->morning == 1)
                <p class="left"><span>Yes</span></h3>
                @else
                <p class="left"><span>No</span></h3>
                @endif
            </div>
            <div style="clear:both"></div>
            @endforeach
        </div>
        
        <div class="row" style="background:#00bcd4!important; padding:0 7% 0 7%;margin-top:7%">
            <div style="float:left; width:100%;">
                <h3 class="left text-black">Powered by {{ env('APP_NAME') }}</h3>
            </div>
            <div style="float:right; width:50%">
                <h3 class="text-black" style="text-align:right ">E-Prescription</h3>
            </div>
        </div>
</div>
</body>
