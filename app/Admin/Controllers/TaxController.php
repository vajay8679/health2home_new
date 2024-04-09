<?php

namespace App\Admin\Controllers;

use App\Models\Tax;
use App\Models\Status;
use App\Models\Service;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class TaxController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Taxes';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Tax);

        $grid->column('id', __('Id'));
        $grid->column('service_id', __('Service'))->display(function($service){
            $service_name = Service::where('id',$service)->value('service_name');
                return "$service_name";
        });
        $grid->column('tax', __('Tax'));
        $grid->column('percentage', __('Percentage'));
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('status_name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        });
        
        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });
        $grid->filter(function ($filter) {
            $statuses = Status::where('slug','general')->pluck('status_name','id');
            
            $filter->like('tax', 'Tax');
            $filter->equal('status', 'Status')->select($statuses);
        
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
        $show = new Show(Tax::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('tax', __('Tax'));
        $show->field('percentage', __('Percentage'));
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
        $form = new Form(new Tax);
        $services = Service::pluck('service_name','id');
        
        $form->select('service_id', __('Service'))->options($services)->rules(function ($form) {
            return 'required';
        });
        $form->text('tax', __('Tax'));
        $form->text('percentage', __('Percentage'));
        $form->select('status', __('Status'))->options(Status::where('slug','general')->pluck('status_name','id'))->rules(function ($form) {
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
