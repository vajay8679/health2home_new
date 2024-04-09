<?php

namespace App\Admin\Controllers;

use App\Models\Hospital;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\DB;

class HospitalController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Hospitals';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Hospital());

        $grid->column('id', __('Id'));
        $grid->column('hospital_name', __('Hospital Name'));
        $grid->column('hospital_logo', __('Hospital Logo'))->image();
        $grid->column('phone_number', __('Phone Number'));
        $grid->column('username', __('User Name'));
        $grid->column('phone_with_code', __('Phone With Code'));
        $grid->column('email', __('Email'));
        $grid->column('address', __('Address'));
        $grid->column('bed_count', __('Bed Count'));
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
        $grid->actions(function ($actions) {
            $actions->disableView();
        });

        $grid->filter(function ($filter) {
            //Get All status

        $filter->like('hospital_name', __('Hospital Name'));
        $filter->like('email', __('Email'));
        $filter->like('phone_number', __('Phone number'));
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
        $show = new Show(Hospital::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('hospital_name', __('Hospital name'));
        $show->field('hospital_logo', __('Hospital logo'));
        $show->field('phone_number', __('Phone number'));
        $show->field('phone_with_code', __('Phone with code'));
        $show->field('email', __('Email'));
        $show->field('username', __('username'));
        $show->field('password', __('Password'));
        $show->field('opening_time', __('opening time'));
        $show->field('closing_time', __('closing time'));
        $show->field('description', __('description'));
        $show->field('bed_count', __('Bed Count'));
        $show->field('address', __('Address'));
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
        $form = new Form(new Hospital());
        $statuses = Status::where('slug','general')->pluck('status_name','id');

        $form->text('hospital_name', __('Hospital Name'))->rules(function ($form) {
            return 'required';
        });
        $form->image('hospital_logo', __('Hospital Logo'))->uniqueName()->move('Hospitals')->rules('required');
        $form->text('phone_number', __('Phone Number'))->rules(function ($form) {
                return 'numeric|digits_between:9,20';
        });
        $form->text('phone_with_code', __('Phone With Code'))->rules(function ($form) {
            return 'required|max:150';
        });
        $form->email('email', __('Email'))->rules(function ($form) {
            return 'required|max:150';
        });
        $form->text('website', __('Website'));
        $form->textarea('address', __('Address'))->rules(function ($form) {
            return 'required';
        });
        $form->text('latitude', __('Latitude'))->rules(function ($form) {
            return 'required';
        });
        $form->text('longitude', __('Longitude'))->rules(function ($form) {
            return 'required';
        });
        $form->text('username', __('User Name'))->rules(function ($form) {
            return 'required';
        });
        $form->text('password', __('Password'))->rules(function ($form) {
            return 'required';
        });
        $form->time('opening_time', __('Opening Time'))->default(date('H:i:s'));
        $form->time('closing_time', __('Closing Time'))->default(date('H:i:s'));
        $form->textarea('description', __('Description'))->rules(function ($form) {
            return 'required';
        });
        $form->text('bed_count', __('Bed Count'))->rules(function ($form) {
            return 'numeric|digits_between:1,20000';
        });
        $form->select('type', __('Type'))->options([ "1" => "Hospital", "2" => "Clinic"])->default(1)->rules(function ($form) {
            return 'required';
        });
        $form->select('status', __('Status'))->options($statuses)->default(1)->rules(function ($form) {
            return 'required';
        });
        $form->select('is_recommended', __('Is Recommended'))->options([ 1 => "Yes", 0 => "No"])->default(0)->rules(function ($form) {
            return 'required';
        });
        $form->hidden('admin_user_id')->default(0);
        $form->saving(function ($form) {
            if($form->password && $form->model()->password != $form->password)
            {
                $form->password = $this->getEncryptedPassword($form->password);
                DB::table('admin_users')->where('id',$form->admin_user_id)->update([ 'password' => $form->password ]);
                
            }
            
            if($form->username && $form->model()->username != $form->username)
            {
                DB::table('admin_users')->where('id',$form->admin_user_id)->update([ 'username' => $form->username ]);
                
            }
            
            if($form->hospital_name && $form->model()->hospital_name != $form->hospital_name)
            {
                DB::table('admin_users')->where('id',$form->admin_user_id)->update([ 'name' => $form->hospital_name ]);
                
            }
            
            if(!$form->model()->id){
                $id = DB::table('admin_users')->insertGetId(
                        ['username' => $form->username, 'password' => $form->password, 'name' => $form->hospital_name, 'avatar' => $form->hospital_logo]
                    );

                    DB::table('admin_role_users')->insert(
                        ['role_id' => 2, 'user_id' => $id ]
                    );
                $form->admin_user_id = $id;
            }
        });
        
        $form->saved(function (Form $form) {
            $this->update_hospital_logo($form->admin_user_id,$form->model()->hospital_logo);
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
    
    function update_hospital_logo($id,$avatar){
        DB::table('admin_users')
            ->where('id', $id)
            ->update(['avatar' => $avatar]); 
    }
}


