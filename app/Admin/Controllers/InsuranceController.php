<?php

namespace App\Admin\Controllers;

use App\Models\Insurance;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class InsuranceController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Insurances';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Insurance());

        $grid->column('id', __('Id'));
        $grid->column('insurance_name', __('Insurance Name'));
        $grid->column('insurance_logo', __('Insurance Logo'))->image();
        $grid->column('insurance_link', __('Insurance Link'));
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('status_name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
            });
        
        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->filter(function ($filter) {
            //Get All status
        $filter->like('insurance_name', __('insurance Name'));
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
        $show = new Show(Insurance::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('insurance_name', __('Insurance name'));
        $show->field('insurance_logo', __('Insurance logo'));
        $show->field('insurance_link', __('Insurance link'));
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
        $form = new Form(new Insurance());
        $statuses = Status::where('slug','general')->pluck('status_name','id');

        $form->text('insurance_name', __('Insurance Name'))->rules(function ($form) {
            return 'required';
        });
        $form->image('insurance_logo', __('Insurance Logo'))->uniqueName()->move('Hospitals')->rules('required');
        $form->text('insurance_link', __('Insurance Link'))->rules(function ($form) {
            return 'required';
        });
        $form->select('status', __('Status'))->options($statuses)->default(1)->rules(function ($form) {
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
