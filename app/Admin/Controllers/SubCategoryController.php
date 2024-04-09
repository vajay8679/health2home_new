<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Status;
use App\Models\Vendor;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;

class SubCategoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Sub Categories';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new SubCategory);
        
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('vendor_id', Vendor::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('vendor_id', __('Vendor'))->display(function($vendor){
            $vendor = Vendor::where('id',$vendor)->value('store_name');
                return $vendor;
        });
        $grid->column('category_id', __('Category'))->display(function($category_id){
            $category_name = Category::where('id',$category_id)->value('category_name');
            return $category_name;
        });
        $grid->column('sub_category_name', __('Sub Category Name'));
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
            $categories = Category::pluck('category_name', 'id');
            $vendors = Vendor::pluck('store_name', 'id');
            
            if(Admin::user()->isAdministrator()){
                $filter->equal('category_id', 'Category')->select($categories);
                $filter->like('sub_category_name', 'Sub Category Name');
                $filter->equal('status', 'Status')->select($statuses);
                $filter->equal('vendor_id', 'Vendor')->select($vendors);
            }else{
                $filter->equal('category_id', 'Category')->select($categories);
                $filter->like('sub_category_name', 'Sub Category Name');
                $filter->equal('status', 'Status')->select($statuses);
            }
            
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
        $form = new Form(new SubCategory);
        $statuses = Status::pluck('status_name', 'id');
        $categories = Category::pluck('category_name', 'id');
        $vendors = Vendor::pluck('store_name', 'id');
        $vendor_id = Vendor::where('admin_user_id',Admin::user()->id)->value('id');

        if(!Admin::user()->isAdministrator()){
            $form->hidden('vendor_id')->value($vendor_id);
        }else{
            $form->select('vendor_id', __('Vendor'))->options($vendors)->rules(function ($form) {
            return 'required';
            });
            $form->select('category_id', __('Category'))->options($categories)->rules(function ($form) {
            return 'required';
            });
        }
        $form->text('sub_category_name', __('Sub Category Name'))->rules(function ($form) {
            return 'required|max:250';
        });
        $form->image('image', __('Image'))->rules('required')->uniqueName()->move('sub_categories')->rules('required');
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
