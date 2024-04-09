<?php

namespace App\Admin\Controllers;

use App\Models\HospitalAppSetting;
use App\Models\UserType;
use App\Models\CommissionSetting;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class HospitalAppSettingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hospital App Setting';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new HospitalAppSetting());

        $grid->column('id', __('Id'));
        $grid->column('app_name', __('App Name'));
        $grid->column('app_logo', __('App Logo'))->image();
        $grid->column('default_currency', __('Default Currency'));
        $grid->column('currency_short_code', __('Currency Short Code'));
        $grid->column('app_version', __('App Version'));
        $grid->column('address', __('Address'));
        $grid->column('user_type', __('User Type'))->display(function($user_types){
            $user_types = UserType::where('id',$user_types)->value('user');
            return $user_types;
        });
        //$grid->column('created_at', __('Created at'));
        //$grid->column('updated_at', __('Updated at'));

        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->filter(function ($filter) {
            //Get All status

        $filter->like('app_name', __('App name'));
        $filter->like('app_logo', __('App logo'));

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
        $show = new Show(HospitalAppSetting::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('app_name', __('App name'));
        $show->field('app_logo', __('App logo'));
        $show->field('default_currency', __('Default currency'));
        $show->field('currency_short_code', __('Currency short code'));
        $show->field('lab_radius', __('Lab radius'));
        $show->field('opening_time', __('Opening time'));
        $show->field('closing_time', __('Closing time'));
        $show->field('delivery_charge_per_km', __('Delivery charge per km'));
        $show->field('description', __('Description'));
        $show->field('app_version', __('App version'));
        $show->field('address', __('Address'));
        $show->field('user_type', __('User type'));
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

        $form = new Form(new HospitalAppSetting());
        $user_types = UserType::pluck('user', 'id');
        $commission_types = CommissionSetting::pluck('commission_type','id');


        $form->text('app_name', __('App Name')) ->rules(function ($form) {
            return 'required|max:150';
        });
        $form->image('app_logo', __('App Logo'))->uniqueName()->rules('required');
        $form->text('default_currency', __('Default currency'))->rules('required');
        $form->text('currency_short_code', __('Currency Short Code'))->rules('required');
        $form->textarea('description', __('Description'))->rules('required');
        $form->text('app_version', __('App Version'))->rules('required');
        $form->textarea('address', __('Address'))->rules('required');
        $form->select('user_type', __('User Type'))->options($user_types)->rules(function ($form) {
            return 'required';
        });
        $form->select('commission_type', __('Commission Type'))->options($commission_types)->rules(function ($form) {
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
