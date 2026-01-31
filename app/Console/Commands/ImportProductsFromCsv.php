<?php

namespace App\Console\Commands;

use App\Models\GoodsAndService;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportProductsFromCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import-csv 
                            {csv_file : Path to the CSV file}
                            {--email= : Email of the user whose organization to import products to}
                            {--type=product : Type of product (product or service)}
                            {--category= : Category for all products}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from a CSV file to a user\'s organization';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $csvFile = $this->argument('csv_file');
        $email = $this->option('email');
        $type = $this->option('type') ?? 'product';
        $category = $this->option('category');

        // Validate type
        if (!in_array($type, ['product', 'service'])) {
            $this->error('Type must be either "product" or "service"');
            return 1;
        }

        // Find user
        if (!$email) {
            $this->error('Email is required. Use --email option.');
            return 1;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User with email {$email} not found.");
            return 1;
        }

        // Get organization
        $organizationId = $user->organizations()->first()?->id;
        if (!$organizationId) {
            $this->error("User {$email} does not belong to any organization.");
            return 1;
        }

        $this->info("Importing products for organization: {$organizationId}");
        $this->info("User: {$user->name} ({$user->email})");

        // Check if file exists
        if (!file_exists($csvFile)) {
            $this->error("CSV file not found: {$csvFile}");
            return 1;
        }

        // Parse CSV
        $rows = [];
        if (($handle = fopen($csvFile, 'r')) !== false) {
            $headers = fgetcsv($handle);
            if (!$headers || empty($headers)) {
                fclose($handle);
                $this->error('CSV file has no headers');
                return 1;
            }

            // Normalize headers
            $normalizedHeaders = [];
            foreach ($headers as $header) {
                $normalized = strtolower(trim($header));
                $normalized = preg_replace('/\s+/', '_', $normalized);
                $normalizedHeaders[] = $normalized;
            }

            while (($data = fgetcsv($handle)) !== false) {
                if (empty(array_filter($data))) {
                    continue;
                }

                $row = [];
                foreach ($normalizedHeaders as $index => $header) {
                    $row[$header] = $data[$index] ?? '';
                }
                $rows[] = $row;
            }
            fclose($handle);
        }

        if (empty($rows)) {
            $this->error('CSV file is empty or has no data rows');
            return 1;
        }

        $this->info("Found " . count($rows) . " rows to process");

        // Import products
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                // Extract data from row - support multiple column name variations
                // CSV columns: Pos, Article, Description, Quantity, Unit, UnitPrice_EUR, Total_EUR
                $article = trim($row['article'] ?? $row['item_no'] ?? $row['itemno'] ?? $row['sku'] ?? '');
                $description = trim($row['description'] ?? '');
                $productName = trim($row['product'] ?? $row['name'] ?? $row['product_name'] ?? '');
                
                // Use Description as name if available, otherwise use Article or productName
                $name = !empty($description) ? $description : (!empty($productName) ? $productName : $article);
                $sku = $article;

                if (empty($name) && empty($sku)) {
                    $skipped++;
                    $this->warn("Row " . ($index + 2) . ": Skipping - no product name or SKU");
                    continue;
                }

                // If name is still empty, use SKU
                if (empty($name)) {
                    $name = $sku;
                }

                // If SKU is empty, use name as SKU
                if (empty($sku)) {
                    $sku = $name;
                }

                // Check if product already exists (by SKU or name)
                $existing = GoodsAndService::where('organization_id', $organizationId)
                    ->where(function ($query) use ($sku, $name) {
                        if (!empty($sku)) {
                            $query->where('sku', $sku);
                        }
                        $query->orWhere('name', $name);
                    })
                    ->first();

                if ($existing) {
                    $skipped++;
                    $this->warn("Row " . ($index + 2) . ": Skipping - product already exists: {$name}");
                    continue;
                }

                // Extract additional data
                $quantity = !empty($row['quantity']) ? (float) $row['quantity'] : 0;
                $unit = trim($row['unit'] ?? 'PCS');
                $unitPrice = !empty($row['unitprice_eur']) ? (float) $row['unitprice_eur'] : 0;
                $costPrice = !empty($row['cost_price']) ? (float) $row['cost_price'] : $unitPrice;
                $sellingPrice = !empty($row['selling_price']) ? (float) $row['selling_price'] : $unitPrice;
                $rowCategory = trim($row['category'] ?? '');
                $rowType = !empty($row['type']) && in_array(strtolower($row['type']), ['product', 'service']) 
                    ? strtolower($row['type']) 
                    : $type;

                // Create product
                GoodsAndService::create([
                    'id' => (string) Str::uuid(),
                    'organization_id' => $organizationId,
                    'name' => $name,
                    'type' => $rowType,
                    'sku' => !empty($sku) ? $sku : null,
                    'description' => !empty($description) ? $description : null,
                    'category' => $rowCategory ?: $category,
                    'cost_price' => $costPrice,
                    'selling_price' => $sellingPrice,
                    'current_stock' => $quantity,
                    'minimum_stock' => 0,
                    'unit' => $unit,
                    'is_active' => true,
                    'track_stock' => $quantity > 0,
                ]);

                $imported++;
                $this->info("Row " . ($index + 2) . ": Imported - {$name} (SKU: {$sku})");
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                $this->error("Row " . ($index + 2) . ": Error - " . $e->getMessage());
            }
        }

        // Summary
        $this->newLine();
        $this->info("=== Import Summary ===");
        $this->info("Imported: {$imported}");
        $this->info("Skipped: {$skipped}");
        $this->info("Errors: " . count($errors));

        if (!empty($errors)) {
            $this->newLine();
            $this->error("Errors encountered:");
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        return 0;
    }
}
