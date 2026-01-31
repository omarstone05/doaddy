<?php

namespace App\Console\Commands;

use App\Models\GoodsAndService;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReplaceProductsFromCsv extends Command
{
    protected $signature = 'products:replace-csv 
                            {--file= : The path to the CSV file} 
                            {--user= : The email of the user to associate products with} 
                            {--organization= : The ID of the organization to import products for}';
    
    protected $description = 'Delete all existing products for an organization and import new ones from CSV.';

    public function handle()
    {
        $filePath = $this->option('file');
        $userEmail = $this->option('user');
        $organizationId = $this->option('organization');

        if (!$filePath) {
            $this->error('Please provide the --file option with the path to the CSV.');
            return Command::FAILURE;
        }

        if (!file_exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return Command::FAILURE;
        }

        $user = null;
        if ($userEmail) {
            $user = User::where('email', $userEmail)->first();
            if (!$user) {
                $this->error("User with email '{$userEmail}' not found.");
                return Command::FAILURE;
            }
        }

        $organization = null;
        if ($organizationId) {
            $organization = Organization::find($organizationId);
            if (!$organization) {
                $this->error("Organization with ID '{$organizationId}' not found.");
                return Command::FAILURE;
            }
            $organizationId = $organization->id;
        } elseif ($user) {
            $organization = $user->organizations()->first();
            if (!$organization) {
                $this->error("User '{$userEmail}' is not associated with any organization.");
                return Command::FAILURE;
            }
            $organizationId = $organization->id;
        } else {
            $this->error('Please provide either --user or --organization option.');
            return Command::FAILURE;
        }

        $this->info("Organization: {$organization->name} ({$organizationId})");
        if ($user) {
            $this->info("User: {$user->name} ({$user->email})");
        }

        // Confirm deletion
        $productCount = GoodsAndService::where('organization_id', $organizationId)->count();
        $this->warn("This will DELETE {$productCount} existing products for this organization.");
        
        if (!$this->confirm('Are you sure you want to proceed?', true)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        DB::beginTransaction();
        try {
            // Delete all existing products
            $deleted = GoodsAndService::where('organization_id', $organizationId)->delete();
            $this->info("Deleted {$deleted} existing products.");

            // Parse and import CSV
            $importedCount = 0;
            $skippedCount = 0;
            $errors = [];

            if (($handle = fopen($filePath, 'r')) !== false) {
                $header = fgetcsv($handle);
                if (!$header) {
                    $this->error('CSV file is empty or has no headers.');
                    fclose($handle);
                    DB::rollBack();
                    return Command::FAILURE;
                }

                // Normalize headers
                $normalizedHeader = array_map(function ($h) {
                    return Str::slug(trim($h), '_');
                }, $header);

                $this->info('Found headers: ' . implode(', ', $normalizedHeader));

                $rowNumber = 1;
                while (($rowData = fgetcsv($handle)) !== false) {
                    $rowNumber++;
                    if (empty(array_filter($rowData))) {
                        continue; // Skip empty rows
                    }

                    // Ensure rowData has enough elements
                    if (count($rowData) < count($normalizedHeader)) {
                        $rowData = array_pad($rowData, count($normalizedHeader), '');
                    }

                    $data = array_combine($normalizedHeader, $rowData);

                    // Map CSV columns to GoodsAndService model attributes
                    // CSV columns: Pos, Article, Description, Quantity, Unit, UnitPrice_EUR, Total_EUR
                    $article = trim($data['article'] ?? '');
                    $description = trim($data['description'] ?? '');
                    $quantity = !empty($data['quantity']) ? (float) $data['quantity'] : 0;
                    $unit = trim($data['unit'] ?? 'PCS');
                    $unitPrice = !empty($data['unitprice_eur']) ? (float) $data['unitprice_eur'] : 0;
                    $totalPrice = !empty($data['total_eur']) ? (float) $data['total_eur'] : 0;

                    // Use Article as SKU and Description as name
                    // If Description is empty, use Article as name
                    $name = !empty($description) ? $description : $article;
                    $sku = $article;

                    if (empty($name) && empty($sku)) {
                        $errors[] = "Row {$rowNumber}: Both Article and Description are empty. Skipping.";
                        $skippedCount++;
                        continue;
                    }

                    // If name is still empty, use Article
                    if (empty($name)) {
                        $name = $sku;
                    }

                    // If SKU is empty, use name as SKU
                    if (empty($sku)) {
                        $sku = $name;
                    }

                    try {
                        GoodsAndService::create([
                            'id' => (string) Str::uuid(),
                            'organization_id' => $organizationId,
                            'name' => $name,
                            'type' => 'product',
                            'description' => $description,
                            'sku' => $sku,
                            'cost_price' => $unitPrice, // Use unit price as cost price
                            'selling_price' => $unitPrice, // Use unit price as selling price
                            'current_stock' => $quantity,
                            'minimum_stock' => 0,
                            'unit' => $unit,
                            'category' => null,
                            'is_active' => true,
                            'track_stock' => $quantity > 0,
                        ]);
                        $this->info("Row {$rowNumber}: Imported - {$name} (SKU: {$sku})");
                        $importedCount++;
                    } catch (\Exception $e) {
                        $errors[] = "Row {$rowNumber}: Failed to import '{$name}' - " . $e->getMessage();
                        $this->error("Row {$rowNumber}: Failed to import '{$name}' - " . $e->getMessage());
                        $skippedCount++;
                    }
                }
                fclose($handle);
            } else {
                $this->error("Could not open CSV file: {$filePath}");
                DB::rollBack();
                return Command::FAILURE;
            }

            DB::commit();

            $this->info("\n=== Import Summary ===");
            $this->info("Deleted: {$deleted}");
            $this->info("Imported: {$importedCount}");
            $this->info("Skipped: {$skippedCount}");
            $this->info("Errors: " . count($errors));

            if (!empty($errors)) {
                $this->warn("\nErrors:");
                foreach (array_slice($errors, 0, 10) as $error) {
                    $this->warn("  - {$error}");
                }
                if (count($errors) > 10) {
                    $this->warn("  ... and " . (count($errors) - 10) . " more errors");
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to replace products: " . $e->getMessage());
            Log::error('Replace products from CSV failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'organization_id' => $organizationId,
            ]);
            return Command::FAILURE;
        }
    }
}
