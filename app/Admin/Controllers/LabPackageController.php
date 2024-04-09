<?php

namespace App\Admin\Controllers;

use App\Models\LabPackage;
use App\Models\LabRelevance;
use App\Models\Laboratory;
use App\Models\Status;
use App\Models\LabTag;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;

class LabPackageController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Lab Packages';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LabPackage());
        
        $grid->model()->orderBy('id','desc');
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('lab_id', Laboratory::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('lab_id', __('Lab'))->display(function($labs){
            $labs = Laboratory::where('id',$labs)->value('lab_name');
                return "$labs";
        });
        $grid->column('lab_relevance_id', __('Lab Relevance'))->display(function($relevances){
            $relevances = LabRelevance::where('id',$relevances)->value('relevance_name');
                return "$relevances";
        });
        $grid->column('package_name', __('Package Name'));
        $grid->column('package_image', __('Package Image'))->image();
        $grid->column('short_description', __('Short Description'));
        $grid->column('expected_delivery', __('Expected Delivery'));
        $grid->column('tag', __('Tag'))->display(function($tags){
            if ($tags == 0) {
                return"NILL";
            }else{
            $tags = LabTag::where('id',$tags)->value('tag_name');
                return "$tags";
            }
        });
        $grid->column('price', __('Price'));
        $grid->column('is_popular', __('Is popular'))->display(function($status){
            if ($status == 1) {
                return "<span class='label label-success'>Yes</span>";
            } else {
                return "<span class='label label-danger'>No</span>";
            }
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
            $labs = Laboratory::pluck('lab_name', 'id');
            
            $filter->like('lab_id', __('Lab'))->select($labs);
            $filter->like('package_name', __('Package name'));

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
        $show = new Show(LabPackage::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('package_name', __('Package name'));
        $show->field('short_description', __('Short description'));
        $show->field('long_description', __('Long description'));
        $show->field('expected_delivery', __('Expected delivery'));
        $show->field('test_type', __('Test type'));
        $show->field('price', __('Price'));
        $show->field('is_popular', __('Is popular'));
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
        $form = new Form(new LabPackage());
        $statuses = Status::where('slug','general')->pluck('status_name', 'id');
        $relevances = LabRelevance::pluck('relevance_name', 'id');
        $labs = Laboratory::pluck('lab_name', 'id');
        $tags = LabTag::pluck('tag_name', 'id');
        
        $form->select('lab_id', __('Lab'))->options($labs)->rules(function ($form) {
            return 'required';
        });
        $form->select('lab_relevance_id', __('Lab Relevance'))->options($relevances)->rules(function ($form) {
            return 'required';
        });
        $form->text('package_name', __('Package Name'))->rules(function ($form) {
            return 'required';
        });
        $form->textarea('short_description', __('Short Description'))->rules('required');
        $form->textarea('long_description', __('Long Description'))->rules('required');
        $form->textarea('test_preparation', __('Test Prepration'))->rules('required');
        $form->image('package_image', __('Package Image'))->rules('required')->uniqueName()->move('lab_packages');
        $form->text('expected_delivery', __('Expected Delivery'))->rules('required');
        $form->select('tag', __('Tag'))->options($tags);
        $form->decimal('price', __('Price'))->rules('required');
        $form->select('is_popular', __('Is popular'))->rules('required')->options(['1' => 'Yes', '0'=> 'No'])->default('1');
        $form->select('status', __('Status'))->options($statuses)->default(1)->rules(function ($form) {
            return 'required';
        });
        $form->saving(function ($form) {
           if(!$form->tag){
              $form->tag = 0;
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
