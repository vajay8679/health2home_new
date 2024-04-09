<?php

namespace App\Admin\Controllers;

use App\Models\LabWithdrawal;
use App\Models\Laboratory;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;

class LabWithdrawalController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Lab Withdrawals';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LabWithdrawal());
        
        $grid->model()->orderBy('id','desc');
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('lab_id', Laboratory::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('lab_id', __('Lab'))->display(function($laboratories){
            $laboratories = Laboratory::where('id',$laboratories)->value('lab_name');
                return $laboratories;
        });
        $grid->column('amount', __('Amount'));
        $grid->column('reference_proof', __('Reference Proof'))->image();
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('status_name');
            if ($status == 6) {
                return "<span class='label label-warning'>$status_name</span>";
            } if ($status == 7) {
                return "<span class='label label-success'>$status_name</span>";
            }if ($status == 8) {
                return "<span class='label label-danger'>$status_name</span>";
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
            $statuses = Status::where('slug','withdrawal')->pluck('status_name','id');
            $laboratories = Laboratory::pluck('lab_name', 'id');
    
            if(!Admin::user()->isAdministrator()){
                $filter->equal('status', 'Status')->select($statuses);
            }else{
                $filter->equal('lab_id', 'Laboratory')->select($laboratories);
                $filter->equal('status', 'Status')->select($statuses);
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
        $show = new Show(LabWithdrawal::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('lab_id', __('Lab id'));
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
        $form = new Form(new LabWithdrawal());
        $laboratories = Laboratory::pluck('lab_name', 'id');
        $statuses = Status::where('slug','general')->pluck('status_name','id');


        $form->select('lab_id', __('Lab Id'))->options($laboratories)->rules(function ($form) {
                return 'required';
            });
        $form->decimal('amount', __('Amount'));
        $form->image('reference_proof', __('Reference Proof'))->uniqueName()->move('lab_withdrawals');
        $form->text('reference_no', __('Reference no'));
        $form->select('status', __('Status'))->options(Status::where('slug','withdrawal')->pluck('status_name','id'))->rules(function ($form) {
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
