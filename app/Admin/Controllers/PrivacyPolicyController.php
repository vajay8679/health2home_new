<?php

namespace App\Admin\Controllers;

use App\Models\PrivacyPolicy;
use App\Models\UserType;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class PrivacyPolicyController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Privacy Policies';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new PrivacyPolicy);

        $grid->column('id', __('Id'));
        $grid->column('privacy_policy_type_id', __('Privacy Policy Type'))->display(function($policytype){
            $type_name   = UserType::where('id',$policytype)->value('user');
            if ($policytype == 1){
                return "<span class='label label-info'>$type_name</span>";
            }else if ($policytype == 2) {
                return "<span class='label label-warning'>$type_name</span>";
            }else if($policytype == 3){
                return "<span class='label label-danger'>$type_name</span>";
            }else {
                return "<span class='label label-success'>$type_name</span>";
            }
        });
        $grid->column('title', __('Title'));
        $grid->column('description', __('Description'));
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
            $actions->disableDelete();
        });
        $grid->filter(function ($filter) {
            //Get All status
            $statuses = Status::where('slug','general')->pluck('status_name','id');
            $filter->like('title', 'Title');
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
        $show = new Show(PrivacyPolicy::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('title', __('Title'));
        $show->field('description', __('Description'));
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
        $form = new Form(new PrivacyPolicy);
        $statuses = Status::where('slug','general')->pluck('status_name','id');
        $policy_types = UserType::pluck('user', 'id');
        
        $form->select('privacy_policy_type_id', __('Privacy Policy Type'))->options($policy_types)->rules(function ($form) {
            return 'required';
        });
        $form->text('title', __('Title'))->rules(function ($form) {
            return 'required|max:100';
        });
        $form->textarea('description', __('Description'))->rules(function ($form) {
            return 'required';
        });
        $form->select('status', __('Status'))->options($statuses)->default(1)->rules(function ($form) {
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
