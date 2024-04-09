<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {


    $router->get('/', 'HomeController@index')->name('admin.home');
    $router->get('get_sub_category', 'GeneralController@GetSubCategory');
    $router->get('get_vendor_sub_category', 'GeneralController@GetVendorSubCategory');
    $router->get('view_orders/{id}', 'ViewOrderController@index');
    $router->get('view_prescription/{id}', 'ViewOrderController@view_prescription');
    $router->get('view_lab_orders/{id}', 'GeneralModuleController@index');
    $router->get('view_patient_history/{id}', 'GeneralModuleController@patient_histories');
    $router->resource('prescription', PrescriptionController::class);
    $router->resource('blog', BlogController::class);
    $router->resource('notification', NotificationController::class);
    $router->resource('customers', CustomerController::class);
    $router->resource('privacy_policies', PrivacyPolicyController::class);
    $router->resource('doctors', DoctorController::class);
    $router->resource('doctor-earnings', DoctorEarningController::class);
    $router->resource('doctor-wallet-histories', DoctorWalletHistoryController::class);
    $router->resource('doctor-withdrawals', DoctorWithdrawalController::class);
    $router->resource('faq-categories', FaqCategoryController::class);
    $router->resource('faqs', FaqController::class);
    $router->resource('user-types', UserTypeController::class);
    $router->resource('customer_app_settings', CustomerAppSettingController::class);
    $router->resource('doctor_app_settings', DoctorAppSettingController::class);
    $router->resource('services', ServiceController::class);
    $router->resource('app-modules', AppModuleController::class);
    $router->resource('banners', BannerController::class);
    $router->resource('module-banners', ModuleBannerController::class);
    $router->resource('symptoms', SymptomController::class);
    $router->resource('doctor-specialist-categories', DoctorSpecialistCategoryController::class);
    $router->resource('clinic-details', ClinicDetailController::class);
    $router->resource('customer-prescriptions', CustomerPrescriptionController::class);
    $router->resource('customer-prescription-items', CustomerPrescriptionItemController::class);
    $router->resource('payment_modes', PaymentModeController::class);
    $router->resource('payment-types', PaymentTypeController::class);
    $router->resource('customer-wallet-histories', CustomerWalletHistoryController::class);
    $router->resource('doctor-booking-settings', DoctorBookingSettingController::class);
    $router->resource('doctor-documents', DoctorDocumentController::class);
    $router->resource('consultation-requests', ConsultationRequestController::class);
    $router->resource('consultation-request-statuses', ConsultationRequestStatusController::class);
    $router->resource('clinic-details', ClinicDetailController::class);
    $router->resource('hospital-department', HospitalDepartmentController::class);
    $router->resource('hospital-facility', HospitalFacilityController::class);
    $router->resource('booking-requests', BookingRequestController::class);
    $router->resource('booking-request-statuses', BookingRequestStatusController::class);
    $router->resource('clinic-details', ClinicDetailController::class);
    $router->resource('doctor-commissions', DoctorCommissionController::class);
    $router->resource('statuses', StatusController::class);
    $router->resource('doctor-booking-settings', DoctorBookingSettingController::class);
    $router->resource('fcm-notifications', FcmNotificationController::class);
    $router->resource('delivery_boys', DeliveryBoyController::class);
    $router->resource('vendors', VendorController::class);
    $router->resource('vendor-documents', VendorDocumentController::class);
    $router->resource('vendor-withdrawals', VendorWithdrawalController::class);
    $router->resource('vendor-earnings', VendorEarningController::class);
    $router->resource('vendor-ratings', VendorRatingController::class);
    $router->resource('vendor-wallet-histories', VendorWalletHistoryController::class);
    $router->resource('delivery_boy_app_settings', DeliveryBoyAppSettingController::class);
    $router->resource('vendor_app_settings', VendorAppSettingController::class);
    $router->resource('categories', CategoryController::class);
    $router->resource('unit_measurements', UnitMeasurementController::class);
    $router->resource('sub_categories', SubCategoryController::class);
    $router->resource('products', ProductController::class);
    $router->resource('orders', OrderController::class);
    $router->resource('order_reports', OrderReportController::class);
    $router->resource('product-types', ProductTypeController::class);
    $router->resource('vendor-promo-codes', VendorPromoCodeController::class);
    $router->resource('lab-promo-codes', LabPromoCodeController::class);
    $router->resource('promo-types', PromoTypeController::class);
    $router->resource('taxes', TaxController::class);
    $router->resource('order-statuses', OrderStatusController::class);
    $router->resource('hospital-doctors', HospitalDoctorController::class);
    $router->resource('hospitals', HospitalController::class);
    $router->resource('hospital-galleries', HospitalGalleryController::class);
    $router->resource('hospital-insurances', HospitalInsuranceController::class);
    $router->resource('insurances', InsuranceController::class);
    $router->resource('hospital_fee_settings', HospitalFeeSettingController::class);
    $router->resource('hospital-patients', HospitalPatientController::class);
    $router->resource('hospital-bank-details', HospitalBankDetailController::class);
    $router->resource('doctor-bank-details', DoctorBankDetailController::class);
    $router->resource('hospital-earnings', HospitalEarningController::class);
    $router->resource('hospital-wallet-histories', HospitalWalletHistoryController::class);
    $router->resource('hospital-pharmacies', HospitalPharmacyController::class);
    $router->resource('hospital-services', HospitalServiceController::class);
    $router->resource('hospital-laboratories', HospitalLaboratoryController::class);
    $router->resource('laboratories', LaboratoryController::class);
    $router->resource('lab-earnings', LabEarningController::class);
    $router->resource('lab-packages', LabPackageController::class);
    $router->resource('lab-process-steps', LabProcessStepController::class);
    $router->resource('lab-relevances', LabRelevanceController::class);
    $router->resource('lab-wallet-histories', LabWalletHistoryController::class);
    $router->resource('lab-withdrawals', LabWithdrawalController::class);
    $router->resource('laboratory-app-settings', LaboratoryAppSettingController::class);
    $router->resource('lab-orders', LabOrderController::class);
    $router->resource('collective-people', LabCollectivePeopleController::class);
    $router->resource('lab-services', LabServiceController::class);
    $router->resource('lab-tags', LabTagController::class);
    $router->resource('lab-xrays', LabXrayController::class);
    $router->resource('xray-orders', XrayOrderController::class);
    $router->resource('xray-order-statuses', XrayOrderStatusController::class);
    $router->resource('commission_settings', CommissionSettingController::class);
    $router->resource('import-excels', ImportExcelController::class);
    $router->resource('hospital-app-settings', HospitalAppSettingController::class);
    $router->resource('hospital-withdrawals', HospitalWithdrawalController::class);
    $router->resource('forum_questions', ForumQuestionController::class);
    $router->resource('forum_answers', ForumAnswerController::class);
    $router->resource('languages', LanguageController::class);
    $router->resource('hospital-bed', BedController::class);
    $router->resource('hospital-bed-status', BedStatusController::class);
});







