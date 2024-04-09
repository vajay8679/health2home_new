<?php

namespace App\Admin\Controllers;

use App\Models\InsurancePlan;
use App\Models\ForumQuestion;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ForumQuestionController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Forum Questions';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ForumQuestion());

        $grid->column('id', __('Id'));
        $grid->column('customer_id', __('Customer id'));
        $grid->column('title', __('Title'));
        $grid->column('description', __('Description'));
        $grid->column('symptom_id', __('Symptom id'));
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
        $show = new Show(ForumQuestion::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('customer_id', __('Customer id'));
        $show->field('title', __('Title'));
        $show->field('description', __('Description'));
        $show->field('symptom_id', __('Symptom id'));
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
        $form = new Form(new ForumQuestion());

        // $form->number('customer_id', __('Customer id'));
        // $form->text('title', __('Title'));
        // $form->text('description', __('Description'));
        // $form->number('symptom_id', __('Symptom id'));

        $insurance = InsurancePlan::where('status', 1)->pluck('insurance_name', 'insurance_id');

        $form->select('customer_id', __('Customer id'))->options($insurance)->rules(function($form) {
            return 'required';
        });
        $form->text('title', __('Title'))->rules(function ($form) {
            return 'required';
        });

        $form->text('description', __('Description'))->rules(function($form){
            return 'required';
        });

        $form->number('symptom_id', __('Symptom id'))->rules(function($form) {
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
