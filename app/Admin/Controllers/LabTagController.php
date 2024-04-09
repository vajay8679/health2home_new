<?php

namespace App\Admin\Controllers;

use App\Models\LabTag;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class LabTagController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Lab Tags';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LabTag());

        $grid->column('id', __('Id'));
        $grid->column('tag_name', __('Tag Name'));
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
        $statuses = Status::where('slug','general')->pluck('status_name','id');

        $filter->like('tag_name', __('Tag Name'));
        $filter->equal('status', __('Status'))->select($statuses);

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
        $show = new Show(LabTag::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('tag_name', __('Tag name'));
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
        $form = new Form(new LabTag());
        $statuses = Status::where('slug','general')->pluck('status_name','id');

        $form->text('tag_name', __('Tag Name'))->rules(function ($form) {
            return 'required|max:150';
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
