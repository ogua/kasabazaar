<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ExpenditureLedgerType: string implements HasLabel
{
    case Material = 'material';
    case AuditFee = 'audit_fee';
    case CommitteeAllowance = 'committee_allowance';
    case SalariesWages = 'salaries_wages';
    case Ssnit = 'ssnit';
    case Transportation = 'transportation';
    case Workmanship = 'workmanship';
    case BankCharges = 'bank_charges';
    case TelephoneInternet = 'telephone_internet';
    case BoardMeeting = 'board_meeting';
    case Donation = 'donation';
    case Stationary = 'stationary';
    case Depreciation = 'depreciation';
    case ShippingImport = 'shipping_import';
    case PropertyManagement = 'property_management';
    case Consultancy = 'consultancy';
    case WarehouseWip = 'warehouse_wip';
    case FixedAssets = 'fixed_assets';
    case StaffTraining = 'staff_training';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Material => 'Material',
            self::AuditFee => 'Audit Fee',
            self::CommitteeAllowance => 'Committee Allowance',
            self::SalariesWages => 'Salaries & Wages',
            self::Ssnit => 'SSNIT',
            self::Transportation => 'Transportation',
            self::Workmanship => 'Workmanship (WHT)',
            self::BankCharges => 'Bank Charges',
            self::TelephoneInternet => 'Telephone / Internet',
            self::BoardMeeting => 'Board Meeting Allowance',
            self::Donation => 'Donation',
            self::Stationary => 'Stationery',
            self::Depreciation => 'Depreciation',
            self::ShippingImport => 'Shipping / Import Exp.',
            self::PropertyManagement => 'Property Management',
            self::Consultancy => 'Consultancy',
            self::WarehouseWip => 'Warehouse WIP',
            self::FixedAssets => 'Fixed Assets',
            self::StaffTraining => 'Staff Training',
        };
    }

    /**
     * The CashbookCostCenter case that feeds this ledger (null if not directly mapped).
     */
    public function costCenter(): ?string
    {
        return match ($this) {
            self::Material => CashbookCostCenter::Materials->value,
            self::SalariesWages => CashbookCostCenter::SalariesWages->value,
            self::Ssnit => CashbookCostCenter::Ssnit->value,
            self::Transportation => CashbookCostCenter::Transportation->value,
            self::Workmanship => CashbookCostCenter::WithholdingTax->value,
            self::BankCharges => CashbookCostCenter::BankCharges->value,
            self::Donation => CashbookCostCenter::Donation->value,
            self::ShippingImport => CashbookCostCenter::ImportDuty->value,
            default => null,
        };
    }
}
