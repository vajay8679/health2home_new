<?php

namespace App\Admin\Controllers;

use App\Models\LabService;
use App\Models\Status;
use App\Models\Laboratory;
use App\Models\Service;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;
class LabServiceController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Lab Services';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LabService());
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('lab_id', Laboratory::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('lab_id', __('Lab Id'))->display(function($laboratories){
            $laboratories = Laboratory::where('id',$laboratories)->value('lab_name');
            return $laboratories;
        });   
        $grid->column('service_id', __('Service'))->display(function($services){
            $services = Service::where('id',$services)->value('service_name');
                return "$services";
            });
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('status_name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        });

        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->filter(function ($filter) {

        $statuses = Status::where('slug','general')->pluck('status_name','id');
        $laboratories = Laboratory::pluck('lab_name','id');
        $providing_services = Service::pluck('service_name','id');
            //Get All status

        $filter->equal('lab_id', __('Lab id'));
        $filter->equal('service_id', __('Service'))->select($providing_services);
        
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
        $show = new Show(LabService::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('lab_id', __('Lab id'));
        $show->field('providing_service', __('Providing service'));
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
        $form = new Form(new LabService());
        $laboratories = Laboratory::pluck('lab_name','id');
        $providing_services = Service::pluck('service_name','id');
        $lab_id = Laboratory::where('admin_user_id',Admin::user()->id)->value('id');
        
        if(!Admin::user()->isAdministrator()){
            $form->hidden('lab_id')->value($lab_id);
        }else{
            $form->select('lab_id', __('Lab Id'))->options($laboratories)->rules(function ($form) {
                return 'required';
            });
        }
        
        $form->select('service_id', __('Service'))->options($providing_services)->rules(function ($form) {
            return 'required';
        });
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
