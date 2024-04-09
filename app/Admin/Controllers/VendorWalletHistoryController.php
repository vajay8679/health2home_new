<?php

namespace App\Admin\Controllers;

use App\Models\VendorWalletHistory;
use App\Models\Vendor;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;

class VendorWalletHistoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Vendor Wallet Histories';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new VendorWalletHistory);
        
        $grid->model()->orderBy('id','desc');
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('vendor_id', Vendor::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('vendor_id', __('Vendor name'))->display(function($vendor_id){
            return Vendor::where('id',$vendor_id)->value('store_name');
        });
        $grid->column('message', __('Message'));
        $grid->column('amount', __('Amount'));
        
        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });
        $grid->filter(function ($filter) {
            //Get All status
            $vendors = Vendor::pluck('store_name', 'id');
            if(Admin::user()->isAdministrator()){
                $filter->equal('vendor_id', 'Vendor')->select($vendors);
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
        $show = new Show(VendorWalletHistory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('vendor_id', __('Vendor id'))->as(function($vendor_id){
            return Vendor::where('id',$vendor_id)->value('store_name');
        });
        $show->field('message', __('Message'));
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
        $form = new Form(new VendorWalletHistory);
        $vendor = Vendor::pluck('owner_name', 'id');

        $form->select('vendor_id', __('Vendor name'))->options($vendor)->rules(function ($form) {
            return 'required';
        });
        $form->text('message', __('Message'))->rules(function ($form) {
            return 'required';
        });
        $form->select('type', __('Type'))->options(['1' => 'Credit', '2'=> 'Debit'])->rules(function ($form) {
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
