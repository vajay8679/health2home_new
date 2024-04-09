<?php

namespace App\Admin\Controllers;

use App\Models\Laboratory;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Kreait\Firebase\Database;

class LaboratoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Laboratories';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Laboratory());

        $grid->column('id', __('Id'));
        $grid->column('lab_name', __('Lab Name'));
        $grid->column('lab_image', __('Lab Image'))->image();
        $grid->column('email', __('Email'));
        $grid->column('phone_number', __('Phone Number'));
        $grid->column('phone_with_code', __('Phone With Code'));
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
            $statuses = Status::where('slug','general')->pluck('status_name', 'id');
            
            $filter->like('lab_name', 'lab Name');
            $filter->like('phone_number', 'Phone Number');
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
        $show = new Show(Laboratory::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('lab_name', __('Lab name'));
        $show->field('address', __('Address'));
        $show->field('image', __('Image'));
        $show->field('user_name', __('User name'));
        $show->field('email', __('Email'));
        $show->field('phone_number', __('Phone number'));
        $show->field('phone_with_code', __('Phone with code'));
        $show->field('password', __('Password'));
        $show->field('status', __('Status'));
        $show->field('lat', __('Lat'));
        $show->field('lng', __('Lng'));
        $show->field('earning_percent', __('Earning percent'));
        $show->field('fcm_token', __('Fcm token'));
        $show->field('otp', __('Otp'));
        $show->field('wallet', __('Wallet'));
        $show->field('file', __('File'));
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
        $form = new Form(new Laboratory());
        $statuses = Status::where('slug','general')->pluck('status_name', 'id');

        $form->text('lab_name', __('Lab name'))->rules('required');
        $form->textarea('description', __('Description'))->rules('required');
        $form->textarea('address', __('Address'))->rules('required');
        $form->image('lab_image', __('Lab Image'))->uniqueName()->move('laboratories');
        $form->text('user_name', __('User name'))->rules('required');
        $form->email('email', __('Email'))->rules('required');
        $form->text('phone_number', __('Phone number'))->rules('required');
        $form->text('phone_with_code', __('Phone With Code'))->rules(function ($form) {
            return 'required|max:150';
        });
        $form->password('password', __('Password'))->rules('required');
        $form->text('lat', __('Lat'))->rules('required');
        $form->text('lng', __('Lng'))->rules('required');
        $form->text('lab_commission', __('Lab Commission'))->rules('required');
        $form->file('file', __('File'))->rules('required')->uniqueName()->move('lab_files');
        if(Admin::user()->isAdministrator()){
            $form->select('is_recommended', __('Is Recommended'))->options([ 1 => "Yes", 0 => "No"])->default(0)->rules(function ($form) {
                return 'required';
            });
        }
        $form->select('status', __('Status'))->options($statuses)->default(1)->rules(function ($form) {
            return 'required';
        });
        $form->hidden('admin_user_id')->default(0);
        $form->saving(function ($form) {
            if($form->password && $form->model()->password != $form->password)
            {
                $form->password = $this->getEncryptedPassword($form->password);
            }
             if($form->username && $form->model()->username != $form->username)
            {
                DB::table('admin_users')->where('id',$form->admin_user_id)->update([ 'username' => $form->user_name ]);
                
            }
            
            if($form->lab_name && $form->model()->lab_name != $form->lab_name)
            {
                DB::table('admin_users')->where('id',$form->admin_user_id)->update([ 'name' => $form->lab_name ]);
                
            }
            
            if(!$form->model()->id){
                $id = DB::table('admin_users')->insertGetId(
                        ['username' => $form->user_name, 'password' => $form->password, 'name' => $form->lab_name, 'avatar' => $form->lab_image]
                    );

                    DB::table('admin_role_users')->insert(
                        ['role_id' => 4, 'user_id' => $id ]
                    );
                $form->admin_user_id = $id;
            }
        });
         $form->saved(function (Form $form) {
            $this->update_profile_image($form->admin_user_id,$form->model()->lab_image);
            $this->update_status($form->model()->id,$form->model()->status,$form->model()->lab_name);
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
    
    public function update_status($id,$status,$lab_nme){
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        $newPost = $database
        ->getReference('labs/'.$id)
        ->update([
            'lab_nme' => $lab_nme
        ]);
    }
    public function update_profile_image($id,$avatar){
        DB::table('admin_users')
            ->where('id', $id)
            ->update(['avatar' => $avatar]); 
    }

}
