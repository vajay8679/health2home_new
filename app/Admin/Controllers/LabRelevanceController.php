<?php

namespace App\Admin\Controllers;

use App\Models\LabRelevance;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class LabRelevanceController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Lab Relevances';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LabRelevance());

        $grid->column('id', __('Id'));
        $grid->column('relevance_name', __('Relevance Name'));
        $grid->column('relevance_icon', __('Relevance Icon'))->image();
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

            $filter->like('relevance_name', __('Relevance name'));
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
        $show = new Show(LabRelevance::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('relevance_name', __('Relevance name'));
        $show->field('relevance_icon', __('Relevance icon'));
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
        $form = new Form(new LabRelevance());
        $statuses = Status::where('slug','general')->pluck('status_name','id');

        $form->text('relevance_name', __('Relevance Name'))->rules('required');
        $form->image('relevance_icon', __('Relevance Icon'))->uniqueName()->move('lab_relevances');
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
