<?php

namespace App\Admin\Controllers;

use App\Models\CustomerAppSetting;
use App\Models\UserType;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CustomerAppSettingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Customer App Settings';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CustomerAppSetting);

        $grid->column('id', __('Id'));
        $grid->column('app_name', __('Application Name'));
        $grid->column('app_logo', __('App Logo'))->image();
        $grid->column('email', __('Email'));
        $grid->column('default_currency', __('Default Currency'));
        $grid->column('doctor_searching_radius', __('Doctor Searching Radius'));
        $grid->column('instant_consultation_duration', __('Instant Consultation Duration'));
        $grid->column('vendor_radius', __('Vendor Radius'));
        $grid->column('pharm_delivery_charge', __('Pharmacy Delivery Charge'));
        $grid->column('user_type', __('User Type'))->display(function($user_types){
            $user_types = UserType::where('id',$user_types)->value('user');
            return $user_types;
        });
        $grid->disableExport();
        //$grid->disableCreation();
        $grid->disableFilter();
        $grid->actions(function ($actions) {
            $actions->disableView();
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
        $show = new Show(CustomerAppSetting::findOrFail($id));

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
        $form = new Form(new CustomerAppSetting);
        $user_types = UserType::pluck('user','id');

        $form->text('app_name', __('Application Name'))->rules(function ($form) {
            return 'required|max:100';
        });
        $form->image('app_logo', __('App Logo'))->rules('required')->rules('required')->uniqueName();
        $form->email('email', __('Email'))->rules('required');
        $form->textarea('address', __('Address'))->rules(function ($form) {
            return 'required';
        });
        $form->text('default_currency', __('Currency Symbol'))->rules(function ($form) {
            return 'required';
        });
        $form->text('currency_short_code', __('Currenct Short Code'))->rules(function ($form) {
            return 'required';
        });
        
        $form->decimal('vendor_radius', __('Vendor Radius'))->rules(function ($form) {
            return 'required';
        });
        $form->decimal('doctor_searching_radius', __('Doctor Searching Radius'))->rules(function ($form) {
            return 'required';
        });
        $form->decimal('pharm_delivery_charge', __('Pharmacy Delivery Charge'))->rules(function ($form) {
            return 'required';
        });
        $form->text('razorpay_key', __('Razorpay Key'))->rules(function ($form) {
            return 'required';
        });
      $form->text('paypal_key', __('Paypal Key'))->rules(function ($form) {
            return 'required';
        });
        $form->select('user_type', __('User Type'))->options($user_types)->rules(function ($form) {
            return 'required';
        });
        $form->number('instant_consultation_duration', __('Instant Consultation Duration'))->rules(function ($form) {
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
