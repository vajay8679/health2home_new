<?php

namespace App\Admin\Controllers;

use App\Models\ConsultationRequestStatus;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class ConsultationRequestStatusController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Consultation Request Statuses';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ConsultationRequestStatus());

        $grid->column('id', __('Id'));
        $grid->column('slug', __('Slug'));
        $grid->column('status_name', __('Status Name'));
        
        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });
        $grid->filter(function ($filter) {
        //Get All status
        $filter->like('slug', __('Slug'));
        $filter->like('status_name', __('Status name'));
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
        $show = new Show(ConsultationRequestStatus::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('slug', __('Slug'));
        $show->field('status_name', __('Status name'));
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
        $form = new Form(new ConsultationRequestStatus());

        $form->text('slug', __('Slug'))->rules('required|max:150');
        $form->text('status_name', __('Status Name'))->rules('required|max:150');

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
