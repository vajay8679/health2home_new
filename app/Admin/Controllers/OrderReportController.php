<?php

namespace App\Admin\Controllers;

use App\Models\Order;
use App\Models\Status;
use App\Models\Customer;
use App\Models\Address;
use App\Models\Label;
use App\Models\Vendor;
use App\Models\PaymentMode;
use App\Models\DeliveryBoy;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;

class OrderReportController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Order Reports';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order);
        
        if(Admin::user()->isRole('vendor')){
            $grid->model()->where('vendor_id', Vendor::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('vendor_id', __('Vendor'))->display(function($vendor){
            $vendor = Vendor::where('id',$vendor)->value('username');
                return $vendor;
        });
        $grid->column('order_id', __('Order id'));
        $grid->column('customer_id', __('Customer'))->display(function($customer_id){
            return Customer::where('id',$customer_id)->value('customer_name');
        });
        $grid->column('address_id', __('Address'))->display(function($address_id){
            return Address::where('id',$address_id)->value('address');
        });
        $grid->column('sub_total', __('Sub total'));
        $grid->column('discount', __('Discount'));
        $grid->column('total', __('Total'));
        $grid->column('delivered_by', __('Delivered by'))->display(function($delivered_by){
            if($delivered_by){
                return DeliveryBoy::where('id',$delivered_by)->value('delivery_boy_name');
            }else{
                return '---';
            }
            
        });
        $grid->column('payment_mode', __('Payment mode'))->display(function($payment_mode){
            return PaymentMode::where('id',$payment_mode)->value('payment_name');
        });
        $grid->column('status', __('Status'))->display(function($status){
            $label_name = Label::where('id',$status)->value('label_name');
            if ($status == 6) {
                return "<span class='label label-success'>$label_name</span>";
            } else {
                return "<span class='label label-warning'>$label_name</span>";
            }
        });
        $grid->column('created_at', __('Date'));
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });
        $grid->disableCreation();
        $grid->disableExport();
        $grid->filter(function ($filter) {
            //Get All status
            $statuses = Status::pluck('status_name', 'id');
            $payment_modes = PaymentMode::pluck('payment_name', 'id');
            $delivery_boys = DeliveryBoy::pluck('delivery_boy_name', 'id');
            $customers = Customer::pluck('customer_name', 'id');
            $vendors = Vendor::pluck('store_name', 'id');
            
            $filter->equal('customer_id', 'Customer')->select($customers);
            $filter->equal('vendor_id', 'Vendor')->select($vendors);
            $filter->equal('delivered_by', 'Delivered By')->select($delivery_boys);
            $filter->equal('payment_mode', 'Payment Mode')->select($payment_modes);
            $filter->between('created_at', 'Date')->datetime();
        });
        $grid->footer(function ($query) {

            // Query the total amount of the order with the paid status
            $data = $query->sum('total');
        
            return "<div style='padding: 10px;'>Total revenue ： $data</div>";
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
        $show = new Show(Order::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('order_id', __('Order id'));
        $show->field('customer_id', __('Customer id'));
        $show->field('address_id', __('Address id'));
        $show->field('expected_delivery_date', __('Expected delivery date'));
        $show->field('total', __('Total'));
        $show->field('discount', __('Discount'));
        $show->field('sub_total', __('Sub total'));
        $show->field('promo_id', __('Promo id'));
        $show->field('delivered_by', __('Delivered by'));
        $show->field('payment_mode', __('Payment mode'));
        $show->field('items', __('Items'));
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
        $form = new Form(new Order);

        
    }
}
