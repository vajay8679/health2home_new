<?php

namespace App\Admin\Controllers;

use App\Models\ConsultationRequest;
use App\Models\Doctor;
use App\Models\Customer;
use App\Models\ConsultationRequestStatus;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Facades\Admin;
class ConsultationRequestController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Consultation Requests';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ConsultationRequest());
        
        if(!Admin::user()->isAdministrator()){
            $grid->model()
                ->join('doctors', 'doctors.id', '=', 'consultation_requests.doctor_id')
                ->join('hospitals', 'hospitals.id', '=', 'doctors.hospital_id')
                ->select('consultation_requests.*')
                ->where('hospitals.admin_user_id',Admin::user()->id)
                ->orderBy('consultation_requests.id','desc');
        }
        $grid->column('id', __('Id'));
        $grid->column('patient_id', __('Patient'))->display(function($customers){
            $customers = Customer::where('id',$customers)->value('customer_name');
            return $customers;
        });
        $grid->column('doctor_id', __('Doctor'))->display(function($doctors){
            $doctors = Doctor::where('id',$doctors)->value('doctor_name');
            return $doctors;
        });
        $grid->column('total', __('Total'));
        $grid->column('rating', __('Rating'))->display(function($rating){
            if($rating){
                return $rating;
            }else{
                return '---';
            }
            
        });
        $grid->column('comments', __('Comments'))->display(function($comments){
            if($comments){
                return $comments;
            }else{
                return '---';
            }
            
        });
        $grid->column('status', __('Status'))->display(function($consultation_request_statuses){
            $consultation_request_statuses = ConsultationRequestStatus::where('id',$consultation_request_statuses)->value('status_name');
            return $consultation_request_statuses;
        });
        

        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });

        $grid->filter(function ($filter) {

        $doctors = Doctor::pluck('doctor_name','id');
        $customers = Customer::pluck('customer_name','id');
        $consultation_request_statuses = ConsultationRequestStatus::pluck('status_name','id');
             //Get All status

        $filter->equal('doctor_id', __('Doctor'))->select($doctors);
        $filter->equal('patient_id', __('Patient'))->select($customers);
        $filter->equal('status', __('Status'))->select($consultation_request_statuses);

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
        $show = new Show(ConsultationRequest::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('patient_id', __('Patient id'));
        $show->field('doctor_id', __('Doctor id'));
        $show->field('total', __('Total'));
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
        $form = new Form(new ConsultationRequest());
        $consultation_request_statuses = ConsultationRequestStatus::pluck('status_name','id');
        $doctors = Doctor::pluck('doctor_name','id');

        $form->text('patient_id', __('Patient'))->rules(function ($form) {
            return 'required';
        });
        $form->select('doctor_id', __('Doctor'))->options($doctors)->rules(function ($form) {
            return 'required';
        });
        $form->decimal('total', __('Total'))->rules(function ($form) {
            return 'required';
        });
        $form->select('status', __('Status'))->options($consultation_request_statuses)->rules(function ($form) {
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
