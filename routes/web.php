<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () { 
    return response()->json([
        'name' => 'Juicebox API', 'documentation' => 'README.md and docs/postman/Juicebox.postman_collection.json']
    );
});
