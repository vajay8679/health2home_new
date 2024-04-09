<?php

namespace App\Admin\Controllers;

use App\Models\HospitalPatient;
use App\Models\Hospital;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\DB;

class HospitalPatientController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hospital Patients';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new HospitalPatient());
        
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('hospital_id', Hospital::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('hospital_id', __('Hospital'))->display(function($hospitals){
            $hospitals = Hospital::where('id',$hospitals)->value('hospital_name');
            return $hospitals;
        });
        $grid->column('patient_name', __('Patient Name'));
        $grid->column('phone_number', __('Phone Number'));
        $grid->column('Patient Histories')->display(function () {
            return "<a target='_blank' href='/admin/view_patient_history/".$this->patient_id."'><span class='label label-info'>View Patient Histories</span></a>";
        });
        if(Admin::user()->isAdministrator()){
            $grid->disableExport();
            $grid->actions(function ($actions) {
                $actions->disableView();
            });
        }else{
            $grid->disableExport();
            //$grid->disableCreation();
            $grid->actions(function ($actions) {
                $actions->disableView();
            });
        }

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
        $show = new Show(HospitalPatient::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('hospital_id', __('Hospital id'));
        $show->field('doctor_id', __('Doctor id'));
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
        $form = new Form(new HospitalPatient());
        $hospitals = Hospital::pluck('hospital_name','id');
        $hospital_id = Hospital::where('admin_user_id',Admin::user()->id)->value('id');

        if(!Admin::user()->isAdministrator()){
            $form->hidden('hospital_id')->value($hospital_id);
        }else{
            $form->select('hospital_id', __('Hospital'))->options($hospitals)->rules(function ($form) {
            return 'required';
        });
        }
        $form->text('patient_name', __('Patient Name'))->rules('required');
        $form->text('phone_number', __('Phone Number'))->rules('required');
        
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
