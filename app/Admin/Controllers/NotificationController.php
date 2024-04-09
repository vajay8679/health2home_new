<?php

namespace App\Admin\Controllers;

use App\Models\Notification;
use App\Models\AppModule;
use App\Models\Doctor;
use App\Models\Vendor;
use App\Models\Laboratory;
use App\Models\DeliveryBoy;
use App\Models\Customer;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
class NotificationController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Notification';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Notification);

        $grid->column('id', __('Id'));
        $grid->column('title', __('Title'));
        $grid->column('app_module', __('App Module'))->display(function($module){
            $module_name = AppModule::where('id',$module)->value('module_name');
                return "$module_name";
        });
        $grid->column('image', __('Image'))->image();

        $grid->disableExport();
        $grid->disablefilter();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });

        return $grid;
    }


    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Notification);
        $app_modules = AppModule::pluck('module_name', 'id');
        
        $form->select('app_module', __('App Module'))->options($app_modules)->rules(function ($form) {
            return 'required';
        });
         $form->text('title', __('Title'))->rules(function ($form) {
            return 'required';
        });
         $form->text('description', __('Description'))->rules(function ($form) {
            return 'required';
        });
        $form->image('image', __('Image'))->uniqueName()->move('notification');
        $form->saved(function (Form $form) {
            if($form->model()->app_module == 1){
                $tokens = Customer::where('fcm_token','!=',NULL)->pluck('fcm_token')->toArray();
            }else if($form->model()->app_module == 2){
                $tokens = Doctor::where('fcm_token','!=',NULL)->pluck('fcm_token')->toArray();
            }else if($form->model()->app_module == 3){
                $tokens = Vendor::where('fcm_token','!=',NULL)->pluck('fcm_token')->toArray();
            }else if($form->model()->app_module == 4){
                $tokens = Laboratory::where('fcm_token','!=',NULL)->pluck('fcm_token')->toArray();
            }else if($form->model()->app_module == 5){
                $tokens = DeliveryBoy::where('fcm_token','!=',NULL)->pluck('fcm_token')->toArray();
            }
            
            $this->send_bulk_fcm($form->model()->title,$form->model()->description,$tokens);
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
    
    
    public function send_bulk_fcm($title,$description,$tokens){
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);
        $optionBuilder->setPriority("high");
        $notificationBuilder = new PayloadNotificationBuilder($title);
        $notificationBuilder->setBody($description)
                            ->setSound('default')->setBadge(1);
        
        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData(['a_data' => 'my_data']);
        
        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();
        
        $downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);
        
        return $downstreamResponse->numberSuccess();
    }
}
