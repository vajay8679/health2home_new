<?php

namespace App\Admin\Controllers;

use App\Models\DoctorWithdrawal;
use App\Models\Doctor;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;
use Illuminate\Support\Facades\DB;

class DoctorWithdrawalController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Doctor Withdrawals';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DoctorWithdrawal);
        
        $grid->model()->orderBy('id','desc');
        $grid->column('id', __('Id'));
        $grid->column('doctor_id', __('Doctor'))->display(function($vendor){
            $vendor = Doctor::where('id',$vendor)->value('doctor_name');
                return $vendor;
        });
        $grid->column('amount', __('Amount'));
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('status_name');
            if ($status == 6) {
                return "<span class='label label-warning'>$status_name</span>";
            } if ($status == 7) {
                return "<span class='label label-success'>$status_name</span>";
            }if ($status == 8) {
                return "<span class='label label-success'>$status_name</span>";
            }
        });
        
        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
              $actions->disableView();
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
        $show = new Show(DoctorWithdrawal::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('doctor_id', __('Doctor id'));
        $show->field('amount', __('Amount'));
        $show->field('reference_proof', __('Reference proof'));
        $show->field('reference_no', __('Reference no'));
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
        $form = new Form(new DoctorWithdrawal);
        $doctors = Doctor::pluck('doctor_name', 'id');
        

        $form->select('doctor_id', __('Doctor'))->options($doctors)->rules(function ($form) {
                return 'required';
            });
        $form->hidden('existing_wallet', __('Existing Wallet'));
        $form->decimal('amount', __('Amount'));
        $form->image('reference_proof', __('Reference Proof'))->uniqueName()->move('doctor_withdrawals');
        $form->text('reference_no', __('Reference No'));
        $form->select('status', __('Status'))->options(Status::where('slug','withdrawal')->pluck('status_name','id'))->rules(function ($form) {
            return 'required';
        });
        
        $form->saved(function (Form $form) {
            if($form->model()->status == 8){
                DB::table('doctors')->where('id',$form->doctor_id)->update([ 'wallet' => $form->existing_wallet ]);
            }
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
