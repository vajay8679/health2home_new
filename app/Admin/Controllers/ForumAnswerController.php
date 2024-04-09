<?php

namespace App\Admin\Controllers;

use App\Models\ForumAnswer;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ForumAnswerController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Forum Answers';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ForumAnswer());

        $grid->column('id', __('Id'));
        $grid->column('forum_id', __('Forum id'));
        $grid->column('doctor_id', __('Doctor id'));
        $grid->column('answer', __('Answer'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->column('created_at')->hide();
        $grid->column('updated_at')->hide();

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
        $show = new Show(ForumAnswer::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('forum_id', __('Forum id'));
        $show->field('doctor_id', __('Doctor id'));
        $show->field('answer', __('Answer'));
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
        $form = new Form(new ForumAnswer());

        // $form->number('forum_id', __('Forum id'));
        // $form->number('doctor_id', __('Doctor id'));
        // $form->text('answer', __('Answer'));

        $form->number('forum_id', __('Forum id'))->rules(function($form) {
            return 'required';
        });
        $form->number('doctor_id', __('Doctor id'))->rules(function ($form) {
            return 'required';
        });

        $form->text('answer', __('Answer'))->rules(function($form){
            return 'required';
        });

        $form->footer(function ($footer) {

        
            // disable `View` checkbox
            $footer->disableViewCheck();
        
            // disable `Continue editing` checkbox
            $footer->disableEditingCheck();
        
            // disable `Continue Creating` checkbox
            $footer->disableCreatingCheck();
        
        });

        return $form;
    }
}
