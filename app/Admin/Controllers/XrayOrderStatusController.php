<?php

namespace App\Admin\Controllers;

use App\Models\XrayOrderStatus;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class XrayOrderStatusController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'XrayOrderStatus';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new XrayOrderStatus());

        $grid->column('id', __('Id'));
        $grid->column('slug', __('Slug'));
        $grid->column('status', __('Status'));
        $grid->column('status_for_customer', __('Status For Customer'));
        $grid->column('status_for_laboratories', __('Status For Laboratories'));
        

        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->filter(function ($filter) {
        //Get All status
        $filter->like('slug', __('Slug'));
        $filter->like('status', __('Status'));
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
        $show = new Show(XrayOrderStatus::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('slug', __('Slug'));
        $show->field('status', __('Status'));
        $show->field('status_for_customer', __('Status for customer'));
        $show->field('status_for_laboratories', __('Status for laboratories'));
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
        $form = new Form(new XrayOrderStatus());

        $form->text('slug', __('Slug'))->rules('required|max:150');
        $form->text('status', __('Status'))->rules('required|max:150');
        $form->text('status_for_customer', __('Status For Customer'))->rules('required|max:150');
        $form->text('status_for_laboratories', __('Status For Laboratories'))->rules('required|max:150');

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
