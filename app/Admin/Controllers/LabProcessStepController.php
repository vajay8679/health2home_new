<?php

namespace App\Admin\Controllers;

use App\Models\LabProcessStep;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class LabProcessStepController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Lab Process Steps';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LabProcessStep());

        $grid->column('id', __('Id'));
        $grid->column('icon', __('Icon'))->image();
        $grid->column('title', __('Title'));
        $grid->column('description', __('Description'));
        //$grid->column('created_at', __('Created at'));
        //$grid->column('updated_at', __('Updated at'));

        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableView();
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
        $show = new Show(LabProcessStep::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('icon', __('Icon'));
        $show->field('title', __('Title'));
        $show->field('description', __('Description'));
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
        $form = new Form(new LabProcessStep());

        $form->image('icon', __('Icon'))->uniqueName();
        $form->text('title', __('Title'))->rules('required');
        $form->textarea('description', __('Description'))->rules('required');

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
