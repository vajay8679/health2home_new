<?php

namespace App\Admin\Controllers;
use App\Models\Status;
use App\Models\DoctorSpecialistCategory;
use App\Models\DoctorSpecialistSubCategory;
use App\Models\DoctorProvidingService;
use App\Models\DoctorBookingSetting;
use App\Models\Doctor;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;

class DoctorController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Doctor';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Doctor());
        
        $grid->model()->orderBy('id','desc');
        $grid->column('id', __('Id'));
        $grid->column('doctor_name', __('Doctor Name'));
        $grid->column('experience', __('Experience'));
        $grid->column('specialist', __('Specialist'))->display(function($specialist){
            $specialist = DoctorSpecialistCategory::where('id',$specialist)->value('category_name');
            return "$specialist";
        });
        $grid->column('phone_number', __('Phone Number'));
        $grid->column('email', __('Email'));
        $grid->column('document_approved_status', __('Document Approved Status'))->display(function($status){
            if ($status == 1) {
                return "<span class='label label-info'>Approved</span>";
            }else {
                return "<span class='label label-warning'>Not Approved</span>";
            } 
        });
        $grid->column('profile_status', __('Profile Status'))->display(function($status){
            if ($status == 1) {
                return "<span class='label label-info'>Updated</span>";
            }else {
                return "<span class='label label-warning'>Not updated</span>";
            } 
        });
        $grid->column('online_status', __('Online Status'))->display(function($status){
            if ($status == 1) {
                return "<span class='label label-info'>Online</span>";
            }else {
                return "<span class='label label-warning'>Offline</span>";
            } 
        });
        $grid->column('overall_ratings', __('Rating'))->display(function($overall_ratings){
            if($overall_ratings){
                return $overall_ratings;
            }else{
                return '---';
            }
            
        });
        $grid->column('status', __('Status'))->display(function($status){
            $status_name = Status::where('id',$status)->value('status_name');
            if ($status == 1) {
                return "<span class='label label-success'>$status_name</span>";
            } else {
                return "<span class='label label-danger'>$status_name</span>";
            }
        });

        $grid->disableExport();
        //$grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableDelete();
        });

        $grid->filter(function ($filter) {
            //Get All status
            $statuses = Status::pluck('status_name', 'id');
            

            $filter->like('doctor_name', 'Doctor Name');
            $filter->like('phone_number', 'Phone Number');
            $filter->like('gender', 'Gender');
            $filter->like('email', 'Email');
            $filter->equal('status', 'Status')->select($statuses);
            
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
        $show = new Show(Doctor::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('doctor_name', __('Name'));
        $show->field('qualification', __('Qualification'));
        $show->field('profile_image', __('Profile Image'));
        $show->field('phone_number', __('Phone no'));
        $show->field('gender', __('Gender'));
        $show->field('email', __('Email'));
        $show->field('password', __('Password'));
        $show->field('available_time', __('Available time'));
        $show->field('price_per_conversation', __('Price per conversation'));
        $show->field('overall_rating', __('Overall_rating'));
        $show->field('verification_status', __('Verification status'));
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
        $form = new Form(new Doctor());
        $statuses = Status::pluck('status_name', 'id');
        $specialist = DoctorSpecialistCategory::pluck('category_name', 'id');
        

        $form->text('doctor_name', __('Doctor Name'))->rules(function ($form) {
            return 'required|max:100';
        });
        $form->text('qualification', __('Qualification'))->rules(function ($form) {
            return 'required|max:100';
        });
        $form->text('experience', __('Experience'))->rules(function ($form) {
            return 'required|max:100';
        });
        $form->select('specialist', __('Specialist'))->options($specialist)->rules(function ($form) {
            return 'required';
        });
        $form->textarea('description', __('Description'));
        
        $form->image('profile_image', __('Profile Image'))->uniqueName()->move('doctors');
        $form->text('phone_number', __('Phone Number'))->rules(function ($form) {
            return 'required|max:100';
        });
        $form->text('phone_with_code', __('Phone With Code'))->rules(function ($form) {
            return 'required';
        });
            $form->select('is_recommended', __('Is Recommended'))->options([1 => 'Yes', 0 => 'No'])->rules(function ($form) {
            return 'required';
        });
        $form->select('gender', __('Gender'))->options([1 => 'Male', 2 => 'Female'])->rules(function ($form) {
            return 'required';
        });
        $form->email('email', __('Email'))->rules(function ($form) {
            return 'required|max:100';
        });
        $form->password('password', __('Password'))->rules(function ($form) {
            return 'required';
        });
        $form->select('status', __('Status'))->options(Status::where('slug','general')->pluck('status_name','id'))->rules(function ($form) {
            return 'required';
        });
        $form->saving(function ($form) {
            if($form->password && $form->model()->password != $form->password)
            {
                $form->password = $this->getEncryptedPassword($form->password);
            }
        });
        
        $form->saved(function (Form $form) {
            $this->update_doctor_booking_setting($form->model()->id);
            $this->update_status($form->model()->id,$form->model()->doctor_name);
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
    public function getEncryptedPassword($input, $rounds = 12) {
        $salt = "";
        $saltchars = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9));
        for ($i = 0; $i < 22; $i++) {
            $salt .= $saltchars[array_rand($saltchars)];
        }
        return crypt($input, sprintf('$2y$%2d$', $rounds) . $salt);
    }
    
    public function update_status($id,$doc_nme){
        
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        $newPost = $database
        ->getReference('doctors/'.$id)
        ->update([
            'doc_nme' => $doc_nme,
            'c_id' => 0,
            'c_stat' => 0,
            'on_stat' => 0
        ]);
    }
    
    public function update_doctor_booking_setting($id){
        $exist = DoctorBookingSetting::where('doctor_id',$id)->first();
        if(!is_object($exist)){
            $credential['doctor_id'] = $id;
            $credential['online_booking_status'] = 1;
            $credential['online_booking_fee'] = 100;
            $credential['online_booking_time'] = 15;
            $credential['direct_appointment_status'] = 0;
            $credential['direct_appointment_fee'] = 100;
            $credential['direct_appointment_time'] = 15;
            
            DoctorBookingSetting::create($credential);
            
            $unique_code = 'VET'.str_pad($id,5,"0",STR_PAD_LEFT);
            Doctor::where('id',$id)->update(['unique_code' => $unique_code]);
        }
    }
}
