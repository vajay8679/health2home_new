<?php

namespace App\Admin\Controllers;

use App\Models\DoctorBookingSetting;
use App\Models\Doctor;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DoctorBookingSettingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Doctor Booking Settings';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DoctorBookingSetting());
        
        $grid->model()->orderBy('id','desc');
        $grid->column('id', __('Id'));
        $grid->column('doctor_id', __('Doctor'))->display(function($doctors){
            $doctors = Doctor::where('id',$doctors)->value('doctor_name');
            return "$doctors";
        });
        $grid->column('online_booking_status', __('Online Booking Status'))->display(function($status){
            if ($status == 1) {
                return "<span class='label label-success'>Yes</span>";
            } else {
                return "<span class='label label-danger'>No</span>";
            }
        });
        $grid->column('direct_appointment_status', __('Direct Appointment Status'))->display(function($status){
            if ($status == 1) {
                return "<span class='label label-success'>Yes</span>";
            } else {
                return "<span class='label label-danger'>No</span>";
            }
        });
        
        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });
        
        $grid->filter(function ($filter) {
            //Get All status
            $doctors = Doctor::pluck('doctor_name', 'id');
        
            $filter->equal('doctor_id', 'Doctor')->select($doctors);
           
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
        $show = new Show(DoctorBookingSetting::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('doctor_id', __('Doctor id'));
        $show->field('instant_booking_status', __('Instant booking status'));
        $show->field('appointment_status', __('Appointment status'));
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
        $form = new Form(new DoctorBookingSetting());
        $doctors = Doctor::pluck('doctor_name', 'id');

        $form->select('doctor_id', __('Doctor'))->options($doctors)->rules(function ($form) {
            return 'required';
        });
        $form->text('online_booking_fee', __('Online Booking Fee'))->rules(function ($form) {
            return 'required';
        });
        $form->text('online_booking_time', __('Online Booking Time'))->rules(function ($form) {
            return 'required';
        })->help('Enter your conversation time in minutes like 15, 20, 30...');
        
        $form->text('direct_appointment_fee', __('Direct Appointment Fee'))->rules(function ($form) {
            return 'required';
        });
        $form->text('direct_appointment_time', __('Direct Appointment Time'))->rules(function ($form) {
            return 'required';
        })->help('Enter your appointment time in minutes like 15, 20, 30...');
        
        $form->select('online_booking_status', __('Instant Booking Status'))->options([1 => 'Yes', 0 => 'No'])->required();
        $form->select('direct_appointment_status', __('Appointment Status'))->options([1 => 'Yes', 0 => 'No'])->required();
        
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
