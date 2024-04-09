<?php

namespace App\Admin\Controllers;

use App\Models\CustomerPrescriptionItem;
use App\Models\CustomerPrescription;
use App\Models\Status;
use App\Models\Booking;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class CustomerPrescriptionItemController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Customer Prescription Items';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new CustomerPrescriptionItem);

        $grid->column('id', __('Id'));
        $grid->column('prescription_id', __('Prescription id'))->display(function($prescription_id){
            return CustomerPrescription::where('id',$prescription_id)->value('id');
        });
        $grid->column('medicine_name', __('Medicine name'));
        $grid->column('morning', __('Morning'))->display(function($status){
            if ($status == 1) {
                return "<span class='label label-warning'>Yes</span>";
            }if ($status == 2) {
                return "<span class='label label-success'>No</span>";
            } 
        });
        $grid->column('afternoon', __('Afternoon'))->display(function($status){
            if ($status == 1) {
                return "<span class='label label-warning'>Yes</span>";
            }if ($status == 0) {
                return "<span class='label label-success'>No</span>";
            } 
        });
        $grid->column('night', __('Night'))->display(function($status){
            if ($status == 1) {
                return "<span class='label label-warning'>Yes</span>";
            }if ($status == 0) {
                return "<span class='label label-success'>No</span>";
            } 
        });
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('status_name');
            if ($status == 1) {
                return "<span class='label label-warning'>$status_name</span>";
            }if ($status == 0) {
                return "<span class='label label-success'>$status_name</span>";
            } 
        });
        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
        });
        $grid->filter(function ($filter) {
            $prescriptions = CustomerPrescription::pluck('id', 'id');
         
            $filter->like('prescription_id', 'Prescription')->select($prescriptions);
            $filter->equal('morning', 'Morning')->select(['1' => 'Yes', '0'=> 'No']);
            $filter->equal('afternoon', 'Afternoon')->select(['1' => 'Yes', '0'=> 'No']);
            $filter->equal('night', 'Night')->select(['1' => 'Yes', '0'=> 'No']);
            $filter->equal('status', 'Status');
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
        $show = new Show(CustomerPrescriptionItem::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('medicine_name', __('Medicine name'));
        $show->field('morning', __('Morning'));
        $show->field('afternoon', __('Afternoon'));
        $show->field('status', __('Status'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));
        $show->field('night', __('Night'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new CustomerPrescriptionItem);
        $prescriptions = CustomerPrescription::pluck('id', 'id');
        
        $form->select('prescription_id', __('Prescription'))->options($prescriptions)->rules(function ($form) {
            return 'required';
        });
        $form->text('medicine_name', __('Medicine name'));
        $form->select('morning', __('Morning'))->options(['1' => 'Yes', '0'=> 'No']);
        $form->select('afternoon', __('Afternoon'))->options(['1' => 'Yes', '0'=> 'No']);
        $form->select('night', __('Night'))->options(['1' => 'Yes', '0'=> 'No']);
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
