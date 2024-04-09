<?php

namespace App\Admin\Controllers;

use App\Models\AppModule;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class AppModuleController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'App Modules';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new AppModule());

        $grid->column('id', __('Id'));
        $grid->column('module_name', __('Module Name'));
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('status_name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
            });
       
        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });

        $grid->filter(function ($filter) {
            //Get All status
            $statuses = Status::where('slug','general')->pluck('status_name','id');

            $filter->like('module_name', __('Module Name'));
            $filter->equal('status', 'Status')->select($statuses);
        
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
        $show = new Show(AppModule::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('module_name', __('Module name'));
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
        $form = new Form(new AppModule());
        $statuses = Status::where('slug','general')->pluck('status_name','id');

        $form->text('module_name', __('Module Name'))->rules(function ($form) {
            return 'required|max:100';
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
