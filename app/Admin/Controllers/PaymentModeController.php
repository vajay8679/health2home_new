<?php

namespace App\Admin\Controllers;

use App\Models\PaymentMode;
use App\Models\PaymentType;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PaymentModeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Payment Modes';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PaymentMode);

        $grid->column('id', __('Id'));
        $grid->column('payment_type_id', __('PaymentType '))->display(function($payment_types){
            $payment_types = PaymentType::where('id',$payment_types)->value('type_name');
            return $payment_types;
        });
        $grid->column('slug', __('Slug'));
        $grid->column('payment_name', __('Payment Name'));
        $grid->column('icon', __('Icon'))->image();
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('status_name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        });
        $grid->disableExport();
        $grid->disableColumnSelector();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });
        
        $grid->filter(function ($filter) {
            //Get All status
        $statuses = Status::where('slug','general')->pluck('status_name','id');

        $filter->equal('status', __('Status'))->select($statuses);
        $filter->like('payment_name', __('Payment Name'));
         });
        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new PaymentMode);
        $payment_types =PaymentType::pluck('type_name', 'id');
        
        $form->select('payment_type_id', __('Payment Type'))->options($payment_types)->rules(function ($form) {
            return 'required';
        });
        $form->text('slug', __('Slug'))->rules(function ($form) {
            return 'required';
        });
        $form->text('payment_name', __('Payment Name'))->rules(function ($form) {
            return 'required';
        });
        $form->image('icon', __('Icon'))->uniqueName()->move('payment_modes')->rules('required');
        $form->select('status', __('Status'))->options(Status::where('slug','general')->pluck('status_name','id'))->rules(function ($form) {
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


