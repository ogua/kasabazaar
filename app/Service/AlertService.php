<?php

namespace App\Service;

use App\Models\Alert;
use App\Models\Vehicle;
use App\Models\Shipment;
use App\Models\Document;
use App\Enums\AlertSeverity;
use Illuminate\Support\Collection;

class AlertService
{
    /**
     * Create a new alert
     */
    public function createAlert(array $data): Alert
    {
        return Alert::create($data);
    }

    /**
     * Generate vehicle-related alerts
     */
    public function generateVehicleAlerts(): int
    {
        $alertsCreated = 0;

        // Insurance expiry alerts
        $vehicles = Vehicle::whereNotNull('insurance_expiry')
            ->where('insurance_expiry', '<=', now()->addDays(30))
            ->where('insurance_expiry', '>', now())
            ->get();

        foreach ($vehicles as $vehicle) {
            $daysUntilExpiry = now()->diffInDays($vehicle->insurance_expiry);
            $severity = $daysUntilExpiry <= 7 ? AlertSeverity::Critical : AlertSeverity::Warning;

            $this->createOrUpdateAlert([
                'branch_id' => $vehicle->branch_id,
                'alert_type' => 'vehicle_insurance_expiry',
                'title' => 'Vehicle Insurance Expiring',
                'message' => "Vehicle {$vehicle->registration_number} insurance expires in {$daysUntilExpiry} days.",
                'severity' => $severity,
                'related_model' => Vehicle::class,
                'related_id' => $vehicle->id,
            ]);
            $alertsCreated++;
        }

        // Roadworthy expiry alerts
        $vehicles = Vehicle::whereNotNull('roadworthy_expiry')
            ->where('roadworthy_expiry', '<=', now()->addDays(30))
            ->where('roadworthy_expiry', '>', now())
            ->get();

        foreach ($vehicles as $vehicle) {
            $daysUntilExpiry = now()->diffInDays($vehicle->roadworthy_expiry);
            $severity = $daysUntilExpiry <= 7 ? AlertSeverity::Critical : AlertSeverity::Warning;

            $this->createOrUpdateAlert([
                'branch_id' => $vehicle->branch_id,
                'alert_type' => 'vehicle_roadworthy_expiry',
                'title' => 'Vehicle Roadworthy Expiring',
                'message' => "Vehicle {$vehicle->registration_number} roadworthy expires in {$daysUntilExpiry} days.",
                'severity' => $severity,
                'related_model' => Vehicle::class,
                'related_id' => $vehicle->id,
            ]);
            $alertsCreated++;
        }

        // Service due alerts
        $vehicles = Vehicle::whereNotNull('next_service_due')
            ->where('next_service_due', '<=', now())
            ->get();

        foreach ($vehicles as $vehicle) {
            $this->createOrUpdateAlert([
                'branch_id' => $vehicle->branch_id,
                'alert_type' => 'vehicle_service_due',
                'title' => 'Vehicle Service Due',
                'message' => "Vehicle {$vehicle->registration_number} service is overdue.",
                'severity' => AlertSeverity::Warning,
                'related_model' => Vehicle::class,
                'related_id' => $vehicle->id,
            ]);
            $alertsCreated++;
        }

        return $alertsCreated;
    }

    /**
     * Generate payment-related alerts
     */
    public function generatePaymentAlerts(): int
    {
        $alertsCreated = 0;

        // Overdue payments
        $overdueThresholds = [
            7 => AlertSeverity::Info,
            14 => AlertSeverity::Warning,
            30 => AlertSeverity::Warning,
            60 => AlertSeverity::Critical,
            90 => AlertSeverity::Critical,
        ];

        foreach ($overdueThresholds as $days => $severity) {
            $shipments = Shipment::whereRaw('total > paid')
                ->where('created_at', '<=', now()->subDays($days))
                ->where('created_at', '>', now()->subDays($days + 7))
                ->get();

            foreach ($shipments as $shipment) {
                $balance = $shipment->total - $shipment->paid;

                $this->createOrUpdateAlert([
                    'branch_id' => $shipment->branch_id,
                    'alert_type' => 'payment_overdue',
                    'title' => "Payment Overdue ({$days}+ days)",
                    'message' => "Shipment {$shipment->shipping_reference} has outstanding balance of \${$balance}.",
                    'severity' => $severity,
                    'related_model' => Shipment::class,
                    'related_id' => $shipment->id,
                ]);
                $alertsCreated++;
            }
        }

        return $alertsCreated;
    }

    /**
     * Generate document expiry alerts
     */
    public function generateDocumentAlerts(): int
    {
        $alertsCreated = 0;

        $documents = Document::whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>', now())
            ->where('is_archived', false)
            ->get();

        foreach ($documents as $document) {
            $daysUntilExpiry = now()->diffInDays($document->expiry_date);
            $severity = $daysUntilExpiry <= 7 ? AlertSeverity::Critical : AlertSeverity::Warning;

            $this->createOrUpdateAlert([
                'branch_id' => $document->branch_id,
                'alert_type' => 'document_expiry',
                'title' => 'Document Expiring',
                'message' => "Document '{$document->title}' expires in {$daysUntilExpiry} days.",
                'severity' => $severity,
                'related_model' => Document::class,
                'related_id' => $document->id,
            ]);
            $alertsCreated++;
        }

        return $alertsCreated;
    }

    /**
     * Create or update an alert
     */
    protected function createOrUpdateAlert(array $data): Alert
    {
        // Check if similar alert already exists
        $existingAlert = Alert::where('alert_type', $data['alert_type'])
            ->where('related_model', $data['related_model'] ?? null)
            ->where('related_id', $data['related_id'] ?? null)
            ->where('is_dismissed', false)
            ->first();

        if ($existingAlert) {
            $existingAlert->update([
                'message' => $data['message'],
                'severity' => $data['severity'],
            ]);
            return $existingAlert;
        }

        return Alert::create($data);
    }

    /**
     * Get unread alerts for a user
     */
    public function getUnreadAlerts(?string $userId = null, ?string $branchId = null): Collection
    {
        $query = Alert::unread()->notDismissed()->orderBy('created_at', 'desc');

        if ($userId) {
            $query->forUser($userId);
        }

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', $branchId);
            });
        }

        return $query->get();
    }

    /**
     * Get critical alerts
     */
    public function getCriticalAlerts(?string $branchId = null): Collection
    {
        $query = Alert::critical()->notDismissed();

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', $branchId);
            });
        }

        return $query->get();
    }

    /**
     * Mark all alerts as read for a user
     */
    public function markAllAsRead(?string $userId = null): int
    {
        $query = Alert::unread();

        if ($userId) {
            $query->forUser($userId);
        }

        return $query->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Run all alert generators
     */
    public function generateAllAlerts(): array
    {
        return [
            'vehicle_alerts' => $this->generateVehicleAlerts(),
            'payment_alerts' => $this->generatePaymentAlerts(),
            'document_alerts' => $this->generateDocumentAlerts(),
        ];
    }
}
