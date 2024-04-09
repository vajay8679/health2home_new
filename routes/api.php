<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
//customers
Route::post('customer/login', 'App\Http\Controllers\CustomerController@login');
Route::post('customer/check_phone', 'App\Http\Controllers\CustomerController@check_phone');
Route::post('customer/register', 'App\Http\Controllers\CustomerController@register');
Route::post('customer/forget_password', 'App\Http\Controllers\CustomerController@forget_password');
Route::post('customer/reset_password', 'App\Http\Controllers\CustomerController@reset_password');
Route::post('customer/profile_picture', 'App\Http\Controllers\CustomerController@profile_picture');
Route::post('customer/profile_picture_update', 'App\Http\Controllers\CustomerController@profile_picture_update');
Route::post('customer/get_profile', 'App\Http\Controllers\CustomerController@get_profile');
Route::post('customer/profile_update', 'App\Http\Controllers\CustomerController@profile_update');
Route::post('customer/get_payment_mode', 'App\Http\Controllers\CustomerController@get_payment_mode');
Route::post('customer/last_active_address', 'App\Http\Controllers\CustomerController@last_active_address');
Route::post('customer/get_last_active_address', 'App\Http\Controllers\CustomerController@get_last_active_address');
Route::post('customer/add_address', 'App\Http\Controllers\AddressController@add_address');
Route::post('customer/update_address', 'App\Http\Controllers\AddressController@update');
Route::post('customer/all_addresses', 'App\Http\Controllers\AddressController@all_addresses');
Route::post('customer/edit_address', 'App\Http\Controllers\AddressController@edit');
Route::post('customer/delete_address', 'App\Http\Controllers\AddressController@delete');
Route::post('customer/get_promo', 'App\Http\Controllers\CustomerController@get_promo');
Route::post('customer/check_promo', 'App\Http\Controllers\CustomerController@check_promo');
Route::post('customer/get_taxes', 'App\Http\Controllers\CustomerController@get_taxes');
Route::get('customer/get_blog', 'App\Http\Controllers\CustomerController@get_blog');
Route::post('customer/get_module_banners', 'App\Http\Controllers\CustomerController@get_module_banners');
Route::get('customer/test_stripe', 'App\Http\Controllers\CustomerController@test_stripe');
Route::post('customer/stripe_payment', 'App\Http\Controllers\CustomerController@stripe_payment');

