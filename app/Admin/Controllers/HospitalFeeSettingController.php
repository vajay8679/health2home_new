<?php

namespace App\Admin\Controllers;

use App\Models\HospitalFeeSetting;
use App\Models\Hospital;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class HospitalFeeSettingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hospital Fee Settings';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new HospitalFeeSetting);

        $grid->column('id', __('Id'));
        $grid->column('hospital_id', __('Hospital'))->display(function($hospitals){
            $hospitals = Hospital::where('id',$hospitals)->value('hospital_name');
            return $hospitals;
        });
        $grid->column('appointment_fee', __('Appointment Fee'));
        $grid->column('consultation_fee', __('Consultation Fee'));
        $grid->column('waiting_time', __('Waiting Time'));
        
        $grid->disableExport();
        //$grid->disableCreation();
        $grid->disableFilter();
        $grid->actions(function ($actions) {
            $actions->disableView();
            //$actions->disableEdit();
            $actions->disableDelete();
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
        $show = new Show(HospitalFeeSetting::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('application_name', __('Application name'));
        $show->field('app_logo', __('App Logo'));
        $show->field('email', __('Email'));
        $show->field('default_currency', __('Currency symbol'));
        $show->field('user_type', __('User Type'));

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
        $form = new Form(new HospitalFeeSetting);
        $hospitals = Hospital::pluck('hospital_name','id');
        
        $form->select('hospital_id', __('Hospital'))->options($hospitals)->rules(function ($form) {
            return 'required';
        });
        $form->text('consultation_fee', __('Consultation Fee'))->rules(function ($form) {
            return 'required';
        });
        $form->text('appointment_fee', __('Appointment Fee'))->rules(function ($form) {
            return 'required';
        });
        $form->number('waiting_time', __('Waiting time '))->rules(function ($form) {
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
