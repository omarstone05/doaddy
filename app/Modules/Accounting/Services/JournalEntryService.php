<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\JournalEntryLine;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\AccountBalance;
use Illuminate\Support\Facades\DB;

class JournalEntryService
{
    /**
     * Post a journal entry
     */
    public function post(JournalEntry $journalEntry): void
    {
        if ($journalEntry->status === 'posted') {
            throw new \Exception('Journal entry is already posted.');
        }

        // Validate that debits equal credits
        $debits = $journalEntry->lines()->where('type', 'debit')->sum('amount');
        $credits = $journalEntry->lines()->where('type', 'credit')->sum('amount');

        if (abs($debits - $credits) > 0.01) {
            throw new \Exception('Journal entry is not balanced. Debits must equal credits.');
        }

        DB::beginTransaction();
        try {
            // Update journal entry status
            $journalEntry->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);

            // Update account balances
            foreach ($journalEntry->lines as $line) {
                $account = $line->account;
                
                // Update account current balance
                $account->increment('current_balance', $line->type === 'debit' ? $line->amount : -$line->amount);
                
                // Create or update account balance record
                $period = $journalEntry->entry_date->format('Y-m');
                $balance = AccountBalance::firstOrNew([
                    'organization_id' => $journalEntry->organization_id,
                    'account_id' => $account->id,
                    'period' => $period,
                ]);

                if ($line->type === 'debit') {
                    $balance->debit_total = ($balance->debit_total ?? 0) + $line->amount;
                } else {
                    $balance->credit_total = ($balance->credit_total ?? 0) + $line->amount;
                }
                $balance->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reverse a posted journal entry
     */
    public function reverse(JournalEntry $journalEntry, string $reason = null): JournalEntry
    {
        if ($journalEntry->status !== 'posted') {
            throw new \Exception('Only posted journal entries can be reversed.');
        }

        DB::beginTransaction();
        try {
            // Create reversal entry
            $reversal = $journalEntry->replicate();
            $reversal->entry_number = null; // Will be auto-generated
            $reversal->entry_date = now();
            $reversal->description = 'Reversal: ' . ($reason ?? $journalEntry->description);
            $reversal->status = 'draft';
            $reversal->posted_at = null;
            $reversal->posted_by = null;
            $reversal->reversing_entry_id = $journalEntry->id;
            $reversal->save();

            // Create reversed lines (swap debits and credits)
            foreach ($journalEntry->lines as $line) {
                $reversalLine = $line->replicate();
                $reversalLine->journal_entry_id = $reversal->id;
                $reversalLine->type = $line->type === 'debit' ? 'credit' : 'debit';
                $reversalLine->save();
            }

            DB::commit();

            return $reversal;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

