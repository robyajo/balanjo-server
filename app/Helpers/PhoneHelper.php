<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * Format phone number to Indonesian format (62xxx)
     * 
     * @param string|null $phone
     * @return string|null
     */
    public static function formatToIndonesian(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If phone is empty after cleaning, return null
        if (empty($phone)) {
            return null;
        }

        // If starts with 0, replace with 62
        if (strpos($phone, '0') === 0) {
            return '62' . substr($phone, 1);
        }

        // If starts with 62, return as is
        if (strpos($phone, '62') === 0) {
            return $phone;
        }

        // If starts with +62, remove +
        if (strpos($phone, '+62') === 0) {
            return substr($phone, 1);
        }

        // Otherwise, add 62 prefix
        return '62' . $phone;
    }

    /**
     * Format phone number to local format (0xxx)
     * 
     * @param string|null $phone
     * @return string|null
     */
    public static function formatToLocal(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 62, replace with 0
        if (strpos($phone, '62') === 0) {
            return '0' . substr($phone, 2);
        }

        // If starts with 0, return as is
        if (strpos($phone, '0') === 0) {
            return $phone;
        }

        // Otherwise, add 0 prefix
        return '0' . $phone;
    }

    /**
     * Validate Indonesian phone number
     * 
     * @param string|null $phone
     * @return bool
     */
    public static function isValidIndonesian(?string $phone): bool
    {
        if (empty($phone)) {
            return false;
        }

        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Indonesian phone numbers typically 10-13 digits (with country code)
        // Starting with 62 or 0
        return preg_match('/^(62|0)[0-9]{9,12}$/', $phone) === 1;
    }
}
