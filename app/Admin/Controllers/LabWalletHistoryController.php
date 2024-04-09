<?php

namespace App\Admin\Controllers;

use App\Models\LabWalletHistory;
use App\Models\Laboratory;
use App\Models\Status;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Admin;

class LabWalletHistoryController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Lab Wallet Histories';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new LabWalletHistory());
        
        $grid->model()->orderBy('id','desc');
        if(!Admin::user()->isAdministrator()){
            $grid->model()->where('lab_id', Laboratory::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('lab_id', __('Lab'))->display(function($laboratories){
            return Laboratory::where('id',$laboratories)->value('lab_name');
        });
        $grid->column('type', __('Type'))->display(function($type){
            
            if ($type == 1) {
                return "<span class='label label-warning'>Credit</span>";
            }if ($type == 2) {
                return "<span class='label label-success'>Debit</span>";
            } 
        });
        $grid->column('message', __('Message'));
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
            if(!Admin::user()->isAdministrator()){
                $filter->equal('type', 'Type')->select(['1' => 'Credit', '2'=> 'Debit']);
            }else{
                $filter->equal('lab_id', 'Laboratory')->select($laboratories);
                $filter->equal('type', 'Type')->select(['1' => 'Credit', '2'=> 'Debit']);
            }
            
        });

        return $grid;
    }

}
