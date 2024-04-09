<?php

namespace App\Admin\Controllers;

use App\Models\VendorDocument;
use App\Models\Status;
use App\Models\Vendor;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
use Admin;

class VendorDocumentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Vendor Document';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new VendorDocument);
        
        $grid->model()->orderBy('id','desc');
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('vendor_id', Vendor::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('vendor_id', __('Vendor'))->display(function($vendor){
            $vendor_name = Vendor::where('id',$vendor)->value('store_name');
            return $vendor_name;
        });
        $grid->column('document_name', __('Document Name'));
        $grid->column('document_path', __('Document'))->image();
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('status_name');
            if ($status == 3) {
                return "<span class='label label-warning'>$status_name</span>";
            }if ($status == 4) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        });
       
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
        if(Admin::user()->isAdministrator()){
        $grid->filter(function ($filter) {
            $statuses = Status::where('slug','document')->pluck('status_name','id'); 
            $vendors = Vendor::pluck('store_name','id'); 
            
            $filter->equal('vendor_id', 'Vendor')->select($vendors);
            $filter->equal('id_proof_status', 'Id Proof Status')->select($statuses);
            $filter->equal('cerficate_status', 'Certificate Status')->select($statuses);
            
        });
        }else{
            $grid->disableFilter();
        }

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
        $show = new Show(VendorDocument::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('vendor_id', __('Vendor id'));
        $show->field('document_name', __('Id proof'));
        $show->field('id_proof_status', __('Id proof status'));
        $show->field('certificate', __('Certificate'));
        $show->field('certificate_status', __('Certificate status'));
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
        $form = new Form(new VendorDocument);

        $form->select('vendor_id', __('Vendor id'))->options(Vendor::pluck('store_name','id'))->rules(function ($form) {
            return 'required';
        });
        $form->text('document_name', __('Document Name'));
        $form->image('document_path', __('Document Path'))->uniqueName();
        $form->select('status', __('Status'))->options(Status::where('slug','document')->pluck('status_name','id'))->rules(function ($form) {
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
        $form->saved(function (Form $form) {
            $ap_count = DB::table('vendor_documents')->where('status',4)->where('vendor_id',$form->model()->vendor_id)->count();
            if($ap_count == 2){
                Vendor::where('id',$form->model()->vendor_id)->update([ 'document_approved_status' => 1 ]);
            }else{
                Vendor::where('id',$form->model()->vendor_id)->update([ 'document_approved_status' => 0 ]);
            }
        });

        return $form;
    }
}
