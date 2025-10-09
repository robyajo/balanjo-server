<?php

namespace App\Rules;

use App\Helpers\PhoneHelper;
use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class UniqueIndonesianPhone implements Rule
{
    protected $ignoreId;

    /**
     * Create a new rule instance.
     *
     * @param int|null $ignoreId User ID to ignore (useful for updates)
     */
    public function __construct($ignoreId = null)
    {
        $this->ignoreId = $ignoreId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (empty($value)) {
            return true; // Let nullable validation handle empty values
        }

        // Format phone to Indonesian format
        $formattedPhone = PhoneHelper::formatToIndonesian($value);

        if (empty($formattedPhone)) {
            return false;
        }

        // Check if phone exists in database
        $query = User::where('phone', $formattedPhone);

        // Ignore specific user ID (useful for update operations)
        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        return !$query->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Phone number is already registered.';
    }
}
