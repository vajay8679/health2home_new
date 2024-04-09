<?php

namespace App\Admin\Controllers;

use App\Models\VendorEarning;
use App\Models\Status;
use App\Models\Order;
use App\Models\Vendor;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;

class VendorEarningController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Vendor Earnings';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new VendorEarning);
        
        $grid->model()->orderBy('id','desc');
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('vendor_id', Vendor::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('order_id', __('Order id'))->display(function($order_id){
            return Order::where('id',$order_id)->value('id');
        });
        $grid->column('vendor_id', __('Vendor name'))->display(function($vendor_id){
            return Vendor::where('id',$vendor_id)->value('store_name');
        });
        $grid->column('amount', __('Amount'));
        
        $grid->disableExport();
        $grid->disableCreation();
        if(Admin::user()->isAdministrator()){
            $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
            });
        }else{
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
            });
        }
        $grid->filter(function ($filter) {
            //Get All status
            $orders = Order::pluck('id', 'id');
            $vendors = Vendor::pluck('store_name', 'id');
            if(Admin::user()->isAdministrator()){
                $filter->equal('order_id', 'Order')->select($orders);
                $filter->equal('vendor_id', 'Vendor')->select($vendors);
            }else{
                $filter->equal('order_id', 'Order')->select($orders);
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
        $show = new Show(VendorEarning::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('order_id', __('Order id'));
        $show->field('vendor_id', __('Vendor id'));
        $show->field('amount', __('Amount'));
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
        $form = new Form(new VendorEarning);
        $vendor = Vendor::pluck('store_name', 'id');
        $order = Order::pluck('id', 'id');

        $form->select('order_id', __('Order id'))->options($order)->rules(function ($form) {
            return 'required';
        });
        $form->select('vendor_id', __('Vendor name'))->options($vendor)->rules(function ($form) {
            return 'required';
        });
        $form->decimal('amount', __('Amount'))->rules(function ($form) {
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
