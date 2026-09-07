<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ClearanceDepartmentController;
use App\Http\Controllers\ClearanceRequestController;
use App\Http\Controllers\GraduationListController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PersonalDetailController;
use App\Http\Controllers\StaffAuthController;
use App\Http\Controllers\StaffClearanceController;
use App\Http\Controllers\StaffStudentController;
use App\Http\Controllers\StaffUserController;
use App\Http\Controllers\BioDataController;
use App\Http\Controllers\BioRegistrationController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseDataController;
use App\Http\Controllers\EducationalDetailsController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\StudentDetailController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Student / payment routes stay public. Staff-only writes and list dumps
| live under auth:sanctum so the student portal is unchanged.
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::delete('/courses/application/{applicationId}', [CourseController::class, 'destroyByApplication']);
Route::apiResource('/courses', CourseController::class);
Route::post('/course-data/upload', [CourseDataController::class, 'upload']);
Route::get('/course-data', [CourseDataController::class, 'index']);

Route::get('/applications', [ApplicationController::class, 'index']);
Route::post('/applications', [ApplicationController::class, 'store']);
Route::post('/school-fees', [ApplicationController::class, 'school_fees']);

Route::post('/student_check', [PersonalDetailController::class, 'find']);
Route::apiResource('personal-details', PersonalDetailController::class);
Route::get('personal-details-paged', [PersonalDetailController::class, 'indexPage']);
Route::get('verify_reference/{reference}', [PersonalDetailController::class, 'reference']);
Route::post('check', [PersonalDetailController::class, 'check']);

Route::apiResource('student-details', controller: StudentDetailController::class);
Route::apiResource('educational-details', EducationalDetailsController::class);
Route::apiResource('bio-registrations', BioRegistrationController::class);

Route::get('/applications/{id}', [ApplicationController::class, 'show']);
Route::put('/applications/{id}', [ApplicationController::class, 'update']);
Route::post('/upload', [FileUploadController::class, 'upload'])->name('file.upload');
Route::post('/multi-upload', [FileUploadController::class, 'multiUpload']);
Route::get('/file/get/{filename}/{visibility?}', [FileUploadController::class, 'getFile'])->name('file.get');

Route::apiResource('bio-data', BioDataController::class);

Route::get('graduation-list/check/{matricNumber}', [GraduationListController::class, 'check'])->where('matricNumber', '.*');

Route::apiResource('clearances', ClearanceRequestController::class);
Route::get('clearances/{clearance}/acceptance-config', [ClearanceRequestController::class, 'acceptanceConfig']);
Route::post('clearances/{clearance}/mark-acceptance-paid', [ClearanceRequestController::class, 'markAcceptancePaid']);

Route::get('clearance-departments', [ClearanceDepartmentController::class, 'index']);

Route::get('/verify-paystack/{reference}', [PaymentController::class, 'verifyTransaction']);
Route::post('/paystack/webhook', [PaymentController::class, 'handleWebhook']);
Route::post('/payments/initiate', [PaymentController::class, 'initiate']);

Route::post('staff/login', [StaffAuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'staff.active'])->group(function () {
    Route::post('staff/logout', [StaffAuthController::class, 'logout']);
    Route::get('staff/me', [StaffAuthController::class, 'me']);
    Route::get('staff/centres', function () {
        return response()->json(['data' => \App\Support\CentreScope::catalogue()]);
    });

    Route::middleware('staff.permission:students.view')->group(function () {
        Route::get('staff/students', [StaffStudentController::class, 'index']);
        Route::get('staff/students/summary', [StaffStudentController::class, 'summary']);
    });

    Route::middleware('staff.permission:students.manage')->group(function () {
        Route::post('admin/minimal-students/check-matric', [PersonalDetailController::class, 'checkMatric']);
        Route::post('admin/minimal-students', [PersonalDetailController::class, 'storeMinimalAdmin']);
        Route::get('import/sample-csv', [PersonalDetailController::class, 'downloadImportSample']);
        Route::post('import/{centre}', [PersonalDetailController::class, 'import']);
    });

    Route::middleware('staff.permission:applications.manage')->group(function () {
        Route::get('approve/{id}', [PersonalDetailController::class, 'approve']);
        Route::post('approve_prence', [BioRegistrationController::class, 'approve_prence']);
    });

    Route::middleware('staff.permission:graduation.view')->group(function () {
        Route::get('graduation-list/unmatched', [GraduationListController::class, 'unmatched']);
        Route::get('graduation-list', [GraduationListController::class, 'index']);
    });

    Route::middleware('staff.permission:graduation.upload')->group(function () {
        Route::get('graduation-list/sample-csv', [GraduationListController::class, 'downloadSample']);
        Route::post('graduation-list/import', [GraduationListController::class, 'import']);
        Route::post('graduation-list', [GraduationListController::class, 'store']);
    });

    Route::middleware('staff.permission:clearance.view')->group(function () {
        Route::get('staff/clearances', [StaffClearanceController::class, 'index']);
    });

    Route::middleware('staff.permission:clearance.start')->group(function () {
        Route::post('staff/clearances/start', [StaffClearanceController::class, 'start']);
    });

    Route::middleware('staff.permission:clearance.approve')->group(function () {
        Route::post('clearances/{clearance}/approve', [ClearanceRequestController::class, 'approve']);
        Route::post('clearances/{clearance}/reject', [ClearanceRequestController::class, 'reject']);
        Route::post('clearances/{clearance}/departments/{departmentId}', [ClearanceRequestController::class, 'updateDepartmentStatus']);
        Route::post('clearance-departments', [ClearanceDepartmentController::class, 'store']);
        Route::put('clearance-departments/{clearance_department}', [ClearanceDepartmentController::class, 'update']);
        Route::delete('clearance-departments/{clearance_department}', [ClearanceDepartmentController::class, 'destroy']);
    });

    Route::middleware('staff.permission:staff.manage')->group(function () {
        Route::get('staff/users', [StaffUserController::class, 'index']);
        Route::post('staff/users', [StaffUserController::class, 'store']);
        Route::put('staff/users/{user}', [StaffUserController::class, 'update']);
    });

    Route::middleware('staff.permission:payments.view')->group(function () {
        Route::get('admin/payments', [PaymentController::class, 'adminIndex']);
        Route::get('admin/payments/{reference}', [PaymentController::class, 'adminShow']);
        Route::post('admin/payments/{reference}/reverify', [PaymentController::class, 'adminReverify']);
    });
});
