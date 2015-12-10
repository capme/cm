<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    return "Micro Bridge Elevania!";
});

$app->get('api/v1/sales_order','SalesOrderController@getOrder');
$app->post('api/v1/sales_order','SalesOrderController@createOrder');


/*
 * Temporary route to generate random key
 */
$app->get('/key', function() {
	return str_random(32);
});
