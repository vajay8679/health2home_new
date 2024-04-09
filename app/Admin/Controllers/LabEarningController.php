<?php

namespace App\Admin\Controllers;

use App\Models\LabEarning;
use App\Models\LabOrder;
use App\Models\Laboratory;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;

class LabEarningController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Lab Earnings';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LabEarning());
        $grid->model()->orderBy('id','desc');
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('lab_id', Laboratory::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('order_id', __('Order Id'))->display(function($order_id){
            return LabOrder::where('id',$order_id)->value('id');
        });
        $grid->column('lab_id', __('Lab'))->display(function($laboratories_id){
            return Laboratory::where('id',$laboratories_id)->value('lab_name');
        });
        $grid->column('amount', __('Amount'));

        $grid->disableExport();
        $grid->disableCreation();
        $grid->actions(function ($actions) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
        });

        $grid->filter(function ($filter) {
            //Get All status
            $laboratories = Laboratory::pluck('lab_name', 'id');
            $orders = LabOrder::pluck('id','id');
            if(Admin::user()->isAdministrator()){
                $filter->like('lab_order_id', 'Lab Orders')->select($orders);
                $filter->like('laboratories', 'Laboratories')->select($laboratories);
            }
            
        });


        return $grid;
    }

}