//Doctors
Route::post('doctor/login', 'App\Http\Controllers\DoctorController@login');
Route::post('doctor/register', 'App\Http\Controllers\DoctorController@register');
Route::post('doctor/check_phone', 'App\Http\Controllers\DoctorController@check_phone');
Route::post('doctor/profile_update', 'App\Http\Controllers\DoctorController@profile_update');
Route::post('doctor/get_profile', 'App\Http\Controllers\DoctorController@get_profile');
Route::post('doctor/forget_password','App\Http\Controllers\DoctorController@forget_password');
Route::post('doctor/reset_password','App\Http\Controllers\DoctorController@reset_password');
Route::post('doctor/profile_picture', 'App\Http\Controllers\DoctorController@profile_picture');
Route::post('doctor/profile_picture_update', 'App\Http\Controllers\DoctorController@profile_picture_update');
Route::post('doctor/document_upload', 'App\Http\Controllers\DoctorController@document_upload');
Route::post('doctor/document_update', 'App\Http\Controllers\DoctorController@document_update');
Route::post('doctor/document_details', 'App\Http\Controllers\DoctorController@document_details');
Route::post('doctor/wallet_histories', 'App\Http\Controllers\DoctorController@doctor_wallet_histories');
Route::post('doctor/withdrawal', 'App\Http\Controllers\DoctorController@doctor_withdrawal');
Route::post('doctor/withdrawal_request', 'App\Http\Controllers\DoctorController@doctor_withdrawal_request');
Route::post('doctor/earning', 'App\Http\Controllers\DoctorController@doctor_earning');
Route::post('doctor/qualification_update', 'App\Http\Controllers\DoctorController@qualification_update');
Route::post('doctor/change_online_status', 'App\Http\Controllers\DoctorController@change_online_status');
Route::post('doctor/clinic_image_upload', 'App\Http\Controllers\DoctorController@clinic_image_upload');
Route::post('doctor/clinic_image_update', 'App\Http\Controllers\DoctorController@clinic_image_update');
Route::post('doctor/add_clinic_detail', 'App\Http\Controllers\DoctorController@add_clinic_detail');
Route::post('doctor/add_clinic_address', 'App\Http\Controllers\DoctorController@add_clinic_address');
Route::post('doctor/dashboard', 'App\Http\Controllers\DoctorController@dashboard');
Route::post('doctor/booking_setting_update', 'App\Http\Controllers\DoctorController@doctor_booking_setting_update');
Route::post('doctor/get_clinic_details', 'App\Http\Controllers\DoctorController@get_clinic_details');
Route::post('doctor/get_booking_settings', 'App\Http\Controllers\DoctorController@get_booking_settings');
Route::post('doctor/upload', 'App\Http\Controllers\DoctorController@upload');
Route::post('doctor/document_upload', 'App\Http\Controllers\DoctorController@document_upload');
Route::post('doctor/document_update', 'App\Http\Controllers\DoctorController@document_update');
Route::post('doctor/document_details', 'App\Http\Controllers\DoctorController@document_details');
Route::post('doctor/hospital_details', 'App\Http\Controllers\DoctorController@get_hospital_details');
Route::post('doctor/add_bank_details', 'App\Http\Controllers\DoctorController@add_bank_detail');
Route::post('doctor/get_bank_details', 'App\Http\Controllers\DoctorController@get_bank_details');
Route::get('doctor/get_languages', 'App\Http\Controllers\DoctorController@get_languages');

//Doctor Booking Module
Route::post('customer/create_booking', 'App\Http\Controllers\DoctorBookingController@create_booking');
Route::post('customer/hospital_rating', 'App\Http\Controllers\DoctorBookingController@hospital_rating');
Route::post('doctor/customer_booking_rating', 'App\Http\Controllers\DoctorBookingController@customer_booking_rating');
Route::post('doctor/get_bookings', 'App\Http\Controllers\DoctorBookingController@get_doctor_bookings');
Route::post('doctor/get_booking_details', 'App\Http\Controllers\DoctorBookingController@doctor_booking_details');
Route::post('doctor/accept_booking', 'App\Http\Controllers\DoctorBookingController@accept_booking');
Route::post('customer/home', 'App\Http\Controllers\CustomerController@home');
Route::get('customer/get_doctor_categories', 'App\Http\Controllers\DoctorBookingController@get_doctor_categories');
Route::post('customer/get_online_doctors', 'App\Http\Controllers\DoctorBookingController@get_online_doctors');
Route::post('customer/get_nearest_doctors', 'App\Http\Controllers\DoctorBookingController@get_nearest_doctors');
Route::get('doctor/specialist_category', 'App\Http\Controllers\DoctorBookingController@doctor_specialist_category');
Route::post('customer/get_booking_requests', 'App\Http\Controllers\DoctorBookingController@get_customer_booking_requests');
Route::post('customer/get_booking_details', 'App\Http\Controllers\DoctorBookingController@get_customer_booking_details');
Route::post('doctor/booking_status_change', 'App\Http\Controllers\DoctorBookingController@booking_status_change');
Route::post('doctor/create_booking', 'App\Http\Controllers\DoctorBookingController@doctor_create_booking');
Route::post('customer/get_time_slots', 'App\Http\Controllers\DoctorBookingController@get_time_slots');

