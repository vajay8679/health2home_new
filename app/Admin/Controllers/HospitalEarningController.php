<?php

namespace App\Admin\Controllers;

use App\Models\HospitalEarning;
use App\Models\Status;
use App\Models\Vendor;
use App\Models\Hospital;
use App\Models\Doctor;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;

class HospitalEarningController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hospital Earnings';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new HospitalEarning);
        
        $grid->model()->orderBy('id','desc');
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('hospital_id', Hospital::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('hospital_id', __('Hospital'))->display(function($hospital_id){
            return Hospital::where('id',$hospital_id)->value('hospital_name');
        });
        $grid->column('type', __('Type'))->display(function($type){
            if ($type == 1) {
                return "<span class='label label-success'>Consultation</span>";
            }if ($type == 2) {
                return "<span class='label label-warning'>Appointment</span>";
            } else {
                return "<span class='label label-info'>Pharmacy</span>";
            }
        });
        $grid->column('ref_id', __('Reference'));
        $grid->column('source_id', __('Source'))->display(function($source_id){
            if ($source_id == 1) {
                $source_name = Doctor::where('id',$source_id)->value('doctor_name');
                return "<span class='label label-success'>$source_name</span>";
            }if ($source_id == 2) {
                $source_name = Doctor::where('id',$source_id)->value('doctor_name');
                return "<span class='label label-success'>$source_name</span>";
            }else {
                $source_name = Vendor::where('id',$source_id)->value('store_name');
                return "<span class='label label-warning'>$source_name</span>";
            }
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
           if(Admin::user()->isAdministrator()){
            //Get All status
            $hospitals = Hospital::pluck('hospital_name','id');
            
            $filter->equal('hospital_id', __('Hospital'))->select($hospitals);
        }
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
        $show = new Show(HospitalEarning::findOrFail($id));

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
        $form = new Form(new HospitalEarning);
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
