<?php

namespace App\Admin\Controllers;

use App\Models\HospitalGallery;
use App\Models\Hospital;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;

class HospitalGalleryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hospital Galleries';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new HospitalGallery());
        
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('hospital_id', Hospital::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('hospital_id', __('Hospital'))->display(function($hospitals){
            $hospitals = Hospital::where('id',$hospitals)->value('hospital_name');
            return $hospitals;
        });
        $grid->column('image_path', __('Image Path'))->image();
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
        $hospitals = Hospital::pluck('hospital_name','id');
        $statuses = Status::where('slug','general')->pluck('status_name','id');
        if(Admin::user()->isAdministrator()){
                $filter->equal('hospital_id', __('Hospital'))->select($hospitals);
                $filter->equal('status', 'Status')->select($statuses); 
            }else{
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
        $show = new Show(HospitalGallery::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('hospital_id', __('Hospital id'));
        $show->field('image_path', __('Image path'));
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
        $form = new Form(new HospitalGallery());
        $statuses = Status::where('slug','general')->pluck('status_name','id');
        $hospitals = Hospital::pluck('hospital_name','id');
        $hospital_id = Hospital::where('admin_user_id',Admin::user()->id)->value('id');

        if(!Admin::user()->isAdministrator()){
            $form->hidden('hospital_id')->value($hospital_id);
        }else{
            $form->select('hospital_id', __('Hospital'))->options($hospitals)->rules(function ($form) {
            return 'required';
        });
        }
        $form->image('image_path', __('Image Path'))->uniqueName()->move('Hospitals')->rules('required');
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
