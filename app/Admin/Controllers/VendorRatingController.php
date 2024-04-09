<?php

namespace App\Admin\Controllers;

use App\Models\VendorRating;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Vendor;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class VendorRatingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Vendor Ratings';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new VendorRating);

        $grid->column('id', __('Id'));
        $grid->column('customer_id', __('Customer name'))->display(function($customer_id){
            return Customer::where('id',$customer_id)->value('customer_name');
        });
        $grid->column('order_id', __('Order name'))->display(function($order_id){
            return Order::where('id',$order_id)->value('order_id');
        });
        $grid->column('vendor_id', __('Vendor name'))->display(function($vendor_id){
            return Vendor::where('id',$vendor_id)->value('store_name');
        });
        $grid->column('ratings', __('Ratings'));
        
        
        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });
        $grid->filter(function ($filter) {
            //Get All status
            $orders = Order::pluck('order_id', 'id');
            $vendors = Vendor::pluck('owner_name', 'id');
            $vendors = Customer::pluck('customer_name', 'id');
            $filter->like('order_id', 'Order')->select($orders);
            $filter->like('vendor_id', 'Vendor')->select($vendors);
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
        $show = new Show(VendorRating::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('customer_id', __('Customer id'))->as(function($customer_id){
            return Customer::where('id',$customer_id)->value('customer_id');
        });
        $show->field('order_id', __('Order id'))->as(function($order_id){
            return Order::where('id',$order_id)->value('order_id');
        });
        $show->field('vendor_id', __('Vendor id'))->as(function($vendor_id){
            return Vendor::where('id',$vendor_id)->value('vendor_id');
        });
        $show->field('ratings', __('Ratings'));
        $show->field('review', __('Review'));
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
        $form = new Form(new VendorRating);
        $vendor = Vendor::pluck('owner_name', 'id');
        $order = Order::pluck('order_id', 'id');
        $customer = Customer::pluck('customer_name', 'id');
        $form->select('customer_id', __('Customer name'))->options($customer)->rules(function ($form) {
            return 'required';
        });
        $form->select('order_id', __('Order id'))->options($order)->rules(function ($form) {
            return 'required';
        });
        $form->select('vendor_id', __('Vendor name'))->options($vendor)->rules(function ($form) {
            return 'required';
        });
        $form->text('ratings', __('Ratings'));
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
