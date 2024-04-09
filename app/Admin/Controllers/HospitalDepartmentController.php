<?php

namespace App\Admin\Controllers;
use App\Models\Hospital;
use App\Models\HospitalDepartment;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;
class HospitalDepartmentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hospital Department';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new HospitalDepartment);
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('hospital_id', Hospital::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        
        $grid->column('id', __('Id'));
        $grid->column('hospital_id', __('Hospital'))->display(function($hospital_id){
            $hospital_name = Hospital::where('id',$hospital_id)->value('hospital_name');
                return $hospital_name;
        });
        $grid->column('name', __('Department Name'));
        $grid->column('image', __('Image'))->image();
        $grid->disableExport();
        $grid->disablefilter();
        $grid->actions(function ($actions) {
            $actions->disableView();
            //$actions->disableEdit();
            $actions->disableDelete();
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
        $form = new Form(new HospitalDepartment);
        $hospitals = Hospital::pluck('hospital_name', 'id');
        $hospital_id = Hospital::where('admin_user_id',Admin::user()->id)->value('id');
        
        if(!Admin::user()->isAdministrator()){
            $form->hidden('hospital_id')->value($hospital_id);
        }else{
            $form->select('hospital_id', __('Hospital'))->options($hospitals)->rules(function ($form) {
                return 'required';
            });
        }
        
        $form->image('image', __('Image'))->uniqueName()->move('hospital_department')->rules('required');
        $form->text('name', __('Department'))->rules(function ($form) {
            return 'required';
        });
        $form->textarea('description', __('Description'))->rules(function ($form) {
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
