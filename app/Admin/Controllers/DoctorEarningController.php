<?php

namespace App\Admin\Controllers;

use App\Models\DoctorEarning;
use App\Models\Status;
use App\Models\Booking;
use App\Models\Doctor;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DoctorEarningController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Doctor Earnings';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DoctorEarning);
        
        $grid->model()->orderBy('id','desc');
        $grid->column('id', __('Id'));
        $grid->column('booking_id', __('Booking'));
        $grid->column('doctor_id', __('Doctor'))->display(function($doctor_id){
            return Doctor::where('id',$doctor_id)->value('doctor_name');
        });
        $grid->column('amount', __('Amount'));
        
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
        $show = new Show(DoctorEarning::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('booking_id', __('Booking id'));
        $show->field('doctor_id', __('Doctor id'));
        $show->field('amount', __('Amount'));
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
        $form = new Form(new DoctorEarning);
        $doctor = Doctor::pluck('doctor_name', 'id');
        $booking = Booking::pluck('id', 'id');

        $form->select('booking_id', __('Booking'))->options($booking)->rules(function ($form) {
            return 'required';
        });
        $form->select('doctor_id', __('Doctor'))->options($doctor)->rules(function ($form) {
            return 'required';
        });
        $form->decimal('amount', __('Amount'))->rules(function ($form) {
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
