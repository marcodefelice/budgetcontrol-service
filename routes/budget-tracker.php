<?php

use App\BudgetTracker\Enums\EntryType;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::apiResource('incoming', \App\BudgetTracker\Http\Controllers\IncomingController::class);
Route::apiResource('expenses', \App\BudgetTracker\Http\Controllers\ExpensesController::class);
Route::apiResource('debit', \App\BudgetTracker\Http\Controllers\DebitController::class);
Route::apiResource('transfer', \App\BudgetTracker\Http\Controllers\TransferController::class);
Route::apiResource('planning-recursively', \App\BudgetTracker\Http\Controllers\PlanningRecursivelyController::class);
Route::apiResource('payee', \App\BudgetTracker\Http\Controllers\PayeeController::class);
Route::apiResource('entry', \App\BudgetTracker\Http\Controllers\EntryController::class);


Route::apiResource('categories', \App\BudgetTracker\Http\Controllers\CategoryController::class);
Route::apiResource('accounts', \App\BudgetTracker\Http\Controllers\AccountController::class);
Route::apiResource('labels', \App\BudgetTracker\Http\Controllers\LabelController::class);
Route::apiResource('currencies', \App\BudgetTracker\Http\Controllers\CurrencyController::class);
Route::apiResource('model', \App\BudgetTracker\Http\Controllers\ModelController::class);
Route::apiResource('paymentstype', \App\BudgetTracker\Http\Controllers\PaymentTypeController::class);

Route::post('search', '\App\BudgetTracker\Http\Controllers\SearchEntriesController@find');
Route::get('entry/account/{id}', function (string $id) {
    return \App\BudgetTracker\Http\Controllers\EntryController::getEntriesFromAccount((int) $id);
});

Route::get('stats/month-wallet/{type}/{planned?}', function(string $type, string $planned = '') {

    $planned = $planned === 'planned' ? true : false;

    try {
        $startDate = new DateTime();
        $endDate = new DateTime();
        $stats = new \App\BudgetTracker\Http\Controllers\StatsController();
    
        $stats->setDateStart(
            date('Y/m/d H:i:s',$startDate->modify('first day of this month')->getTimestamp())
        )
        ->setDateEnd(
            date('Y/m/d H:i:s',$endDate->modify('last day of this month')->getTimestamp())
        );
    
        return $stats->$type($planned);

    } catch(Exception $e) {

        return response("Ops an error occured...",500);

    }

});