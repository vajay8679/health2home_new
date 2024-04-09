<?php

namespace App\Admin\Controllers;

use App\Models\Symptom;
use App\Models\Status;
use App\Models\DoctorSpecialistCategory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class SymptomController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Symptoms';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Symptom());
        
        $grid->model()->orderBy('id','desc');
        $grid->column('id', __('Id'));
        $grid->column('specialist_id', __('Specialist'))->display(function($specialist){
            $specialist = DoctorSpecialistCategory::where('id',$specialist)->value('category_name');
                return "$specialist";
        });
        $grid->column('symptom_name', __('Symptom Name'));
        $grid->column('symptom_image', __('Symptom Image'))->image();
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
            //Get All status
            $statuses = Status::where('slug','general')->pluck('status_name','id');
        
            $filter->like('service_name', 'Name');
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
        $show = new Show(Symptom::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('service_name', __('Service name'));
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
        $form = new Form(new Symptom());
        $specialist = DoctorSpecialistCategory::pluck('category_name','id');
        
        $form->select('specialist_id', __('Specialist'))->options($specialist)->rules(function ($form) {
            return 'required';
        });
        $form->text('symptom_name', __('Symptom Name'))->rules('required');
        $form->image('symptom_image', __('Symptom Image'))->move('symptoms')->uniqueName()->rules('required');
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