//Doctor Consultation Module
Route::post('customer/create_consultation', 'App\Http\Controllers\DoctorConsultationController@create_consultation');
Route::post('doctor/consultation_status_change', 'App\Http\Controllers\DoctorConsultationController@consultation_status_change');
Route::post('doctor/get_consultations', 'App\Http\Controllers\DoctorConsultationController@get_doctor_consultations');
Route::post('doctor/get_consultation_details', 'App\Http\Controllers\DoctorConsultationController@doctor_consultation_details');
Route::post('customer/get_consultation_requests', 'App\Http\Controllers\DoctorConsultationController@get_customer_consultation_requests');
Route::post('customer/get_consultation_details', 'App\Http\Controllers\DoctorConsultationController@get_customer_consultation_details');
Route::post('customer/doctor_rating', 'App\Http\Controllers\DoctorConsultationController@doctor_rating');
Route::post('doctor/customer_consultation_rating', 'App\Http\Controllers\DoctorConsultationController@customer_consultation_rating');
Route::post('doctor/get_prescription', 'App\Http\Controllers\DoctorConsultationController@get_prescription');
Route::post('doctor/create_prescription', 'App\Http\Controllers\DoctorConsultationController@create_prescription');
Route::post('doctor/create_prescription_items', 'App\Http\Controllers\DoctorConsultationController@create_prescription_items');
Route::post('doctor/check_consultation', 'App\Http\Controllers\DoctorConsultationController@check_consultation');
Route::post('doctor/continue_consultation', 'App\Http\Controllers\DoctorConsultationController@continue_consultation');
Route::post('customer/get_consultation_time_slots', 'App\Http\Controllers\DoctorConsultationController@get_time_slots');
Route::post('customer/start_call', 'App\Http\Controllers\DoctorConsultationController@start_call');

//faq & privacy policy
Route::post('get_faq', 'App\Http\Controllers\FaqController@get_faq');
Route::post('get_privacy_policy', 'App\Http\Controllers\PrivacyPolicyController@get_privacy_policy');

//app settings
Route::get('customer_app_setting', 'App\Http\Controllers\AppSettingController@customer_app_setting');
Route::get('doctor_app_setting', 'App\Http\Controllers\AppSettingController@doctor_app_setting');
Route::get('delivery_boy_app_setting', 'App\Http\Controllers\AppSettingController@delivery_boy_app_setting');
Route::get('vendor_app_setting', 'App\Http\Controllers\AppSettingController@vendor_app_setting');

//delivery boys
Route::post('delivery_boy/login', 'App\Http\Controllers\DeliveryBoyController@login');
Route::post('delivery_boy/check_phone', 'App\Http\Controllers\DeliveryBoyController@check_phone');
Route::post('delivery_boy/reset_password', 'App\Http\Controllers\DeliveryBoyController@reset_password');
Route::post('delivery_boy/forget_password', 'App\Http\Controllers\DeliveryBoyController@forget_password');
Route::post('delivery_boy/profile_update', 'App\Http\Controllers\DeliveryBoyController@profile_update');
Route::post('delivery_boy/get_profile', 'App\Http\Controllers\DeliveryBoyController@get_profile');
Route::post('delivery_boy/profile_picture', 'App\Http\Controllers\DeliveryBoyController@profile_picture');
Route::post('delivery_boy/profile_picture_update', 'App\Http\Controllers\DeliveryBoyController@profile_picture_update');
Route::post('delivery_boy/change_online_status', 'App\Http\Controllers\DeliveryBoyController@change_online_status');
Route::post('delivery_boy/dashboard', 'App\Http\Controllers\DeliveryBoyController@dashboard');



