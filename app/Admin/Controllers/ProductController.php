<?php

namespace App\Admin\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\UnitMeasurement;
use App\Models\Status;
use App\Models\Vendor;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;

class ProductController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Products';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Product);
        
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('vendor_id', Vendor::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('vendor_id', __('Vendor'))->display(function($vendor){
            $vendor = Vendor::where('id',$vendor)->value('store_name');
                return $vendor;
        });
        $grid->column('category_id', __('Category'))->display(function($category){
            $category_name = Category::where('id',$category)->value('category_name');
            return $category_name;
        });
        $grid->column('sub_category_id', __('Sub Category'))->display(function($sub_category_id){
            $sub_category_name = SubCategory::where('id',$sub_category_id)->value('sub_category_name');
            return $sub_category_name;
        });
        $grid->column('product_name', __('Product'));
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
            $category = Category::pluck('category_name', 'id');
            $units = UnitMeasurement::pluck('unit', 'id');
            $vendors = Vendor::pluck('store_name', 'id');
            
            if(Admin::user()->isAdministrator()){
                $filter->like('product_name', 'Product Name');
                $filter->equal('category_id', 'Category')->select($category);
                $filter->equal('unit_id', 'Unit')->select($units);
                $filter->equal('status', 'Status')->select($statuses);
                $filter->equal('vendor_id', 'Vendor')->select($vendors);
            }else{
                $filter->like('product_name', 'Product Name');
                $filter->equal('category_id', 'Category')->select($category);
                $filter->equal('unit_id', 'Unit')->select($units);
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
        $show = new Show(Product::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('category_id', __('Category id'));
        $show->field('product_name', __('Product name'));
        $show->field('description', __('Description'));
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
        $form = new Form(new Product);
        $statuses = Status::where('slug','general')->pluck('status_name','id');
        $category = Category::pluck('category_name', 'id');
        $units = UnitMeasurement::pluck('unit', 'id');
        $vendors = Vendor::pluck('store_name', 'id');
        $vendor_id = Vendor::where('admin_user_id',Admin::user()->id)->value('id');
        
         if(!Admin::user()->isAdministrator()){
            $form->hidden('vendor_id')->value($vendor_id);
            $form->select('category_id', __('Category'))->load('sub_category_id', '/admin/get_vendor_sub_category', 'id', 'sub_category_name')->options(Category::where('vendor_id',$vendor_id)->pluck('category_name', 'id'))->rules(function ($form) {
            return 'required';
            });
            $form->select('sub_category_id', 'Sub Category')->options(function ($id) {
                $sub_category = SubCategory::find($id);

                if ($sub_category) {
                    return [$sub_category->id => $sub_category->sub_category_name];
                }
            })->rules(function ($form) {
                return 'required';
            });
        }else{
            $form->select('vendor_id', __('Vendor'))->options($vendors)->rules(function ($form) {
                return 'required';
            });
            $form->select('category_id', __('Category'))->load('sub_category_id', '/admin/get_sub_category', 'id', 'sub_category_name')->options($category)->rules(function ($form) {
            return 'required';
            });
            $form->select('sub_category_id', 'Sub Category')->options(function ($id) {
                $sub_category = SubCategory::find($id);

                if ($sub_category) {
                    return [$sub_category->id => $sub_category->sub_category_name];
                }
            })->rules(function ($form) {
                return 'required';
            });
        }    
        $form->text('product_name', __('Product name'))->rules(function ($form) {
            return 'required|max:250';
        });
        $form->text('slug', __('Slug'))->rules(function ($form) {
            return 'required|max:100';
        });
        $form->image('image', __('Image'))->rules('required')->uniqueName()->move('products')->rules('required');
        $form->textarea('description', __('Description'))->rules(function ($form) {
            return 'required';
        });
        $form->decimal('marked_price', __('Marked Price'))->rules(function ($form) {
            return 'required';
        });
        $form->text('discount', __('Discount'))->rules(function ($form) {
            return 'required';
        });
        $form->decimal('price', __('Price'))->rules(function ($form) {
            return 'required';
        });
        $form->select('unit_id', __('Unit'))->options($units)->rules(function ($form) {
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
