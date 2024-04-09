<?php

namespace App\Admin\Controllers;

use App\Models\DoctorSpecialistCategory;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class DoctorSpecialistCategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Doctor Specialist Category';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DoctorSpecialistCategory());

        $grid->column('id', __('Id'));
        $grid->column('category_name', __('Category Name'));
        $grid->column('category_image', __('Category Image'))->image();
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
        
            $filter->like('category_name', 'Category Name');
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
        $show = new Show(DoctorSpecialistCategory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('category_name', __('Category name'));
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
        $form = new Form(new DoctorSpecialistCategory());
        
        $statuses = Status::where('slug','general')->pluck('status_name','id');

        $form->text('category_name', __('Category Name'))->rules('required');
        $form->image('category_image', __('Category Image'))->uniqueName()->move('specialist_categories')->rules('required');
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
