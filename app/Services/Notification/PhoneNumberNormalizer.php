<?php

namespace App\Services\Notification;

class PhoneNumberNormalizer
{
    /**
     * Normalize a Pakistani mobile number to raw international format (e.g. 923001234567).
     * Ideal for Pakistani bulk SMS HTTP gateways.
     */
    public static function toInternational(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // If starts with 0092...
        if (str_starts_with($cleaned, '0092')) {
            $cleaned = substr($cleaned, 2);
        }

        // If starts with 03... (11 digits, e.g. 03001234567) -> replace leading 0 with 92
        if (str_starts_with($cleaned, '03') && strlen($cleaned) === 11) {
            $cleaned = '92' . substr($cleaned, 1);
        }

        // If starts with 3... (10 digits, e.g. 3001234567) -> prepend 92
        if (str_starts_with($cleaned, '3') && strlen($cleaned) === 10) {
            $cleaned = '92' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Normalize to standard E.164 format with leading plus (e.g. +923001234567).
     * Ideal for Twilio and WhatsApp Cloud API.
     */
    public static function toE164(string $phone): string
    {
        $intl = self::toInternational($phone);
        return '+' . $intl;
    }

    /**
     * Normalize to standard Pakistani local 11-digit format (e.g. 03001234567).
     */
    public static function toLocal(string $phone): string
    {
        $intl = self::toInternational($phone);
        if (str_starts_with($intl, '92') && strlen($intl) === 12) {
            return '0' . substr($intl, 2);
        }
        return $phone;
    }

    /**
     * Validate whether the number is a valid Pakistani mobile number.
     */
    public static function isValidPakistaniMobile(string $phone): bool
    {
        $intl = self::toInternational($phone);
        // Pakistani mobile is exactly 12 digits starting with 923
        return (bool) preg_match('/^923[0-9]{9}$/', $intl);
    }

    /**
     * Detect the cellular network provider based on mobile number prefix.
     */
    public static function getCarrierName(string $phone): string
    {
        $local = self::toLocal($phone);
        $prefix = substr($local, 0, 4);

        return match ($prefix) {
            '0300', '0301', '0302', '0303', '0304', '0305', '0306', '0307', '0308', '0309' => 'Jazz / Mobilink',
            '0320', '0321', '0322', '0323', '0324', '0325' => 'Warid (Jazz)',
            '0340', '0341', '0342', '0343', '0344', '0345', '0346', '0347', '0348', '0349' => 'Telenor Pakistan',
            '0310', '0311', '0312', '0313', '0314', '0315', '0316', '0317', '0318' => 'Zong / CMPak',
            '0330', '0331', '0332', '0333', '0334', '0335', '0336', '0337' => 'Ufone (PTCL)',
            '0355' => 'Special Communications Organization (SCO)',
            default => 'Pakistani Cellular Network',
        };
    }
}
