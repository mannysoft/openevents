<?php

namespace Tests\Unit;

use App\Enums\DiscountType;
use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Enums\QuestionType;
use App\Support\Legacy\StatusMapper;
use Tests\TestCase;

class LegacyStatusMapperTest extends TestCase
{
    public function test_it_maps_the_legacy_order_status_names(): void
    {
        $this->assertSame(OrderStatus::Completed, StatusMapper::orderStatus('Completed'));
        $this->assertSame(OrderStatus::Refunded, StatusMapper::orderStatus('Refunded'));
        $this->assertSame(OrderStatus::Refunded, StatusMapper::orderStatus('Partially Refunded'));
        $this->assertSame(OrderStatus::Cancelled, StatusMapper::orderStatus('Cancelled'));
        $this->assertSame(OrderStatus::Reserved, StatusMapper::orderStatus('Awaiting Payment'));
    }

    public function test_it_ignores_case_and_padding_in_order_status_names(): void
    {
        $this->assertSame(OrderStatus::Completed, StatusMapper::orderStatus('  COMPLETED  '));
        $this->assertSame(OrderStatus::Cancelled, StatusMapper::orderStatus('cancelled'));
    }

    public function test_unknown_order_statuses_fall_back_to_paid_state(): void
    {
        $this->assertSame(OrderStatus::Completed, StatusMapper::orderStatus('Something new', paid: true));
        $this->assertSame(OrderStatus::Reserved, StatusMapper::orderStatus('Something new', paid: false));
        $this->assertSame(OrderStatus::Completed, StatusMapper::orderStatus(null, paid: true));
        $this->assertSame(OrderStatus::Reserved, StatusMapper::orderStatus(null, paid: false));
        $this->assertSame(OrderStatus::Reserved, StatusMapper::orderStatus(''));
    }

    public function test_it_maps_is_live_to_an_event_status(): void
    {
        $this->assertSame(EventStatus::Live, StatusMapper::eventStatus(1));
        $this->assertSame(EventStatus::Live, StatusMapper::eventStatus('1'));
        $this->assertSame(EventStatus::Live, StatusMapper::eventStatus(true));
        $this->assertSame(EventStatus::Draft, StatusMapper::eventStatus(0));
        $this->assertSame(EventStatus::Draft, StatusMapper::eventStatus('0'));
        $this->assertSame(EventStatus::Draft, StatusMapper::eventStatus(null));
    }

    public function test_it_converts_legacy_price_strings_to_minor_units(): void
    {
        $this->assertSame(1250, StatusMapper::priceToMinor('12.50', 'GBP'));
        $this->assertSame(1250, StatusMapper::priceToMinor('£12.50', 'GBP'));
        $this->assertSame(123456, StatusMapper::priceToMinor('£1,234.56', 'GBP'));
        $this->assertSame(3000, StatusMapper::priceToMinor('30', 'USD'));
        $this->assertSame(3000, StatusMapper::priceToMinor(30, 'USD'));
        $this->assertSame(0, StatusMapper::priceToMinor('0.00', 'EUR'));
        $this->assertSame(0, StatusMapper::priceToMinor('  ', 'GBP'));
        $this->assertSame(0, StatusMapper::priceToMinor(null, 'GBP'));
        $this->assertSame(0, StatusMapper::priceToMinor('free', 'GBP'));
    }

    public function test_negative_legacy_prices_are_clamped_to_zero(): void
    {
        $this->assertSame(0, StatusMapper::priceToMinor('-5.00', 'GBP'));
    }

    public function test_it_maps_legacy_question_type_aliases(): void
    {
        $this->assertSame(QuestionType::Text, StatusMapper::questionType('text'));
        $this->assertSame(QuestionType::Text, StatusMapper::questionType('textarea'));
        $this->assertSame(QuestionType::Select, StatusMapper::questionType('dropdown'));
        $this->assertSame(QuestionType::Select, StatusMapper::questionType('radio'));
        $this->assertSame(QuestionType::Checkbox, StatusMapper::questionType('checkbox'));
        $this->assertSame(QuestionType::Checkbox, StatusMapper::questionType('dropdown_multiple'));
    }

    public function test_unknown_question_types_use_the_option_flags(): void
    {
        $this->assertSame(QuestionType::Text, StatusMapper::questionType('mystery'));
        $this->assertSame(QuestionType::Select, StatusMapper::questionType('mystery', hasOptions: true));
        $this->assertSame(QuestionType::Checkbox, StatusMapper::questionType('mystery', hasOptions: true, allowMultiple: true));
        $this->assertSame(QuestionType::Text, StatusMapper::questionType(null));
    }

    public function test_it_detects_fixed_amount_access_codes(): void
    {
        $this->assertSame(DiscountType::Fixed, StatusMapper::discountType('fixed_amount', '10'));
        $this->assertSame(DiscountType::Fixed, StatusMapper::discountType('flat', '10'));
        $this->assertSame(DiscountType::Fixed, StatusMapper::discountType(null, '250'));
        $this->assertSame(DiscountType::Percentage, StatusMapper::discountType('percentage', '10'));
        $this->assertSame(DiscountType::Percentage, StatusMapper::discountType(null, '20%'));
        $this->assertSame(DiscountType::Percentage, StatusMapper::discountType(null, '10'));
        $this->assertSame(DiscountType::Percentage, StatusMapper::discountType(null, null));
    }

    public function test_it_converts_discount_values(): void
    {
        $this->assertSame(20, StatusMapper::discountValue(DiscountType::Percentage, '20', 'GBP'));
        $this->assertSame(20, StatusMapper::discountValue(DiscountType::Percentage, '20%', 'GBP'));
        $this->assertSame(100, StatusMapper::discountValue(DiscountType::Percentage, '150', 'GBP'));
        $this->assertSame(0, StatusMapper::discountValue(DiscountType::Percentage, null, 'GBP'));
        $this->assertSame(1000, StatusMapper::discountValue(DiscountType::Fixed, '10.00', 'GBP'));
        $this->assertSame(0, StatusMapper::discountValue(DiscountType::Fixed, null, 'GBP'));
    }

    public function test_it_maps_whole_access_code_rows(): void
    {
        $this->assertSame(
            ['type' => DiscountType::Fixed, 'value' => 1500],
            StatusMapper::discountFromAccessCode(['amounts' => '15.00', 'usage_requirements' => 'fixed'], 'GBP'),
        );

        $this->assertSame(
            ['type' => DiscountType::Percentage, 'value' => 25],
            StatusMapper::discountFromAccessCode(['amounts' => '25%'], 'GBP'),
        );

        // Production rows only carry a code and a usage count.
        $this->assertSame(
            ['type' => DiscountType::Percentage, 'value' => 0],
            StatusMapper::discountFromAccessCode(['code' => 'LONDON123', 'usage_count' => 3], 'GBP'),
        );
    }
}
