<?php

namespace App\Admin\Controllers;

use App\Models\HospitalPharmacy;
use App\Models\Vendor;
use App\Models\Hospital;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\DB;

class HospitalPharmacyController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hospital Pharmacies';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new HospitalPharmacy());
        
         if(!Admin::user()->isAdministrator()){
            $grid->model()->where('hospital_id', Hospital::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('hospital_id', __('Hospital'))->display(function($hospitals){
            $hospitals = Hospital::where('id',$hospitals)->value('hospital_name');
            return $hospitals;
        });
        $grid->column('pharmacy_id', __('Pharmacy'))->display(function($pharmacies){
            $pharmacies = Vendor::where('id',$pharmacies)->value('store_name');
            return $pharmacies;
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
        $pharmacies = Vendor::pluck('store_name','id');
        $statuses = Status::where('slug','general')->pluck('status_name','id');
        
        $filter->equal('hospital_id', __('Hospital'))->select($hospitals);
        $filter->equal('pharmacy_id', __('Pharmacy'))->select($pharmacies);
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
        $show = new Show(HospitalPharmacy::findOrFail($id));

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
        $form = new Form(new HospitalPharmacy());
        $hospitals = Hospital::pluck('hospital_name','id');
        $hospital_id = Hospital::where('admin_user_id',Admin::user()->id)->value('id');
        $pharmacies = Vendor::pluck('store_name','id');
        $statuses = Status::where('slug','general')->pluck('status_name','id');

        if(!Admin::user()->isAdministrator()){
            $form->hidden('hospital_id')->value($hospital_id);
        }else{
            $form->select('hospital_id', __('Hospital'))->options($hospitals)->rules(function ($form) {
            return 'required';
        });
        }
        $form->select('pharmacy_id', __('Pharmacy'))->options($pharmacies)->rules(function ($form) {
            return 'required';
        });
        $form->select('status', __('Status'))->options($statuses)->default(1)->rules(function ($form) {
            return 'required';
        });
        $form->saved(function (Form $form) {
            $this->update_hospital_id($form->hospital_id,$form->pharmacy_id);
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
    
    function update_hospital_id($hospital_id,$pharmacy_id){
        DB::table('vendors')
            ->where('id', $pharmacy_id)
            ->update(['hospital_id' => $hospital_id]); 
    }
    
    
}
