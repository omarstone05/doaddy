<?php

namespace App\Modules\SmartInvoice\Policies;

use App\Modules\SmartInvoice\Models\DigitaxCredential;
use Modules\User\Models\User;

class DigitaxCredentialPolicy
{
    /**
     * Determine if user can view credentials
     */
    public function view(User $user, DigitaxCredential $credential): bool
    {
        return $user->organization_id === $credential->organization_id;
    }

    /**
     * Determine if user can update credentials
     */
    public function update(User $user, DigitaxCredential $credential): bool
    {
        return $user->organization_id === $credential->organization_id &&
               $user->hasPermissionTo('manage_digitax_credentials');
    }

    /**
     * Determine if user can delete credentials
     */
    public function delete(User $user, DigitaxCredential $credential): bool
    {
        return $user->organization_id === $credential->organization_id &&
               $user->hasPermissionTo('manage_digitax_credentials');
    }

    /**
     * Determine if user can create credentials
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_digitax_credentials');
    }
}
