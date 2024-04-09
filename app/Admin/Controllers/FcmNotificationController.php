<?php

namespace App\Admin\Controllers;

use App\Models\FcmNotification;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class FcmNotificationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Fcm Notification';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new FcmNotification());

        $grid->column('id', __('Id'));
        $grid->column('slug', __('Slug'));
        $grid->column('customer_title', __('Customer Title'));
        $grid->column('customer_description', __('Customer Description'));
        
        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableView();
            //$actions->disableEdit();
            $actions->disableDelete();
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
        $show = new Show(FcmNotification::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('slug', __('Slug'));
        $show->field('customer_title', __('Customer title'));
        $show->field('customer_description', __('Customer description'));
        $show->field('vendor_title', __('Vendor title'));
        $show->field('vendor_description', __('Vendor description'));
        $show->field('partner_title', __('Partner title'));
        $show->field('partner_description', __('Partner description'));
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
        $form = new Form(new FcmNotification());

        $form->text('slug', __('Slug'))->rules('required|max:150');
        $form->text('customer_title', __('Customer Title'));
        $form->textarea('customer_description', __('Customer Description'));
        $form->text('vendor_title', __('Vendor Title'));
        $form->textarea('vendor_description', __('Vendor Description'));
        $form->text('partner_title', __('Partner Title'));
        $form->textarea('partner_description', __('Partner Description'));
        $form->text('doctor_title', __('Doctor Title'));
        $form->textarea('doctor_description', __('Doctor Description'));

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
