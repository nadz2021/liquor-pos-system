<?php
declare(strict_types=1);

namespace App\Core;

use App\Controllers\CategoriesController;

final class App {
  public static function run(): void {
    Env::load(__DIR__ . '/../../.env');
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();

    $r = new Router();

    $r->get('/login', 'AuthController@showLogin');
    $r->post('/login', 'AuthController@login');
    $r->post('/logout', 'AuthController@logout');

    $r->get('/', 'PosController@index');
    $r->post('/pos/scan', 'PosController@scan');
    $r->post('/pos/checkout', 'PosController@checkout');

    $r->get('/products', 'ProductsController@index');
    $r->get('/products/create', 'ProductsController@create');
    $r->post('/products/store', 'ProductsController@store');
    $r->get('/products/edit', 'ProductsController@edit');
    $r->post('/products/update', 'ProductsController@update');
    $r->post('/products/toggle', 'ProductsController@toggle');

    $r->get('/categories', 'CategoriesController@index');
    $r->get('/categories/create', 'CategoriesController@create');
    $r->post('/categories/store', 'CategoriesController@store');
    $r->get('/categories/edit', 'CategoriesController@edit');
    $r->post('/categories/update', 'CategoriesController@update');
    $r->post('/categories/delete', 'CategoriesController@delete');

    $r->get('/subcategories', 'SubcategoriesController@index');
    $r->post('/subcategories/store', 'SubcategoriesController@store');
    $r->post('/subcategories/delete', 'SubcategoriesController@delete');
    $r->get('/subcategories/by-category', 'SubcategoriesController@byCategory');

    $r->post('/import/products', 'ImportController@products');
    $r->post('/import/categories', 'ImportController@categories');
    $r->post('/import/subcategories', 'ImportController@subcategories');

    $r->get('/sales', 'SalesController@index');
    $r->get('/sales/show', 'SalesController@show');

    $r->get('/settings', 'SettingsController@index');
    $r->post('/settings/save', 'SettingsController@save');

    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $r->dispatch($_SERVER['REQUEST_METHOD'], $path);
  }
}
