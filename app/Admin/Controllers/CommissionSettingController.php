<?php

namespace App\Admin\Controllers;

use App\Models\CommissionSetting;
use App\Models\UserType;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CommissionSettingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Commission Settings';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CommissionSetting);

        $grid->column('id', __('Id'));
        $grid->column('commission_type', __('Commission Type'));
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
        $show = new Show(CommissionSetting::findOrFail($id));

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
        $form = new Form(new CommissionSetting);
        
        $form->text('commission_type', __('Commission Type'))->rules(function ($form) {
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
