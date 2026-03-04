<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CashbookCostCenter: string implements HasLabel, HasColor
{
    // Receipt-side cost centers
    case OpBalance = 'op_balance';
    case Sales = 'sales';
    case DirTransfer = 'dir_transfer';
    case ShippingFee = 'shipping_fee';
    case ServiceFee = 'service_fee';
    case MomoInterest = 'momo_interest';
    case PropertyManagement = 'property_management';
    case Refund = 'refund';
    case ContraReceipt = 'contra_receipt';

    // Expenditure-side cost centers
    case ImportDuty = 'import_duty';
    case ShippingExpenses = 'shipping_expenses';
    case SalariesWages = 'salaries_wages';
    case BankCharges = 'bank_charges';
    case Ssnit = 'ssnit';
    case Paye = 'paye';
    case WithholdingTax = 'withholding_tax';
    case MomoCharges = 'momo_charges';
    case ContraPayment = 'contra_payment';
    case Transportation = 'transportation';
    case Materials = 'materials';
    case Donation = 'donation';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::OpBalance => 'Opening Balance',
            self::Sales => 'Sales',
            self::DirTransfer => 'Director Transfer',
            self::ShippingFee => 'Shipping Fee',
            self::ServiceFee => 'Service Fee',
            self::MomoInterest => 'MOMO Interest Received',
            self::PropertyManagement => 'Property Management',
            self::Refund => 'Refund',
            self::ContraReceipt => 'Contra (Receipt)',
            self::ImportDuty => 'Import Duty Charges',
            self::ShippingExpenses => 'Shipping Expenses',
            self::SalariesWages => 'Salaries & Wages',
            self::BankCharges => 'Bank Charges',
            self::Ssnit => 'SSNIT',
            self::Paye => 'PAYE',
            self::WithholdingTax => 'Withholding Tax',
            self::MomoCharges => 'MOMO Charges',
            self::ContraPayment => 'Contra (Payment)',
            self::Transportation => 'Transportation',
            self::Materials => 'Materials',
            self::Donation => 'Donation',
        };
    }

    public function getColor(): string|array|null
    {
        return $this->isReceipt() ? 'success' : 'danger';
    }

    public function isReceipt(): bool
    {
        return in_array($this, [
            self::OpBalance,
            self::Sales,
            self::DirTransfer,
            self::ShippingFee,
            self::ServiceFee,
            self::MomoInterest,
            self::PropertyManagement,
            self::Refund,
            self::ContraReceipt,
        ]);
    }

    /**
     * Returns the DB column name for this cost center's analysis column.
     */
    public function analysisColumn(): string
    {
        return match ($this) {
            self::OpBalance => 'op_balance',
            self::Sales => 'sales',
            self::DirTransfer => 'dir_transfer',
            self::ShippingFee => 'shipping_fee',
            self::ServiceFee => 'service_fee',
            self::MomoInterest => 'momo_interest',
            self::PropertyManagement => 'property_management',
            self::Refund => 'refund',
            self::ContraReceipt => 'contra_receipt',
            self::ImportDuty => 'import_duty',
            self::ShippingExpenses => 'shipping_expenses',
            self::SalariesWages => 'salaries_wages',
            self::BankCharges => 'bank_charges',
            self::Ssnit => 'ssnit',
            self::Paye => 'paye',
            self::WithholdingTax => 'withholding_tax',
            self::MomoCharges => 'momo_charges',
            self::ContraPayment => 'contra_payment',
            self::Transportation => 'transportation',
            self::Materials => 'materials',
            self::Donation => 'donation',
        };
    }

    /**
     * All analysis column names (used to reset before setting one).
     */
    public static function allAnalysisColumns(): array
    {
        return [
            'op_balance', 'sales', 'dir_transfer', 'shipping_fee', 'service_fee',
            'momo_interest', 'property_management', 'refund', 'contra_receipt',
            'import_duty', 'shipping_expenses', 'salaries_wages', 'bank_charges',
            'ssnit', 'paye', 'withholding_tax', 'momo_charges', 'contra_payment',
            'transportation', 'materials', 'donation',
        ];
    }
}
