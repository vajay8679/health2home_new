<?php

namespace App\Admin\Controllers;

use App\Models\LabPromoCode;
use App\Models\Status;
use App\Models\PromoType;
use App\Models\Customer;
use App\Models\Laboratory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;

class LabPromoCodeController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Lab Promo Codes';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LabPromoCode());
        
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('lab_id', Laboratory::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('customer_id', __('Customer'))->display(function($customer_id){
            if($customer_id == 0){
                return "NILL";
            }else{
                $customer_id = Customer::where('id',$customer_id)->value('phone_number');
            return $customer_id;
            }
        });
        $grid->column('lab_id', __('Laboratory'))->display(function($lab_id){
            if($lab_id == 0){
                return "NILL";
            }else{
                $lab_id = Laboratory::where('id',$lab_id)->value('lab_name');
                return $lab_id;
            }
        });
        $grid->column('promo_name', __('Promo Name'));
        $grid->column('promo_code', __('Promo Code'));
        $grid->column('min_purchase_price', __('Minimum Purchase Price'));
        $grid->column('max_discount_value', __('Maximum Discount Value'));
        $grid->column('redemptions', __('Redemptions'));
        $grid->column('promo_type', __('Promo Type'))->display(function($promo_id){
            $promo_id = PromoType::where('id',$promo_id)->value('type_name');
            return $promo_id;
        });
        $grid->column('discount', __('Discount'));
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('status_name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } if ($status == 2) {
                return "<span class='label label-danger'>$status_name</span>";
            } 
        });


        $grid->disableExport();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });

        $grid->filter(function ($filter) {
            //Get All status
        $statuses = Status::where('slug','general')->pluck('status_name','id');
        $promo_types = PromoType::pluck('type_name', 'id');
        $labs = Laboratory::pluck('lab_name', 'id');
        if(Admin::user()->isAdministrator()){
            $filter->equal('lab_id', 'Laboratory')->select($labs);
            $filter->like('promo_name', __('Promo name'));
            $filter->like('promo_code', __('Promo code'));
            $filter->equal('promo_type', __('Promo type'))->select($promo_types);
            $filter->equal('status', __('Status'))->select($statuses);
        }else{
            $filter->like('promo_name', __('Promo name'));
            $filter->like('promo_code', __('Promo code'));
            $filter->equal('promo_type', __('Promo type'))->select($promo_types);
            $filter->equal('status', __('Status'))->select($statuses);
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
        $show = new Show(LabPromoCode::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('promo_name', __('Promo name'));
        $show->field('promo_code', __('Promo code'));
        $show->field('description', __('Description'));
        $show->field('promo_type', __('Promo type'));
        $show->field('discount', __('Discount'));
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
        $form = new Form(new LabPromoCode());
        $statuses = Status::where('slug','general')->pluck('status_name','id');
        $promo_types = PromoType::pluck('type_name', 'id');
        $labs = Laboratory::pluck('lab_name', 'id');
        $lab_id = Laboratory::where('admin_user_id',Admin::user()->id)->value('id');
        $customers = Customer::pluck('phone_number', 'id');
        
        if(!Admin::user()->isAdministrator()){
            $form->hidden('lab_id')->value($lab_id);
        }else{
            $form->select('lab_id', __('Laboratory'))->options($labs)->rules(function ($form) {
                return 'required';
            });
        }
        $form->select('customer_id', __('Customer'))->options($customers);
        $form->text('promo_name', __('Promo Name'))->rules(function ($form) {
            return 'required|max:250';
        });

        $form->text('promo_code', __('Promo Code'))->rules(function ($form) {
            return 'required|max:250';
        });

        $form->textarea('description', __('Description'))->rules('required');
        $form->textarea('long_description', __('Long Description'))->rules('required');
        $form->select('promo_type', __('Promo Type'))->options($promo_types)->default(1)->rules(function ($form) {
            return 'required';
        });
        $form->decimal('discount', __('Discount'))->rules('required');
        $form->text('min_purchase_price', __('Minimum Purchase Price'))->rules('required')->help('Minimum order total');
        $form->text('max_discount_value', __('Maximum Discount Value'))->rules('required')->help('Maximum discount amount for this promo');
        $form->text('redemptions', __('Redemptions'))->rules('required|numeric');
        $form->select('status', __('Status'))->options(Status::where('slug','general')->pluck('status_name','id'))->rules(function ($form) {
            return 'required';
        });
        $form->saving(function ($form) {
           if(!$form->customer_id){
              $form->customer_id = 0;
           }
           if(!$form->lab_id){
              $form->lab_id = 0;
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
