<?php

namespace App\Admin\Controllers;

use App\Models\DoctorBankDetail;
use App\Models\Doctor;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DoctorBankDetailController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Doctor Bank  Details';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DoctorBankDetail());

        $grid->column('id', __('Id'));
        $grid->column('doctor_id', __('Doctor'))->display(function($doctor){
            $doctor_name = Doctor::where('id',$doctor)->value('doctor_name');
                return "$doctor_name";
        });
        $grid->column('bank_name', __('Bank Name'));
        $grid->column('bank_account_number', __('Bank Account Number'));
        $grid->column('beneficiary_name', __('Beneficiary Name'));
        $grid->column('swift_code', __('Swift Code'));
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('status_name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        });
        $grid->disableExport();
        //$grid->disableCreateButton();
        $grid->actions(function ($actions) {
        // $actions->disableView();
        $actions->disableDelete();
        $actions->disableEdit();
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
        $show = new Show(DoctorBankDetail::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('hospital_id', __('Hospital Name'));
        $show->field('bank_name', __('Bank Name'));
        $show->field('bank_account_number', __('Bank Account Number'));
        $show->field('ifsc_code', __('Ifsc Code'));
        $show->field('aadhar_number', __('Aadhar Number'));
        $show->field('pan_number', __('Pan Number'));
        $show->field('status', __('Status'))->as(function($status){
            $status_name = Status::where('id',$status)->value('name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        });
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
        $form = new Form(new DoctorBankDetail());
        $doctors = Doctor::pluck('doctor_name','id');
        $statuses = Status::where('slug','general')->pluck('status_name','id');

        $form->select('doctor_id', __('Doctor'))->options($doctors)->rules(function ($form) {
            return 'required';
        });
        $form->text('bank_name', __('Bank Name'));
        $form->text('bank_account_number', __('Bank Account Number'));
        $form->text('beneficiary_name', __('Beneficiary Name'));
        $form->text('swift_code', __('Swift Code'));
        $form->select('status', __('Status'))->options($statuses)->default(1)->rules(function ($form) {
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
