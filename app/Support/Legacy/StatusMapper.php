<?php

namespace App\Support\Legacy;

use App\Enums\DiscountType;
use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Enums\QuestionType;
use App\Support\Money;
use Illuminate\Support\Str;

/**
 * Pure mapping helpers for the Attendize -> new schema migration.
 *
 * Nothing here touches the legacy database so the behaviour can be unit tested
 * in isolation (see tests/Unit/LegacyStatusMapperTest.php).
 */
class StatusMapper
{
    /**
     * Map a legacy `order_statuses.name` onto an OrderStatus.
     *
     * Legacy values in production: Completed, Refunded, Partially Refunded,
     * Cancelled, Awaiting Payment. Unknown values fall back to completed when
     * the order looks paid, otherwise reserved.
     */
    public static function orderStatus(?string $legacyName, bool $paid = false): OrderStatus
    {
        $name = Str::of((string) $legacyName)
            ->lower()
            ->replaceMatches('/[^a-z]+/', ' ')
            ->trim()
            ->value();

        if ($name === '') {
            return $paid ? OrderStatus::Completed : OrderStatus::Reserved;
        }

        return match (true) {
            str_contains($name, 'refund') => OrderStatus::Refunded,
            str_contains($name, 'cancel') => OrderStatus::Cancelled,
            str_contains($name, 'complete') => OrderStatus::Completed,
            str_contains($name, 'await'), str_contains($name, 'pending') => OrderStatus::Reserved,
            str_contains($name, 'paid') => OrderStatus::Completed,
            str_contains($name, 'reserv') => OrderStatus::Reserved,
            default => $paid ? OrderStatus::Completed : OrderStatus::Reserved,
        };
    }

    public static function eventStatus(mixed $isLive): EventStatus
    {
        $live = filter_var($isLive, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;

        return $live ? EventStatus::Live : EventStatus::Draft;
    }

    /**
     * Convert a legacy decimal price string ("12.50", "£1,234.50", " 7 ") into
     * integer minor units. Anything unusable becomes 0 because the new columns
     * are unsigned integers.
     */
    public static function priceToMinor(int|float|string|null $value, string $currency = 'GBP'): int
    {
        $numeric = self::numericString($value);

        if ($numeric === null) {
            return 0;
        }

        return max(0, Money::toMinor($numeric, $currency));
    }

    /**
     * Map a legacy `question_types.alias` onto a QuestionType.
     */
    public static function questionType(?string $alias, bool $hasOptions = false, bool $allowMultiple = false): QuestionType
    {
        $alias = Str::of((string) $alias)->lower()->trim()->value();

        return match (true) {
            in_array($alias, ['checkbox', 'dropdown_multiple', 'multiselect', 'multi_select', 'multi-select'], true) => QuestionType::Checkbox,
            in_array($alias, ['dropdown', 'select', 'radio'], true) => QuestionType::Select,
            in_array($alias, ['text', 'textarea', 'string'], true) => QuestionType::Text,
            $allowMultiple => QuestionType::Checkbox,
            $hasOptions => QuestionType::Select,
            default => QuestionType::Text,
        };
    }

    /**
     * Decide whether a legacy access code behaved as a fixed amount or a
     * percentage discount.
     */
    public static function discountType(?string $usageRequirements = null, int|float|string|null $amount = null): DiscountType
    {
        $requirements = Str::of((string) $usageRequirements)->lower()->value();
        $hasPercentKeyword = str_contains($requirements, 'percent');

        if (str_contains($requirements, 'fixed') || str_contains($requirements, 'flat')) {
            return DiscountType::Fixed;
        }

        if (str_contains($requirements, 'amount') && ! $hasPercentKeyword) {
            return DiscountType::Fixed;
        }

        if ($hasPercentKeyword) {
            return DiscountType::Percentage;
        }

        $raw = trim((string) $amount);

        if (str_ends_with($raw, '%')) {
            return DiscountType::Percentage;
        }

        $numeric = self::numericString($amount);

        // A "percentage" above 100 is only sensible as a fixed money amount.
        if ($numeric !== null && (float) $numeric > 100) {
            return DiscountType::Fixed;
        }

        return DiscountType::Percentage;
    }

    /**
     * Convert the legacy access-code amount into the integer `value` column:
     * whole percent for percentage codes, minor units for fixed codes.
     */
    public static function discountValue(DiscountType $type, int|float|string|null $amount, string $currency = 'GBP'): int
    {
        return match ($type) {
            DiscountType::Percentage => max(0, min(100, (int) round((float) (self::numericString($amount) ?? 0)))),
            DiscountType::Fixed => self::priceToMinor($amount, $currency),
        };
    }

    /**
     * @param  array<string, mixed>  $row  Legacy event_access_codes row (or equivalent).
     * @return array{type: DiscountType, value: int}
     */
    public static function discountFromAccessCode(array $row, string $currency = 'GBP'): array
    {
        $amount = $row['amounts'] ?? $row['amount'] ?? null;
        $type = self::discountType($row['usage_requirements'] ?? $row['usage_requirement'] ?? null, $amount);

        return [
            'type' => $type,
            'value' => self::discountValue($type, $amount, $currency),
        ];
    }

    /**
     * Strip currency symbols, thousands separators and whitespace, returning a
     * numeric string (or null when there is no usable number).
     */
    private static function numericString(int|float|string|null $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', $value) ?? '';

        return is_numeric($clean) ? $clean : null;
    }
}
