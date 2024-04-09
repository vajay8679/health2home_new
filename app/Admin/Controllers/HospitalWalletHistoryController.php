<?php

namespace App\Admin\Controllers;

use App\Models\HospitalWalletHistory;
use App\Models\Hospital;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;

class HospitalWalletHistoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hospital Wallet History';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new HospitalWalletHistory);
        
        $grid->model()->orderBy('id','desc');
        
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('hospital_id', Hospital::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('hospital_id', __('Hospital'))->display(function($hospital_id){
            return Hospital::where('id',$hospital_id)->value('hospital_name');
        });
        $grid->column('type', __('Type'))->display(function($type){
            if ($type == 1) {
                return "<span class='label label-success'>Consultation</span>";
            }if ($type == 2) {
                return "<span class='label label-warning'>Appointment</span>";
            } else {
                return "<span class='label label-info'>Pharmacy</span>";
            }
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
            if(Admin::user()->isAdministrator()){
            //Get All status
            $hospitals = Hospital::pluck('hospital_name','id');
            
            $filter->equal('hospital_id', __('Hospital'))->select($hospitals);
        }
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
        $show = new Show(HospitalWalletHistory::findOrFail($id));

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
        $form = new Form(new HospitalWalletHistory);
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
