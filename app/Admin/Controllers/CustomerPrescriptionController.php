<?php

namespace App\Admin\Controllers;

use App\Models\CustomerPrescription;
use App\Models\Customer;
use App\Models\Booking;
use App\Models\Doctor;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CustomerPrescriptionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Customer Prescriptions';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CustomerPrescription);

        $grid->column('id', __('Id'));
        $grid->column('doctor_id', __('Doctor'))->display(function($doctor_id){
            return Doctor::where('id',$doctor_id)->value('doctor_name');
        });
        $grid->column('patient_id', __('Patient'))->display(function($patient_id){
            return Customer::where('id',$patient_id)->value('customer_name');
        });
        $grid->column('booking_id', __('Booking id'))->display(function($booking_id){
            return Booking::where('id',$booking_id)->value('id');
        });
        $grid->column('date', __('Date'));
        
        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });
        $grid->filter(function ($filter) {
            //Get All status
        $patients = Customer::pluck('customer_name', 'id');
        $bookings = Booking::pluck('id', 'id');
        $doctors = Doctor::pluck('doctor_name', 'id');
            $filter->like('booking_id', 'Booking')->select($bookings);
            $filter->like('doctor_id', 'Doctor')->select($doctors);
            $filter->like('patient_id', 'Patient')->select($patients);
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
        $show = new Show(CustomerPrescription::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('doctor_id', __('Doctor id'));
        $show->field('patient_id', __('Patient id'));
        $show->field('date', __('Date'));
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
        $form = new Form(new CustomerPrescription);
        
        $patients = Customer::pluck('customer_name', 'id');
        $bookings = Booking::pluck('id', 'id');
        $doctors = Doctor::pluck('doctor_name', 'id');

        $form->select('doctor_id', __('Doctor'))->options($doctors)->rules(function ($form) {
            return 'required';
        });
        $form->select('patient_id', __('Patient'))->options($patients)->rules(function ($form) {
            return 'required';
        });
        $form->select('booking_id', __('Booking'))->options($bookings)->rules(function ($form) {
            return 'required';
        });
        $form->date('date', __('Date'))->default(date('D-m-y'));
        
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
