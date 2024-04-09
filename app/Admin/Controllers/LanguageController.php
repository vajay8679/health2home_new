<?php

namespace App\Admin\Controllers;

use App\Models\Language;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class LanguageController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Language';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Language);

        $grid->column('id', __('Id'));
        $grid->column('language', __('Language'));
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
        $form = new Form(new Language);
        
        $form->text('language', __('Language'))->rules(function ($form) {
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
