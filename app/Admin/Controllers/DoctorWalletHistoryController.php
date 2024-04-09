<?php

namespace App\Admin\Controllers;

use App\Models\DoctorWalletHistory;
use App\Models\Doctor;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DoctorWalletHistoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Doctor Wallet History';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DoctorWalletHistory);
        
        $grid->model()->orderBy('id','desc');
        $grid->column('id', __('Id'));
        $grid->column('doctor_id', __('Doctor'))->display(function($doctor_id){
            return Doctor::where('id',$doctor_id)->value('doctor_name');
        });
        $grid->column('message', __('Message'));
        $grid->column('amount', __('Amount'));
        
        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });
        $grid->filter(function ($filter) {
            //Get All status
            $doctors = Doctor::pluck('doctor_name', 'id');
            
            $filter->equal('doctor_id', 'Doctor')->select($doctors);
            
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
        $show = new Show(DoctorWalletHistory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('doctor_id', __('Doctor'));
        $show->field('message', __('Message'));
        $show->field('amount', __('Amount'));
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
        $form = new Form(new DoctorWalletHistory);
        $doctor = Doctor::pluck('doctor_name', 'id');

        $form->select('doctor_id', __('Doctor'))->options($doctor)->rules(function ($form) {
            return 'required';
        });
        $form->text('message', __('Message'))->rules(function ($form) {
            return 'required';
        });
        $form->select('type', __('Type'))->options(['1' => 'Credit', '2'=> 'Debit'])->rules(function ($form) {
            return 'required';
        });
        $form->decimal('amount', __('Amount'))->rules(function ($form) {
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
