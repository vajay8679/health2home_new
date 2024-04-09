<?php

namespace App\Admin\Controllers;

use App\Models\XrayOrder;
use App\Models\Customer;
use App\Models\Laboratory;
use App\Models\CustomerAddress;
use App\Models\XrayOrderStatus;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\MessageBag;
use Admin;

class XrayOrderController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Xray Orders';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new XrayOrder());
        $grid->model()->orderBy('id','desc');
        $grid->column('id', __('Id'));
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('lab_id', Laboratory::where('admin_user_id',Admin::user()->id)->value('id'));
        }else{
            $grid->column('lab_id', __('Lab'))->display(function($laboratories){
            $laboratories = Laboratory::where('id',$laboratories)->value('lab_name');
            return $laboratories;
            }); 
        }
        $grid->column('customer_id', __('Customer'))->display(function($customers){
            $customers = Customer::where('id',$customers)->value('customer_name');
            return $customers;
        });
        $grid->column('patient_name', __('Patient Name'));
        $grid->column('patient_age', __('Patient Age'));
        $grid->column('patient_gender', __('Patient Gender'))->display(function($type){
            if ($type == 1) {
                return "<span class='label label-success'>Male</span>";
            }if ($type == 2) {
                return "<span class='label label-info'>Female</span>";
            }if ($type == 3) {
                return "<span class='label label-warning'>Others</span>";
            } 
        });
        $grid->column('status', __('Status'))->display(function($xray_order_statuses){
            $xray_order_statuses = XrayOrderStatus::where('id',$xray_order_statuses)->value('status');
            return $xray_order_statuses;
        });

        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->filter(function ($filter) {
             //Get All status
        $customers = Customer::pluck('customer_name','id');
        $laboratories = Laboratory::pluck('lab_name','id');
        $xray_order_statuses = XrayOrderStatus::pluck('status','id');

        $filter->equal('customer_id', __('Customer'))->select($customers);
        $filter->equal('laboratory_id', __('Laboratory'))->select($laboratories);
        $filter->equal('xray_order_status', __('Xray Order Status'))->select($xray_order_statuses);
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
        $show = new Show(XrayOrder::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('customer_id', __('Customer id'));
        $show->field('patient_name', __('Patient name'));
        $show->field('patient_dob', __('Patient dob'));
        $show->field('patient_gender', __('Patient gender'));
        $show->field('address_id', __('Address id'));
        $show->field('lab_id', __('Lab id'));
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
        $form = new Form(new XrayOrder());
        $customers = Customer::pluck('customer_name','id');
        $laboratories = Laboratory::pluck('lab_name','id');
        $xray_order_statuses = XrayOrderStatus::pluck('status','id');

        if(!Admin::user()->isAdministrator()){
            $form->hidden('lab_id')->value($laboratories);
        }else{
            $form->select('lab_id', __('Lab'))->options($laboratories);
        }
        $form->select('status', __('Status'))->options($xray_order_statuses)->default(1)->rules(function ($form) {
            return 'required';
        });
        $form->saving(function (Form $form) {
           if($form->status ==1){
                $error = new MessageBag([
                    'title'   => 'Warning',
                    'message' => 'Please change order status...',
                ]);

                return back()->with(compact('error'));
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
