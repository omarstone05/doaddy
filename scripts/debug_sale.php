<?php

use App\Models\Sale;
use Illuminate\Support\Facades\Schema;

echo "Checking table existence...\n";
if (Schema::hasTable('retail_sales')) {
    echo "Table 'retail_sales' exists.\n";
} else {
    echo "Table 'retail_sales' DOES NOT exist.\n";
}

echo "Checking Sale model table...\n";
$sale = new Sale();
echo "Model table: " . $sale->getTable() . "\n";

echo "Attempting query...\n";
try {
    $count = Sale::count();
    echo "Count: " . $count . "\n";
} catch (\Exception $e) {
    echo "Query failed: " . $e->getMessage() . "\n";
}