//vendor
Route::post('vendor/check_phone', 'App\Http\Controllers\VendorController@check_phone');
Route::post('vendor/register','App\Http\Controllers\VendorController@register');
Route::post('vendor/login','App\Http\Controllers\VendorController@login');
Route::post('vendor/profile_update', 'App\Http\Controllers\VendorController@profile_update');
Route::post('vendor/profile_picture', 'App\Http\Controllers\VendorController@profile_picture');
Route::post('vendor/profile_picture_update', 'App\Http\Controllers\VendorController@profile_picture_update');
Route::post('vendor/forget_password', 'App\Http\Controllers\VendorController@forget_password');
Route::post('vendor/reset_password', 'App\Http\Controllers\VendorController@reset_password');
Route::post('vendor/address', 'App\Http\Controllers\VendorController@vendor_address');
Route::post('vendor/earning', 'App\Http\Controllers\VendorController@vendor_earning');
Route::post('vendor/wallet', 'App\Http\Controllers\VendorController@vendor_wallet');
Route::post('vendor/upload', 'App\Http\Controllers\VendorController@upload');
Route::post('vendor/document_upload', 'App\Http\Controllers\VendorController@document_upload');
Route::post('vendor/document_update', 'App\Http\Controllers\VendorController@document_update');
Route::post('vendor/document_details', 'App\Http\Controllers\VendorController@document_details');
Route::post('vendor/withdrawal_request', 'App\Http\Controllers\VendorController@vendor_withdrawal_request');
Route::post('vendor/withdrawal_history', 'App\Http\Controllers\VendorController@vendor_withdrawal_history');
Route::post('vendor/dashboard', 'App\Http\Controllers\VendorController@vendor_dashboard');
Route::post('vendor/detail', 'App\Http\Controllers\VendorController@vendor_detail');
Route::post('vendor/change_online_status', 'App\Http\Controllers\VendorController@change_online_status');

//Vendor Order Module
Route::post('customer/vendor_list', 'App\Http\Controllers\VendorOrderController@vendor_list');
Route::post('customer/vendor_detail', 'App\Http\Controllers\VendorOrderController@vendor_detail');
Route::post('customer/vendor_category', 'App\Http\Controllers\VendorOrderController@vendor_category');
Route::post('customer/vendor_products', 'App\Http\Controllers\VendorOrderController@vendor_products');
Route::post('customer/vendor_sub_category', 'App\Http\Controllers\VendorOrderController@vendor_sub_category');
Route::post('customer/pharmacy_order', 'App\Http\Controllers\VendorOrderController@place_order');
Route::post('customer/vendor_rating', 'App\Http\Controllers\VendorOrderController@vendor_rating');
Route::post('vendor/get_order_list', 'App\Http\Controllers\VendorOrderController@get_vendor_order_list');
Route::post('vendor/get_order_detail', 'App\Http\Controllers\VendorOrderController@get_vendor_order_detail');
Route::post('vendor/order_accept', 'App\Http\Controllers\VendorOrderController@vendor_order_accept');
Route::post('order_status_change', 'App\Http\Controllers\VendorOrderController@order_status_change');
Route::post('customer/get_order_list', 'App\Http\Controllers\VendorOrderController@get_customer_order_list');
Route::post('customer/get_order_detail', 'App\Http\Controllers\VendorOrderController@get_customer_order_detail');
Route::post('delivery_boy/get_new_status', 'App\Http\Controllers\VendorOrderController@get_new_status');
Route::post('delivery_boy/get_order_list', 'App\Http\Controllers\VendorOrderController@get_deliveryboy_order_list');
Route::post('delivery_boy/get_order_detail', 'App\Http\Controllers\VendorOrderController@get_deliveryboy_order_detail');
Route::post('deliveryboy/accept', 'App\Http\Controllers\VendorOrderController@partner_accept');
Route::post('deliveryboy/reject', 'App\Http\Controllers\VendorOrderController@partner_reject');
Route::get('partner_cron', 'App\Http\Controllers\VendorOrderController@partner_cron');
Route::post('customer/upload_prescription', 'App\Http\Controllers\VendorOrderController@upload_prescription');
Route::post('customer/upload_doctor_prescription', 'App\Http\Controllers\VendorOrderController@upload_doctor_prescription');

