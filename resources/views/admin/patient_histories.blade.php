<div class="container">
    <div class="col-lg-8">
        <div class="table-responsive">          
          <table class="table">
            <tbody>
              <tr>
                <th>Patient Name</th>
                <td>{{ $patient_details->customer_name }}</td>
              </tr>
              <tr>
                <th>Gender</th>
                @if($patient_details->gender == 1)
                <td>Male</td>
                @elseif($patient_details->gender == 2)
                <td>Female</td>
                @else
                <td>---</td>
                @endif
              </tr>
              <tr>
                <th>Blood Group</th>
                @if($patient_details->blood_group)
                <td>{{ $patient_details->blood_group }}</td>
                @else
                <td>---</td>
                @endif
              </tr>
              <tr>
                <th>Pre Existing Disease</th>
                @if($patient_details->pre_existing_desease)
                <td>{{ $patient_details->pre_existing_desease }}</td>
                @else
                <td>---</td>
                @endif
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
                <th>Doctor Name</th>
                <th>Title</th>
                <th>Description</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
            <?php $i=1; ?>
            @foreach($history as $value)
              <tr>
                <td>{{ $i }}</td>
                <td>Dr.{{ $value->doctor_name.'-'.$value->hospital_name }}</td>
                <td>{{ $value->title }}</td>
                <td>{{ $value->description }}</td>
                <td>{{ $value->start_time }}</td>
              </tr>
              <?php $i++; ?>
            @endforeach
            </tbody>
        </table>
    </div>
</div>