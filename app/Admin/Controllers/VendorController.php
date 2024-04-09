<?php

namespace App\Admin\Controllers;
use App\Models\Status;
use App\Models\Vendor;
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

class VendorController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Vendor';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Vendor());
        
        $grid->model()->orderBy('id','desc');
        $grid->column('id', __('Id'));
        $grid->column('owner_name', __('Owner Name'));
        $grid->column('store_name', __('Store Name'));
        $grid->column('phone_number', __('Phone Number'));
        $grid->column('email', __('Email'));
        $grid->column('wallet', __('Wallet'));
        $grid->column('overall_ratings', __('Rating'))->display(function($overall_ratings){
            if($overall_ratings){
                return $overall_ratings;
            }else{
                return '---';
            }
            
        });
        $grid->column('profile_picture', __('Profile Picture'))->image();
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
            //$actions->disableEdit();
            $actions->disableDelete();
        });
        $grid->filter(function ($filter) {
            //Get All status
            $statuses = Status::where('slug','general')->pluck('status_name','id');
            
            $filter->like('store_name', 'Store Name');
            $filter->like('owner_name', 'Owner Name');
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
        $show = new Show(Vendor::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('vendor_name', __('Vendor name'));
        $show->field('phone_number', __('Phone number'));
        $show->field('email', __('Email'));
        $show->field('password', __('Password'));
        $show->field('profile_picture', __('Profile picture'));
        $show->field('otp', __('Otp'));
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
        $form = new Form(new Vendor());
        $statuses = Status::where('slug','general')->pluck('status_name','id');
    
        $form->text('store_name', __('Store Name'))->rules(function ($form) {
            return 'required|max:100';
        });
        $form->text('owner_name', __('Owner Name'))->rules(function ($form) {
            return 'required|max:100';
        });
        $form->text('phone_number', __('Phone number'))->rules(function ($form) {
                return 'numeric|digits_between:9,20';
        });
        $form->text('phone_with_code', __('Phone with code'))->rules('required');
        $form->email('email', __('Email'))->rules(function ($form) {
                return 'required|max:100';
        });
        $form->password('password', __('Password'))->rules(function ($form) {
            return 'required';
        });
        $form->image('profile_picture', __('Profile picture'))->uniqueName();
        $form->image('store_image', __('Store Image'))->uniqueName();
        $form->textarea('address', __('Address'))->rules(function ($form) {
            return 'required';
        });
        $form->text('latitude', __('Latitude'))->rules(function ($form) {
            return 'required';
        });
        $form->text('longitude', __('Longitude'))->rules(function ($form) {
            return 'required';
        });
        $form->text('pin_code', __('Pincode'))->rules(function ($form) {
            return 'required';
        });
        $form->image('static_map', __('Static Map'))->uniqueName();
        $form->textarea('manual_address', __('Manual Address'))->rules(function ($form) {
            return 'required';
        });
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
                DB::table('admin_users')->where('id',$form->admin_user_id)->update([ 'password' => $form->password ]);
                
            }
            
            if($form->username && $form->model()->username != $form->username)
            {
                DB::table('admin_users')->where('id',$form->admin_user_id)->update([ 'username' => $form->phone_with_code ]);
                
            }
            
            if($form->first_name && $form->model()->first_name != $form->first_name)
            {
                DB::table('admin_users')->where('id',$form->admin_user_id)->update([ 'first_name' => $form->owner_name ]);
                
            }
            
            if(!$form->model()->id){
                $id = DB::table('admin_users')->insertGetId(
                        ['username' => $form->phone_with_code, 'password' => $form->password, 'name' => $form->owner_name, 'avatar' => $form->profile_picture]
                    );

                    DB::table('admin_role_users')->insert(
                        ['role_id' => 3, 'user_id' => $id ]
                    );
                $form->admin_user_id = $id;
            }
        });
        $form->saved(function (Form $form) {
            $this->update_profile_image($form->admin_user_id,$form->model()->profile_picture);
            $this->update_status($form->model()->id,$form->model()->status);
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
    
    public function update_status($id,$status){
        $factory = (new Factory())->withDatabaseUri(env('FIREBASE_DB'));
        $database = $factory->createDatabase();
        $newPost = $database
        ->getReference('vendors/'.$id)
        ->update([
            'status' => $status,
            'o_stat' => 0,
            'on_stat'=> 0,
        ]);
    }

     function update_profile_image($id,$avatar){
        DB::table('admin_users')
            ->where('id', $id)
            ->update(['avatar' => $avatar]); 
    }


    
}

