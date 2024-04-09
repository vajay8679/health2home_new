<?php

namespace App\Admin\Controllers;

use App\Models\HospitalPatientHistory;
use App\Models\HospitalPatient;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\DB;

class HospitalPatientHistoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hospital Patient Histories';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new HospitalPatientHistory());
        
        $grid->column('id', __('Id'));
        $grid->column('hospital_patient_id', __('Hospital Patient Id'));
        $grid->column('date', __('Date'));
        $grid->column('purpose_of_visit', __('Pupose Of Visit'));
        
        $grid->disableExport();
        $grid->disableFilter();
        $grid->actions(function ($actions) {
                $actions->disableView();
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
        $show = new Show(HospitalPatientHistory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('hospital_id', __('Hospital id'));
        $show->field('doctor_id', __('Doctor id'));
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
        $form = new Form(new HospitalPatientHistory());
        $hospital_patient_id = HospitalPatient::pluck('id','id');
        
        $form->select('hospital_patient_id', __('Hospital Patient Id'))->options($hospital_patient_id)->rules(function ($form) {
            return 'required';
        });
        $form->date('date', __('Date'))->rules('required')->format('YYYY-MM-DD');
        $form->text('purpose_of_visit', __('Purpose Of Visit'))->rules('required');
        
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
