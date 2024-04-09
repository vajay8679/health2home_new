<?php

namespace App\Admin\Controllers;

use App\Models\DoctorCommission;
use App\Models\UserType;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DoctorCommissionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Doctor Commissions';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DoctorCommission());

        $grid->column('id', __('Id'));
        $grid->column('booking_id', __('Booking'));
        $grid->column('role', __('Role'));
        $grid->column('user_id', __('User'))->display(function($user_types){
            $user_types = UserType::where('id',$user_types)->value('user');
            return $user_types;
        });
        $grid->column('commission_amount', __('Commission Amount'));
        $grid->column('total_amount', __('Total Amount'));
        

        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->filter(function ($filter) {
            //Get All status
           $user_types = userType::pluck('user','id');
            
            $filter->equal('user_type', 'User Type');
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
        $show = new Show(DoctorCommission::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('booking_id', __('Booking id'));
        $show->field('role', __('Role'));
        $show->field('user_id', __('User id'));
        $show->field('commission_amount', __('Commission amount'));
        $show->field('total_amount', __('Total amount'));
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
        $form = new Form(new DoctorCommission());
         $user_types = userType::pluck('user','id');

        $form->text('booking_id', __('Booking'))->rules('required|max:150');
        $form->text('role', __('Role'))->rules('required|max:150');
        $form->select('user_id', __('User'))->options($user_types)->rules(function ($form) {
            return 'required';
        });
        $form->decimal('commission_amount', __('Commission Amount'))->rules('required|max:150');
        $form->decimal('total_amount', __('Total Amount'))->rules('required|max:150');

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