//laboratories
Route::get('laboratory/app_setting', 'App\Http\Controllers\LaboratoryController@get_app_setting');
Route::post('laboratory/login', 'App\Http\Controllers\LaboratoryController@login');
Route::post('laboratory/check_phone', 'App\Http\Controllers\LaboratoryController@check_phone');
Route::post('laboratory/reset_password', 'App\Http\Controllers\LaboratoryController@reset_password');
Route::post('laboratory/forget_password', 'App\Http\Controllers\LaboratoryController@forget_password');
Route::post('laboratory/profile_update', 'App\Http\Controllers\LaboratoryController@profile_update');
Route::post('laboratory/get_profile', 'App\Http\Controllers\LaboratoryController@get_profile');
Route::post('laboratory/lab_image', 'App\Http\Controllers\LaboratoryController@profile_picture');
Route::post('laboratory/lab_image_update', 'App\Http\Controllers\LaboratoryController@profile_picture_update');
Route::post('laboratory_faq', 'App\Http\Controllers\FaqController@laboratory_faq');
Route::post('laboratory_privacy_policy', 'App\Http\Controllers\PrivacyPolicyController@laboratory_privacy_policy');

//lab order
Route::get('customer/lab_relevance', 'App\Http\Controllers\LabOrderController@get_lab_relevance');
Route::post('customer/lab_list', 'App\Http\Controllers\LabOrderController@get_lab_list');
Route::post('customer/lab_packages', 'App\Http\Controllers\LabOrderController@get_lab_packages');
Route::post('customer/lab_package_detail', 'App\Http\Controllers\LabOrderController@lab_package_detail');
Route::post('customer/lab/place_order', 'App\Http\Controllers\LabOrderController@place_order');
Route::post('customer/lab/get_lab_orders', 'App\Http\Controllers\LabOrderController@get_lab_orders');
Route::post('customer/lab/get_order_detail', 'App\Http\Controllers\LabOrderController@get_order_detail');
Route::post('customer/lab_detail', 'App\Http\Controllers\LabOrderController@lab_detail');
Route::post('customer/xray/place_order', 'App\Http\Controllers\LabOrderController@place_xray_order');
Route::post('customer/get_xray_orders', 'App\Http\Controllers\LabOrderController@get_xray_orders');
Route::post('customer/get_lab_promo', 'App\Http\Controllers\LabOrderController@get_lab_promo');
Route::post('customer/check_lab_promo', 'App\Http\Controllers\LabOrderController@check_lab_promo');
Route::post('get_notifications', 'App\Http\Controllers\CommonController@get_notifications');
Route::post('get_patient_histories', 'App\Http\Controllers\CustomerController@get_patient_histories');

Route::get('e_prescription_download/{id}', 'App\Http\Controllers\DoctorConsultationController@e_prescription_download');

//CRON
Route::get('customer/cr_expiring', 'App\Http\Controllers\DoctorConsultationController@cr_expiring');
Route::get('customer/cr_reminder', 'App\Http\Controllers\DoctorConsultationController@cr_reminder');
// Route::get('customer/cr_reminder', 'App\Http\Controllers\DoctorConsultationController@cr_reminder');

Route::get('customer/hospital_allbed', 'App\Http\Controllers\BedApiController@allBeds');
Route::post('customer/hospital_add_bed', 'App\Http\Controllers\BedApiController@create');
Route::get('customer/hospital_bed_detail/{id}', 'App\Http\Controllers\BedApiController@show');
Route::get('customer/hospital_edit_bed/{id}', 'App\Http\Controllers\BedApiController@edit');
Route::post('customer/hospital_bed_update', 'App\Http\Controllers\BedApiController@update');
Route::delete('customer/hospital_bed_detete/{id}', 'App\Http\Controllers\BedApiController@destroy');



Route::get('customer/hospital_allbed_status', 'App\Http\Controllers\BedStatusApiController@allBedsStatus');
Route::post('customer/hospital_add_bed_status', 'App\Http\Controllers\BedStatusApiController@create');
Route::get('customer/hospital_bed_status_detail/{id}', 'App\Http\Controllers\BedStatusApiController@show');
Route::get('customer/hospital_edit_status_bed/{id}', 'App\Http\Controllers\BedStatusApiController@edit');
Route::post('customer/hospital_bed_status_update', 'App\Http\Controllers\BedStatusApiController@update');
Route::delete('customer/hospital_bedstatus_detete/{id}', 'App\Http\Controllers\BedStatusApiController@destroy');