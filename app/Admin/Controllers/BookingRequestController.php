<?php

namespace App\Admin\Controllers;

use App\Models\BookingRequest;
use App\Models\Doctor;
use App\Models\Customer;
use App\Models\BookingRequestStatus;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
class BookingRequestController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Booking Requests';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BookingRequest());
        
        if(!Admin::user()->isAdministrator()){
            $grid->model()
                ->join('doctors', 'doctors.id', '=', 'booking_requests.doctor_id')
                ->join('hospitals', 'hospitals.id', '=', 'doctors.hospital_id')
                ->select('booking_requests.*')
                ->where('hospitals.admin_user_id',Admin::user()->id)
                ->orderBy('booking_requests.id','desc');
        }
        
        $grid->column('id', __('Id'));
        $grid->column('patient_id', __('Patient'))->display(function($customers){
            $customers = Customer::where('id',$customers)->value('customer_name');
            return $customers;
        });
        $grid->column('doctor_id', __('Doctor'))->display(function($doctors){
            $doctors = Doctor::where('id',$doctors)->value('doctor_name');
            return $doctors;
        });
        //$grid->column('start_time', __('Start Time'));
        $grid->column('title', __('Title'));
        $grid->column('description', __('Description'));
        $grid->column('start_time', __('Appointment Time'));
        $grid->column('total_amount', __('Total Amount'));
        $grid->column('rating', __('Rating'))->display(function($rating){
            if($rating){
                return $rating;
            }else{
                return '---';
            }
            
        });
        $grid->column('comments', __('Comments'))->display(function($comments){
            if($comments){
                return $comments;
            }else{
                return '---';
            }
            
        });
        $grid->column('status', __('Status'))->display(function($booking_request_statuses){
            $booking_request_statuses = BookingRequestStatus::where('id',$booking_request_statuses)->value('status_name');
            return $booking_request_statuses;
        });
        $grid->column('Patient Histories')->display(function () {
            return "<a target='_blank' href='/admin/view_patient_history/".$this->patient_id."'><span class='label label-info'>View Patient Histories</span></a>";
        });

        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });
        $grid->filter(function ($filter) {
            //Get All status
            $doctors = Doctor::pluck('doctor_name','id');
            $customers = Customer::pluck('customer_name','id');
            $booking_request_statuses = BookingRequestStatus::pluck('status_name','id');
            
            $filter->equal('doctor_id', 'Doctor')->select($doctors);
            $filter->equal('patient_id', 'Patient')->select($customers);
            $filter->equal('status', 'Status')->select($booking_request_statuses);
        });

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(BookingRequest::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('patient_id', __('Patient id'));
        $show->field('doctor_id', __('Doctor id'));
        $show->field('start_time', __('Start time'));
        $show->field('title', __('Title'));
        $show->field('description', __('Description'));
        $show->field('total_amount', __('Total amount'));
        $show->field('status', __('Status'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new BookingRequest());
        $doctors = Doctor::pluck('doctor_name','id');
        $booking_request_statuses = BookingRequestStatus::pluck('status_name','id');
        
        /*
        $form->text('patient_id', __('Patient'));
        $form->select('doctor_id', __('Doctor'))->options($doctors)->rules(function ($form) {
            return 'required';
        });
        $form->text('title', __('Title'))->rules(function ($form) {
            return 'required|max:150';
        });
        $form->textarea('description', __('Description'))->rules(function ($form) {
            return 'required';
        });
        $form->decimal('total_amount', __('Total Amount'))->rules(function ($form) {
            return 'required|max:150';
        });
        */
        $form->datetime('start_time', __('Start Time'))->default(date('Y-m-d H:i:s'));
        $form->select('status', __('Status'))->options($booking_request_statuses)->rules(function ($form) {
            return 'required';
        });

        $form->tools(function (Form\Tools $tools) {
            $tools->disableDelete(); 
            $tools->disableView();
        });
        $form->footer(function ($footer) {
            $footer->disableViewCheck();
            $footer->disableEditingCheck();
            $footer->disableCreatingCheck();
        });


        return $form;
    }
}
