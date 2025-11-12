<?php

namespace App\Services\Addy\Actions;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateInvoiceAction extends BaseAction
{
    public function validate(): bool
    {
        // Accept either customer_id or customer_name
        return isset($this->parameters['customer_id']) || isset($this->parameters['customer_name']);
    }

    public function preview(): array
    {
        $customerName = $this->getCustomerName();
        $totalAmount = $this->parameters['total_amount'] ?? 0;
        $invoiceDate = $this->parameters['invoice_date'] ?? now()->toDateString();
        
        return [
            'title' => 'Create Invoice',
            'description' => "Generate a new invoice for {$customerName} - \${$totalAmount}",
            'items' => [
                [
                    'customer' => $customerName,
                    'amount' => $totalAmount,
                    'date' => $invoiceDate,
                ]
            ],
            'impact' => $totalAmount > 1000 ? 'high' : 'medium',
            'warnings' => [],
        ];
    }

    public function execute(): array
    {
        DB::beginTransaction();
        try {
            // Get or create customer
            $customer = $this->getOrCreateCustomer();
            if (!$customer) {
                throw new \Exception('Could not find or create customer. Please provide a valid customer name.');
            }
            
            // Prepare invoice data
            $invoiceDate = $this->parameters['invoice_date'] ?? now()->toDateString();
            $dueDate = $this->parameters['due_date'] ?? now()->addDays(30)->toDateString();
            $totalAmount = $this->parameters['total_amount'] ?? 0;
            
            // Prepare items - if items array is provided, use it; otherwise create a single item
            $items = $this->parameters['items'] ?? [];
            if (empty($items) && $totalAmount > 0) {
                $items = [[
                    'description' => $this->parameters['description'] ?? 'Invoice item',
                    'quantity' => 1,
                    'unit_price' => $totalAmount,
                ]];
            }
            
            if (empty($items)) {
                throw new \Exception('Invoice must have at least one item. Please provide items or a total amount.');
            }
            
            // Calculate totals
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
            }
            
            $taxAmount = $this->parameters['tax_amount'] ?? 0;
            $discountAmount = $this->parameters['discount_amount'] ?? 0;
            $finalTotal = $subtotal + $taxAmount - $discountAmount;
            
            // Create invoice
            $invoice = Invoice::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $this->organization->id,
                'customer_id' => $customer->id,
                'invoice_date' => $invoiceDate,
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $finalTotal,
                'status' => 'draft',
                'notes' => $this->parameters['notes'] ?? null,
                'terms' => $this->parameters['terms'] ?? null,
            ]);
            
            // Create invoice items
            foreach ($items as $index => $item) {
                InvoiceItem::create([
                    'id' => (string) Str::uuid(),
                    'invoice_id' => $invoice->id,
                    'goods_service_id' => $item['goods_service_id'] ?? null,
                    'description' => $item['description'] ?? 'Invoice item',
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'total' => ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0),
                    'display_order' => $index,
                ]);
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'invoice_id' => $invoice->id,
                'message' => "Invoice created successfully for {$customer->name}.",
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Invoice creation failed', [
                'error' => $e->getMessage(),
                'parameters' => $this->parameters,
            ]);
            throw $e;
        }
    }
    
    protected function getOrCreateCustomer(): ?Customer
    {
        // If customer_id is provided, find it
        if (isset($this->parameters['customer_id'])) {
            return Customer::where('organization_id', $this->organization->id)
                ->find($this->parameters['customer_id']);
        }
        
        // If customer_name is provided, find or create
        if (isset($this->parameters['customer_name'])) {
            $customerName = trim($this->parameters['customer_name']);
            
            // Try to find existing customer
            $customer = Customer::where('organization_id', $this->organization->id)
                ->where('name', 'LIKE', "%{$customerName}%")
                ->first();
            
            if ($customer) {
                return $customer;
            }
            
            // Create new customer
            return Customer::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $this->organization->id,
                'name' => $customerName,
                'status' => 'active',
            ]);
        }
        
        return null;
    }
    
    protected function getCustomerName(): string
    {
        if (isset($this->parameters['customer_name'])) {
            return $this->parameters['customer_name'];
        }
        
        if (isset($this->parameters['customer_id'])) {
            $customer = Customer::find($this->parameters['customer_id']);
            return $customer->name ?? 'Unknown Customer';
        }
        
        return 'Unknown Customer';
    }
}

