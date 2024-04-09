<?php

namespace App\Admin\Controllers;

use App\Models\Status;
use App\Models\Vendor;
use App\Models\ImportExcel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Maatwebsite\Excel\Facades\Excel;

use App\Models\Category;
use App\Models\ProductType;
use App\Models\SubCategory;
use App\Models\Brand;
use App\Models\UnitMeasurement;
use App\Models\Product;
use Admin;
use ZipArchive;

class ImportExcelController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Import Excels';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new ImportExcel());

         if(Admin::user()->isRole('vendor')){
            $grid->model()->where('vendor_id', Vendor::where('admin_user_id',Admin::user()->id)->value('id'));
        }
        $grid->column('id', __('Id'));
        $grid->column('vendor_id', __('Vendor'))->display(function($vendor){
            $vendor = Vendor::where('id',$vendor)->value('store_name');
                return $vendor;
        });
        $grid->column('file', __('File'));
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
            $vendors = Vendor::pluck('owner_name', 'id');
            $statuses = Status::pluck('status_name', 'id');

            $filter->equal('vendor_id', 'Vendor Id')->select($vendors);
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
        $show = new Show(ImportExcel::findOrFail($id));

        $show->field('id', __('Id'));
        $show->field('vendor_id', __('Vendor id'));
        $show->field('file', __('File'));
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
        $form = new Form(new ImportExcel());

        $vendors = Vendor::pluck('owner_name', 'id');
        $statuses = Status::pluck('status_name', 'id');


        if(Admin::user()->isRole('vendor')){
            $form->hidden('vendor_id')->value(Vendor::where('admin_user_id',Admin::user()->id)->value('id'));
        }else{
            $form->select('vendor_id', __('Vendor Id'))->options($vendors)->rules(function ($form) {
                return 'required';
            });
        }
        $form->file('file', __('File'))->rules(function ($form) {
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
        $form->saved(function (Form $form) {
            $form->model()->id;
            $this->importExcel($form->model()->file,$form->model()->vendor_id);
        });
        return $form;
    }
    
        public function importExcel($file,$vendor_id)
    {

        $path = "uploads/".$file;
        $import = new ImportExcel;
        $rows = Excel::toArray(new ImportExcel, $path); 
        echo "<pre>";
        $data = $rows[0];
       
        if(!empty($data) && count($data)){
            ini_set('user_agent','Mozilla/4.0 (compatible; MSIE 6.0)'); 
            
            $time = time();
            $zip_name = 'uploads/'.$time.".zip";
            $img_data = [];
            foreach ($data as $key => $value) {
                if(!empty($value) && $key != 0 && $value[0]){
                    $value[0] = $this->product_type($value,$vendor_id);
                    $product['category_id'] = $this->category($value,$vendor_id);
                    $product['sub_category_id'] = $this->sub_category($product['category_id'],$value,$vendor_id);
                    $product['brand_id'] = $this->brand($value);
                    $product['unit_id'] = $this->unit_measurement($value);
                    $product['product_name'] = $value[4];
                    $product['description'] = $value[5];
                    $product['slug'] = $value[6];
                    
                    $url = $value[8];
                    // $url = str_replace(' ','%20',$url);
                    
                    $last_path = explode('/',$url);
                    $img = $time.'/'.end($last_path);
                    $img = str_replace(' ','_',$img);
                    
                    array_push($img_data,$url);
                    
                    
                    $product['price'] = $value[7];
                    $product['image'] = $img;
                    $product['marked_price'] = $value[10];
                    $product['discount'] = $value[11];
                    $product['vendor_id'] = $vendor_id;
                    $product['status'] = 1;
                    $this->product($product);
                }
            }
            
            $data = ['https://www.bigbasket.com/media/uploads/p/l/10000025_24-fresho-banana-robusta.jpg', 'https://www.bigbasket.com/media/uploads/p/l/10000152_18-fresho-papaya-medium.jpg'];
       
           $context = stream_context_create(
                array(
                    "http" => array(
                        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
                    )
                )
            );

            $zip = new ZipArchive();
             
            $zip->open($zip_name,  ZipArchive::CREATE);
            foreach ($img_data as $key => $file) {
                    $file_url = str_replace(' ','%20',$file);
                    $file_name = str_replace('%20','_',basename($file_url));

              $zip->addFromString($file_name,  file_get_contents($file_url, false, $context)); 
            }
            $zip->close();
        }

    }
    
    public function product_type($data,$vendor_id){
        
        $product_type = ProductType::where('name',$data[0])->where('vendor_id',$vendor_id)->value('id');
        if($product_type){
            return $product_type;
        }else{
            $product_type = ProductType::create(['name'=> $data[0], 'vendor_id'=> $vendor_id ])->id;
            return $product_type;
        }
    }
    
    public function category($data,$vendor_id){
        
        $category_id = Category::where('category_type',$data[0])->where('category_name',$data[1])->where('vendor_id',$vendor_id)->value('id');
        if($category_id){
            return $category_id;
        }else{
            $category_id = Category::create(['category_type'=> $data[0], 'category_name' => $data[1], 'status' => 1, 'vendor_id'=> $vendor_id ])->id;
            return $category_id;
        }
    }
    
    public function sub_category($category_id, $data, $vendor_id){
        $sub_category_id = SubCategory::where('category_id',$category_id)->where('sub_category_name',$data[2])->where('vendor_id',$vendor_id)->value('id');
        if($sub_category_id){
            return $sub_category_id;
        }else{
            $sub_category_id = SubCategory::create(['category_id'=> $category_id, 'sub_category_name' => $data[2], 'vendor_id'=> $vendor_id, 'status' => 1 ])->id;
            return $sub_category_id;
        }
    }
    
    public function brand($data){
        $brand_id = Brand::where('brand_name',$data[3])->value('id');
        if($brand_id){
            return $brand_id;
        }else{
            $brand_id = Brand::create(['brand_name'=> $data[3] ])->id;
            return $brand_id;
        }
    }
    
    public function unit_measurement($data){
        $unit_id = UnitMeasurement::where('unit',$data[9])->value('id');
        if($unit_id){
            return $unit_id;
        }else{
            $unit_id = UnitMeasurement::create(['unit'=> $data[9], 'status' => 1 ])->id;
            return $unit_id;
        }
    }
    
    public function product($data){
        $product_id = Product::where('category_id',$data['category_id'])->where('sub_category_id',$data['sub_category_id'])->where('brand_id',$data['brand_id'])->where('unit_id',$data['unit_id'])->where('product_name',$data['product_name'])->where('discount',$data['product_name'])->where('vendor_id',$data['vendor_id'])->value('id');
        if($product_id){
            return $product_id;
        }else{
            $product_id = Product::create($data)->id;
            return $product_id;
        }
    }

}
