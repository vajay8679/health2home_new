<?php

namespace App\Admin\Controllers;

use App\Models\DoctorDocument;
use App\Models\Doctor;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;
class DoctorDocumentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Doctor Document';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new DoctorDocument());
        
        $grid->model()->orderBy('id','desc');
        $grid->column('id', __('Id'));
        $grid->column('doctor_id', __('Doctor'))->display(function($doctors){
            $doctors = Doctor::where('id',$doctors)->value('doctor_name');
            return "$doctors";
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
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });
        
        $grid->filter(function ($filter) {
            //Get All status
            $doctors = Doctor::pluck('doctor_name', 'id');
            
            $filter->equal('doctor_id', 'Doctor')->select($doctors);
            
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
        $show = new Show(DoctorDocument::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('doctor_id', __('Doctor id'));
        $show->field('id_proof', __('Id proof'));
        $show->field('id_proof_status', __('Id proof status'));
        $show->field('certificate', __('Certificate'));
        $show->field('certificate_status', __('Certificate status'));
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
        $form = new Form(new DoctorDocument());
        
        $statuses = Status::pluck('status_name', 'id');
        $doctors = Doctor::pluck('doctor_name', 'id');

        $form->select('doctor_id', __('Doctor'))->options($doctors)->rules(function ($form) {
            return 'required';
        });
        $form->text('document_name', __('Document Name'));
        $form->image('document_path', __('Document Path'))->uniqueName();
        $form->select('status', __('Status'))->options(Status::where('slug','document')->pluck('status_name','id'))->rules(function ($form) {
            return 'required';
        });
       
        $form->saved(function ($form) {
            $ap_count = DB::table('doctor_documents')->where('status',4)->where('doctor_id',$form->model()->doctor_id)->count();
            if($ap_count == 2){
                Doctor::where('id',$form->model()->doctor_id)->update([ 'document_approved_status' => 1 ]);
            }else{
                Doctor::where('id',$form->model()->doctor_id)->update([ 'document_approved_status' => 0 ]);
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

 public function destroy($id)
    {
        $document = DoctorDocument::findOrFail($id);
        $file_path = public_path().'/files/'.$doctor->file_path;
        unlink($file_path);
        $document->delete();
        return redirect('admin/dashboard');
    }
}
