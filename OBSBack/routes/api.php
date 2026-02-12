<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\AnnouncementController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\IncidenceController;
use App\Http\Controllers\ScreenController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentConceptController;

// RUTAS PÚBLICAS (sin autenticación)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/debug/user/{user}/permissions', [UserController::class, 'getPermissions']); // Temporal para depurar

// RUTAS PROTEGIDAS (requieren token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'index']);
    Route::get('/user/{user}', [UserController::class, 'obtener_usuario']);
    Route::post('/user', [UserController::class, 'ingresar_usuarios']);
    Route::put('/user/{user}', [UserController::class, 'update']);
    Route::patch('/user/{user}/deactivate', [UserController::class, 'deactivate']);
    Route::patch('/user/{user}/activate', [UserController::class, 'activate']);
    Route::delete('/user/{user}', [UserController::class, 'destroy']);
    Route::post('/user/{user}/permissions', [UserController::class, 'savePermissions']);
    Route::get('/user/{user}/permissions', [UserController::class, 'getPermissions']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/me/change-password', [AuthController::class, 'changePassword']);

    // Rutas para Estudiantes
    Route::post('/students', [StudentController::class, 'store']);
    Route::get('/students', [StudentController::class, 'index']);
    Route::put('/students/{student}', [StudentController::class, 'update']);
    Route::delete('/students/{student}', [StudentController::class, 'destroy']);

    // Rutas para Maestros
    Route::post('/teachers', [TeacherController::class, 'store']);
    Route::get('/teachers', [TeacherController::class, 'index']);
    Route::put('/teachers/{teacher}', [TeacherController::class, 'update']);
    Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy']);

    // Rutas para Roles
    Route::get('/roles', [RoleController::class, 'index']);
    Route::get('/roles/screens-hierarchy', [RoleController::class, 'screensHierarchy']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::put('/roles/{role}', [RoleController::class, 'update']);
    Route::delete('/roles/{role}', [RoleController::class, 'destroy']);

    // Rutas para Cargos
    Route::get('/positions', [PositionController::class, 'index']);
    Route::post('/positions', [PositionController::class, 'store']);
    Route::put('/positions/{position}', [PositionController::class, 'update']);
    Route::patch('/positions/{position}/status', [PositionController::class, 'setStatus']);
    Route::delete('/positions/{position}', [PositionController::class, 'destroy']);

    // Matrículas (tabla registro_matricula)
    Route::get('/enrollments', [EnrollmentController::class, 'index']);
    Route::post('/enrollments', [EnrollmentController::class, 'store']);
    Route::put('/enrollments/{enrollment}', [EnrollmentController::class, 'update']);
    Route::patch('/enrollments/{enrollment}/cancel', [EnrollmentController::class, 'cancel']);

    // Empleados
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::post('/employees', [EmployeeController::class, 'store']);
    Route::put('/employees/{employee}', [EmployeeController::class, 'update']);
    Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy']);

    // Rutas para Grados
    Route::get('/grades', [GradeController::class, 'index']);
    Route::post('/grades', [GradeController::class, 'store']);
    Route::put('/grades/{grade}', [GradeController::class, 'update']);
    Route::delete('/grades/{grade}', [GradeController::class, 'destroy']);

    // Notificaciones
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'readOne']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Incidencias (Reportes)
    Route::get('/incidences', [IncidenceController::class, 'index']);
    Route::post('/incidences', [IncidenceController::class, 'store']);
    Route::put('/incidences/{incidence}', [IncidenceController::class, 'update']);
    Route::delete('/incidences/{incidence}', [IncidenceController::class, 'destroy']);

    // Pantallas / Permisos (roles)
    Route::get('/screens', [ScreenController::class, 'index']);
    Route::get('/me/screens', [ScreenController::class, 'myScreens']);
    Route::get('/roles/{role}/screens', [ScreenController::class, 'roleScreens']);
    Route::put('/roles/{role}/screens', [ScreenController::class, 'updateRoleScreens']);

    // Pantallas / Permisos (por usuario - overrides)
    Route::get('/user/{user}/screens', [ScreenController::class, 'userScreens']);
    Route::put('/user/{user}/screens', [ScreenController::class, 'updateUserScreens']);

    // Auditoría / Sesiones (logs)
    Route::get('/audit-logs', [AuditLogController::class, 'index']);

    // Pagos
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    Route::put('/payments/{payment}', [PaymentController::class, 'update']);
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy']);
    Route::post('/payments/{payment}/process', [PaymentController::class, 'processPayment']);
    Route::get('/payments/dashboard/stats', [PaymentController::class, 'getDashboardStats']);
    Route::get('/payments/recent-transactions', [PaymentController::class, 'getRecentTransactions']);

    // Conceptos de pago
    Route::get('/payment-concepts', [PaymentConceptController::class, 'index']);
    Route::post('/payment-concepts', [PaymentConceptController::class, 'store']);
    Route::put('/payment-concepts/{concept}', [PaymentConceptController::class, 'update']);
    Route::delete('/payment-concepts/{concept}', [PaymentConceptController::class, 'destroy']);
});

// ANUNCIO

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::post('/announcements', [AnnouncementController::class, 'store']);
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update']);
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);
});
