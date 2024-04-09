<?php

namespace App\Admin\Controllers;

use App\Models\LabCollectivePeople;
use App\Models\Laboratory;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;
class LabCollectivePeopleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Collective Peoples';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LabCollectivePeople());
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('lab_id', Laboratory::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('name', __('Name'));
        $grid->column('phone_number', __('Phone Number'));
        $grid->column('email', __('Email'));
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

        $statuses = Status::where('slug','general')->pluck('status_name','id');

        $filter->like('name', __('Name'));
        $filter->like('phone_number', __('Phone number'));
        $filter->like('email', __('Email'));
        
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
        $show = new Show(LabCollectivePeople::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('name', __('Name'));
        $show->field('phone_number', __('Phone number'));
        $show->field('email', __('Email'));
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
        $form = new Form(new LabCollectivePeople());
        $statuses = Status::where('slug','general')->pluck('status_name','id');
        $labs = Laboratory::where('status',1)->pluck('lab_name','id');
        $lab_id = Laboratory::where('admin_user_id',Admin::user()->id)->value('id');
        
        if(!Admin::user()->isAdministrator()){
            $form->hidden('lab_id')->value($lab_id);
        }else{
            $form->select('lab_id', __('Lab Id'))->options($labs)->rules(function ($form) {
                return 'required';
            });
        }
        $form->text('name', __('Name'))->rules(function ($form) {
            return 'required|max:150';
        });
        $form->text('phone_number', __('Phone Number'))->rules(function ($form) {
            return 'numeric|required';
        });
        $form->email('email', __('Email'))->rules(function ($form) {
            return 'required|max:150';
        });
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
