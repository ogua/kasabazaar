<?php

namespace App\Service;

use App\Models\Staff;
use App\Models\PayrollPeriod;
use App\Models\PayrollEntry;
use App\Enums\EmploymentStatus;
use App\Enums\PayrollStatus;
use App\Enums\PayrollEntryStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PayrollService
{
    /**
     * Create a new payroll period
     */
    public function createPayrollPeriod(array $data): PayrollPeriod
    {
        return PayrollPeriod::create($data);
    }

    /**
     * Generate payroll entries for all active staff in a period
     */
    public function generatePayrollEntries(PayrollPeriod $period): Collection
    {
        $activeStaff = Staff::where('branch_id', $period->branch_id)
            ->where('employment_status', EmploymentStatus::Active)
            ->with('role')
            ->get();

        $entries = collect();

        foreach ($activeStaff as $staff) {
            // Check if entry already exists
            $existingEntry = PayrollEntry::where('payroll_period_id', $period->id)
                ->where('staff_id', $staff->id)
                ->first();

            if ($existingEntry) {
                $entries->push($existingEntry);
                continue;
            }

            // Get salary (from staff or from role's base salary)
            $baseSalary = $staff->salary ?? $staff->role?->base_salary ?? 0;

            $entry = PayrollEntry::create([
                'payroll_period_id' => $period->id,
                'staff_id' => $staff->id,
                'base_salary' => $baseSalary,
                'overtime' => 0,
                'bonus' => 0,
                'allowances' => 0,
                'tax' => $this->calculateTax($baseSalary),
                'ssnit' => $this->calculateSsnit($baseSalary),
                'other_deductions' => 0,
                'status' => PayrollEntryStatus::Pending,
            ]);

            $entries->push($entry);
        }

        return $entries;
    }

    /**
     * Calculate Ghana tax (PAYE)
     * Simplified calculation - in production, use actual tax brackets
     */
    protected function calculateTax(float $salary): float
    {
        // Simplified Ghana tax calculation
        // First GHS 365 is tax-free
        // Then tiered rates apply
        if ($salary <= 365) {
            return 0;
        }

        $taxable = $salary - 365;
        $tax = 0;

        // 5% on next GHS 110
        if ($taxable > 0) {
            $bracket = min($taxable, 110);
            $tax += $bracket * 0.05;
            $taxable -= $bracket;
        }

        // 10% on next GHS 130
        if ($taxable > 0) {
            $bracket = min($taxable, 130);
            $tax += $bracket * 0.10;
            $taxable -= $bracket;
        }

        // 17.5% on next GHS 3000
        if ($taxable > 0) {
            $bracket = min($taxable, 3000);
            $tax += $bracket * 0.175;
            $taxable -= $bracket;
        }

        // 25% on next GHS 16395
        if ($taxable > 0) {
            $bracket = min($taxable, 16395);
            $tax += $bracket * 0.25;
            $taxable -= $bracket;
        }

        // 30% on remaining
        if ($taxable > 0) {
            $tax += $taxable * 0.30;
        }

        return round($tax, 2);
    }

    /**
     * Calculate SSNIT contribution (5.5% employee contribution)
     */
    protected function calculateSsnit(float $salary): float
    {
        return round($salary * 0.055, 2);
    }

    /**
     * Approve a payroll period
     */
    public function approvePayroll(PayrollPeriod $period, string $userId): PayrollPeriod
    {
        $period->update([
            'status' => PayrollStatus::Approved,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        // Approve all pending entries
        $period->entries()
            ->where('status', PayrollEntryStatus::Pending)
            ->update(['status' => PayrollEntryStatus::Approved]);

        return $period->fresh();
    }

    /**
     * Process payroll (mark as paid)
     */
    public function processPayroll(PayrollPeriod $period): PayrollPeriod
    {
        $period->update([
            'status' => PayrollStatus::Paid,
        ]);

        // Mark all entries as paid
        $period->entries()
            ->where('status', PayrollEntryStatus::Approved)
            ->update([
                'status' => PayrollEntryStatus::Paid,
                'paid_at' => now(),
            ]);

        return $period->fresh();
    }

    /**
     * Get payroll summary for a period
     */
    public function getPayrollSummary(PayrollPeriod $period): array
    {
        $entries = $period->entries()->with('staff.role')->get();

        return [
            'period' => $period,
            'total_staff' => $entries->count(),
            'total_gross' => $entries->sum('gross_pay'),
            'total_deductions' => $entries->sum('total_deductions'),
            'total_net' => $entries->sum('net_pay'),
            'by_role' => $entries->groupBy('staff.role.name')->map(fn ($items) => [
                'count' => $items->count(),
                'total_gross' => $items->sum('gross_pay'),
                'total_net' => $items->sum('net_pay'),
            ]),
            'entries' => $entries,
        ];
    }

    /**
     * Get annual payroll summary
     */
    public function getAnnualPayrollSummary(int $year, ?string $branchId = null): array
    {
        $query = PayrollPeriod::whereYear('pay_date', $year)
            ->where('status', PayrollStatus::Paid);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $periods = $query->with('entries')->get();

        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthPeriods = $periods->filter(fn ($p) => Carbon::parse($p->pay_date)->month === $month);
            $monthlyData[$month] = [
                'month' => Carbon::create($year, $month)->format('M'),
                'total_gross' => $monthPeriods->sum('total_gross_pay'),
                'total_net' => $monthPeriods->sum('total_net_pay'),
                'staff_count' => $monthPeriods->sum(fn ($p) => $p->entries->count()),
            ];
        }

        return [
            'year' => $year,
            'total_gross' => $periods->sum('total_gross_pay'),
            'total_net' => $periods->sum('total_net_pay'),
            'monthly_breakdown' => $monthlyData,
        ];
    }
}
