<?php

namespace App\Admin\Controllers;

use App\Models\BedStatus;
use App\Models\Bed;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Models\Hospital;
use Admin; 

class BedStatusController extends AdminController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $title = 'Bed Status';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new BedStatus());
        
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('hospital_id', Hospital::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('hospital_id', __('Hospital'))->display(function($hospitals){
            $hospitals = Hospital::where('id',$hospitals)->value('hospital_name');
            return $hospitals;
        });
        $grid->column('bed_id', __('Bed'))->display(function($beds){
            $beds = Bed::where('id',$beds)->value('bed_count');
            return $beds;
        });
        $grid->column('status', __('Status'));
        $grid->column('updated_at', __('Updated At'));
        if(Admin::user()->isAdministrator()){
            $grid->disableExport();
            $grid->actions(function ($actions) {
                $actions->disableView();
            });
        }else{
            $grid->disableExport();
            $grid->disableCreation();
            $grid->actions(function ($actions) {
                $actions->disableView();
            });
        }

        $grid->filter(function ($filter) {
        if(Admin::user()->isAdministrator()){
            //Get All status
            $hospitals = Hospital::pluck('hospital_name','id');
            
            $filter->equal('hospital_id', __('Hospital'))->select($hospitals);
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
        $show = new Show(BedStatus::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('hospital_id', __('hospital_id'));
        $show->field('bed_id', __('bed_id'));
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
        $form = new Form(new BedStatus());
        $hospitals = Hospital::pluck('hospital_name','id');
        $beds = Bed::pluck('bed_count','id');
       

        if(!Admin::user()->isAdministrator()){
            $form->select('hospital_id', __('Hospital'))->options($hospitals)->rules(function ($form) {
                return 'required';
            });
            // $form->hidden('hospital_id')->value($hospital_id);
        }else{
            $form->select('hospital_id', __('Hospital'))->options($hospitals)->rules(function ($form) {
            return 'required';
        });
        }
        if(!Admin::user()->isAdministrator()){

            $form->select('bed_id', __('Bed'))->options($beds)->rules(function ($form) {
                return 'required';
            });
            // $form->hidden('bed_id')->value($bed_id);
        }else{
            $form->select('bed_id', __('Bed'))->options($beds)->rules(function ($form) {
            return 'required';
        });
        }
        if (!Admin::user()->isAdministrator()) {
            $form->hidden('status')->value('Empty');
        } else {
            $form->select('status', __('Status'))
                 ->options(['Assigned' => 'Assigned', 'Empty' => 'Empty'])
                 ->rules('required'); 
        }        
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
