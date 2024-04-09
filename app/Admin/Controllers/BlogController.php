<?php

namespace App\Admin\Controllers;

use App\Models\Blog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class BlogController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Blog';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Blog);

        $grid->column('id', __('Id'));
        $grid->column('title', __('title'));
        $grid->column('image', __('Image'))->image();
        
        $grid->disableExport();
        $grid->disablefilter();
        $grid->actions(function ($actions) {
            $actions->disableView();
            //$actions->disableEdit();
            //$actions->disableDelete();
        });

        return $grid;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Blog);
        
        $form->text('title', __('Title'))->rules(function ($form) {
            return 'required';
        });
        $form->textarea('description', __('Description'))->rows(10)->rules(function ($form) {
            return 'required';
        });
        $form->image('image', __('Image'))->uniqueName()->move('blog')->rules('required');
        $form->text('video', __('video'));
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
