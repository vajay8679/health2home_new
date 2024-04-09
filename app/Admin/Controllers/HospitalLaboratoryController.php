<?php

namespace App\Admin\Controllers;

use App\Models\HospitalLaboratory;
use App\Models\Laboratory;
use App\Models\Hospital;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\DB;

class HospitalLaboratoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hospital Laboratories';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new HospitalLaboratory());
        
         if(!Admin::user()->isAdministrator()){
            $grid->model()->where('hospital_id', Hospital::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('hospital_id', __('Hospital'))->display(function($hospitals){
            $hospitals = Hospital::where('id',$hospitals)->value('hospital_name');
            return $hospitals;
        });
        $grid->column('lab_id', __('Laboratory'))->display(function($labs){
            $labs = Laboratory::where('id',$labs)->value('lab_name');
            return $labs;
        });
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('status_name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        });
        
        if(Admin::user()->isAdministrator()){
            $grid->disableExport();
            $grid->actions(function ($actions) {
                $actions->disableView();
            });
        }else{
            $grid->disableExport();
            //$grid->disableCreation();
            $grid->actions(function ($actions) {
                $actions->disableView();
            });
        }

        $grid->filter(function ($filter) {
            //Get All status
        $hospitals = Hospital::pluck('hospital_name','id');
        $labs = Laboratory::pluck('lab_name','id');
        $statuses = Status::where('slug','general')->pluck('status_name','id');
        
        $filter->equal('hospital_id', __('Hospital'))->select($hospitals);
        $filter->equal('lab_id', __('Laboratory'))->select($labs);
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
        $show = new Show(HospitalLaboratory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('hospital_id', __('Hospital id'));
        $show->field('doctor_id', __('Doctor id'));
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
        $form = new Form(new HospitalLaboratory());
        $hospitals = Hospital::pluck('hospital_name','id');
        $hospital_id = Hospital::where('admin_user_id',Admin::user()->id)->value('id');
        $labs = Laboratory::pluck('lab_name','id');
        $statuses = Status::where('slug','general')->pluck('status_name','id');

        if(!Admin::user()->isAdministrator()){
            $form->hidden('hospital_id')->value($hospital_id);
        }else{
            $form->select('hospital_id', __('Hospital'))->options($hospitals)->rules(function ($form) {
            return 'required';
        });
        }
        $form->select('lab_id', __('Laboratory'))->options($labs)->rules(function ($form) {
            return 'required';
        });
        $form->select('status', __('Status'))->options($statuses)->default(1)->rules(function ($form) {
            return 'required';
        });
        $form->saved(function (Form $form) {
            $this->update_lab_id($form->hospital_id,$form->lab_id);
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
    
    function update_lab_id($hospital_id,$lab_id){
        DB::table('laboratories')
            ->where('id', $lab_id)
            ->update(['hospital_id' => $hospital_id]); 
    }
    
    
}
