# KasaBaZaar Mobile App Development Guide

This document outlines the specifications and requirements for developing mobile applications for the KasaBaZaar shipping management system.

---

## Overview

The mobile application suite will consist of two separate apps:
1. **Driver App** - For delivery drivers and field staff
2. **Client App** - For customers to track shipments and manage their account

---

## 1. Driver Mobile App

### 1.1 Target Platforms
- Android (API 24+, Android 7.0+)
- iOS (iOS 14.0+)

### 1.2 Recommended Technology Stack
- **Framework**: Flutter or React Native (cross-platform)
- **State Management**: Provider/Riverpod (Flutter) or Redux (React Native)
- **Local Storage**: SQLite / Hive for offline support
- **Push Notifications**: Firebase Cloud Messaging (FCM)
- **Maps**: Google Maps SDK
- **Camera**: Native camera integration for proof of delivery

### 1.3 Features

#### Authentication
- Login with employee credentials (linked to staff user account)
- Biometric authentication (fingerprint/face ID)
- Session management with JWT tokens
- Remember device functionality

#### Dashboard
- Today's assigned trips overview
- Pending deliveries count
- Completed deliveries count
- Quick stats (distance traveled, deliveries today)

#### Trip Management
```
Screens:
├── Trip List (Today's Trips)
│   ├── Scheduled
│   ├── In Progress
│   └── Completed
├── Trip Details
│   ├── Route information
│   ├── Vehicle details
│   ├── Shipment list
│   └── Cost tracking
└── Start/Complete Trip
    ├── Mileage input
    ├── Fuel cost logging
    └── Trip notes
```

#### Delivery Management
- List of shipments for current trip
- Shipment details view
  - Client information
  - Receiver details
  - Items list
  - Delivery location (with map)
- Update delivery status
  - Pending → Delivered / Failed / Partial
- Proof of Delivery (POD)
  - Capture receiver signature (digital signature pad)
  - Take delivery photo
  - Add delivery notes
- Offline capability for areas with poor network

#### Navigation
- Integrated GPS navigation to delivery locations
- Route optimization suggestions
- Turn-by-turn directions

#### Vehicle Management
- View assigned vehicle details
- Report vehicle issues
- Log fuel purchases
  - Amount
  - Fuel station
  - Receipt photo
- Pre-trip vehicle checklist

#### Expenses
- Log trip-related expenses
- Capture receipt photos
- Categories: Fuel, Toll, Food, Other
- Sync with main system

#### Notifications
- New trip assignments
- Schedule changes
- System alerts

### 1.4 API Endpoints Required

```
Authentication:
POST   /api/mobile/auth/login
POST   /api/mobile/auth/logout
POST   /api/mobile/auth/refresh-token

Driver Profile:
GET    /api/mobile/driver/profile
PUT    /api/mobile/driver/profile

Trips:
GET    /api/mobile/trips                    # List driver's trips
GET    /api/mobile/trips/{id}               # Trip details
POST   /api/mobile/trips/{id}/start         # Start trip
POST   /api/mobile/trips/{id}/complete      # Complete trip
PUT    /api/mobile/trips/{id}/mileage       # Update mileage
POST   /api/mobile/trips/{id}/expenses      # Add trip expense

Deliveries:
GET    /api/mobile/trips/{id}/shipments     # Shipments in trip
PUT    /api/mobile/deliveries/{id}/status   # Update delivery status
POST   /api/mobile/deliveries/{id}/pod      # Upload proof of delivery

Vehicle:
GET    /api/mobile/vehicles/assigned        # Get assigned vehicle
POST   /api/mobile/vehicles/{id}/issue      # Report issue
POST   /api/mobile/vehicles/{id}/fuel       # Log fuel purchase

Sync:
POST   /api/mobile/sync/upload              # Sync offline data
GET    /api/mobile/sync/download            # Download pending data
```

### 1.5 Offline Functionality

The driver app must work in areas with limited connectivity:

- **Cached Data**:
  - Today's trips and shipments
  - Client/receiver information
  - Delivery locations (pre-cached maps)

- **Offline Actions** (queued for sync):
  - Delivery status updates
  - Signature captures
  - Photo uploads
  - Expense entries

- **Sync Strategy**:
  - Auto-sync when network available
  - Manual sync option
  - Conflict resolution (server wins for most data)
  - Last sync timestamp display

---

## 2. Client Mobile App

### 2.1 Target Platforms
- Android (API 24+, Android 7.0+)
- iOS (iOS 14.0+)
- Progressive Web App (PWA) as alternative

### 2.2 Recommended Technology Stack
- **Framework**: Flutter or React Native
- **State Management**: Provider/Riverpod (Flutter) or Redux (React Native)
- **Push Notifications**: Firebase Cloud Messaging
- **Payment Integration**: Paystack Mobile SDK

### 2.3 Features

#### Authentication
- Login with client credentials
- Phone number verification (OTP)
- Social login (Google, Apple)
- Guest tracking (shipment lookup only)

#### Dashboard
```
Widgets:
├── Active Shipments Summary
├── Recent Payments
├── Outstanding Balance
└── Quick Actions
    ├── Track Shipment
    ├── Request Quote
    └── Contact Support
```

#### Shipment Tracking
- Real-time shipment status
- Timeline view of shipment journey
- Push notifications for status changes
- Map view of current shipment location (when in transit)
- Estimated delivery date
- Shipment history

#### Shipments
```
Screens:
├── All Shipments
│   ├── Filter by status
│   ├── Search by reference
│   └── Date range filter
├── Shipment Details
│   ├── Reference & tracking number
│   ├── Status timeline
│   ├── Receiver information
│   ├── Items list
│   ├── Payment status
│   └── Documents (invoice, receipt)
└── Request New Shipment
    ├── Sender info (pre-filled)
    ├── Receiver details
    ├── Items declaration
    └── Submit request
```

#### Payments
- View outstanding balances
- Payment history
- Make payments via:
  - Mobile Money (MTN, Vodafone, AirtelTigo)
  - Card payment
  - Bank transfer
- Download payment receipts
- Payment notifications

#### Quotes
- Request shipping quotes
- View quote history
- Accept/reject quotes
- Convert quote to shipment

#### Profile Management
- Update contact information
- Change password
- Notification preferences
- Saved receivers (address book)

#### Support
- In-app chat support
- FAQ section
- Contact information
- Report issues

#### Notifications
- Shipment status updates
- Payment reminders
- Delivery confirmations
- Promotional messages

### 2.4 API Endpoints Required

```
Authentication:
POST   /api/mobile/client/auth/login
POST   /api/mobile/client/auth/register
POST   /api/mobile/client/auth/verify-otp
POST   /api/mobile/client/auth/forgot-password
POST   /api/mobile/client/auth/logout

Profile:
GET    /api/mobile/client/profile
PUT    /api/mobile/client/profile
PUT    /api/mobile/client/password

Shipments:
GET    /api/mobile/client/shipments
GET    /api/mobile/client/shipments/{id}
POST   /api/mobile/client/shipments/request
GET    /api/mobile/client/shipments/{id}/track
GET    /api/mobile/client/shipments/{id}/timeline

Payments:
GET    /api/mobile/client/payments
GET    /api/mobile/client/balance
POST   /api/mobile/client/payments/initiate
GET    /api/mobile/client/payments/{id}/receipt

Quotes:
GET    /api/mobile/client/quotes
POST   /api/mobile/client/quotes/request
PUT    /api/mobile/client/quotes/{id}/accept
PUT    /api/mobile/client/quotes/{id}/reject

Receivers:
GET    /api/mobile/client/receivers        # Saved receivers
POST   /api/mobile/client/receivers
PUT    /api/mobile/client/receivers/{id}
DELETE /api/mobile/client/receivers/{id}

Support:
POST   /api/mobile/client/support/message
GET    /api/mobile/client/support/faq

Tracking (Public):
GET    /api/track/{tracking_number}        # No auth required
```

---

## 3. Backend API Development

### 3.1 API Structure

Create a new API module in Laravel:

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── Mobile/
│   │           ├── Driver/
│   │           │   ├── AuthController.php
│   │           │   ├── TripController.php
│   │           │   ├── DeliveryController.php
│   │           │   └── VehicleController.php
│   │           └── Client/
│   │               ├── AuthController.php
│   │               ├── ShipmentController.php
│   │               ├── PaymentController.php
│   │               └── QuoteController.php
│   └── Resources/
│       └── Api/
│           ├── TripResource.php
│           ├── ShipmentResource.php
│           └── ...
routes/
└── api/
    ├── mobile-driver.php
    └── mobile-client.php
```

### 3.2 Authentication

Use Laravel Sanctum for API authentication:

```php
// Token-based authentication for mobile apps
// Tokens should have abilities/scopes:
// - driver:* for driver app
// - client:* for client app

// Token expiration: 30 days
// Refresh mechanism: Issue new token on each successful request
```

### 3.3 Response Format

```json
{
    "success": true,
    "message": "Operation successful",
    "data": {
        // Response data
    },
    "meta": {
        "pagination": {
            "current_page": 1,
            "per_page": 20,
            "total": 100
        }
    }
}

// Error response
{
    "success": false,
    "message": "Error message",
    "errors": {
        "field": ["Error details"]
    },
    "code": "ERROR_CODE"
}
```

### 3.4 Rate Limiting

```php
// Driver App: 60 requests per minute
// Client App: 30 requests per minute
// Tracking (public): 10 requests per minute per IP
```

---

## 4. Push Notifications

### 4.1 Notification Types

#### Driver App
| Event | Title | Body |
|-------|-------|------|
| trip_assigned | New Trip Assigned | You have a new trip to {destination} |
| trip_updated | Trip Updated | Trip {reference} has been updated |
| schedule_change | Schedule Changed | Your schedule for {date} has changed |

#### Client App
| Event | Title | Body |
|-------|-------|------|
| shipment_status | Shipment Update | Your shipment {reference} is now {status} |
| shipment_delivered | Delivered! | Your shipment {reference} has been delivered |
| payment_received | Payment Received | Payment of ${amount} received for {reference} |
| payment_reminder | Payment Reminder | You have an outstanding balance of ${amount} |

### 4.2 Implementation

```php
// Use Laravel Notifications with FCM channel
// Store device tokens in users table or separate device_tokens table

Schema::create('device_tokens', function (Blueprint $table) {
    $table->id();
    $table->foreignUuid('user_id')->constrained();
    $table->string('token');
    $table->string('platform'); // ios, android
    $table->string('device_name')->nullable();
    $table->timestamp('last_used_at');
    $table->timestamps();
});
```

---

## 5. Security Considerations

### 5.1 Data Security
- All API communication over HTTPS
- Encrypt sensitive data at rest
- Secure token storage (Keychain/Keystore)
- Certificate pinning for API calls

### 5.2 Authentication Security
- Strong password requirements
- Rate limiting on auth endpoints
- Account lockout after failed attempts
- Device fingerprinting

### 5.3 Data Privacy
- Minimum data collection
- Clear data retention policies
- User data export capability
- Account deletion option

---

## 6. Testing Requirements

### 6.1 Testing Types
- Unit tests for business logic
- Integration tests for API endpoints
- UI tests for critical flows
- Performance testing
- Security penetration testing

### 6.2 Test Devices
- Android: Range of devices (low-end to flagship)
- iOS: iPhone 8+ and recent models
- Test on actual devices, not just emulators

---

## 7. Deployment

### 7.1 App Distribution
- **Android**: Google Play Store
- **iOS**: Apple App Store
- **Internal**: Firebase App Distribution for beta testing

### 7.2 CI/CD Pipeline
- Automated builds on push
- Automated testing
- Staged rollouts (10% → 50% → 100%)
- Crash monitoring (Firebase Crashlytics)

### 7.3 Versioning
- Semantic versioning (MAJOR.MINOR.PATCH)
- Force update mechanism for critical updates
- Backward compatibility for 2 previous versions

---

## 8. Analytics & Monitoring

### 8.1 Analytics Events
- Screen views
- User actions (button clicks, form submissions)
- Error events
- Performance metrics

### 8.2 Monitoring
- Crash reporting (Crashlytics)
- Performance monitoring
- API latency tracking
- User retention metrics

---

## 9. Timeline Estimation

### Phase 1: Foundation (4-6 weeks)
- API development
- Authentication system
- Basic app structure

### Phase 2: Driver App Core (6-8 weeks)
- Trip management
- Delivery updates
- Offline support
- Testing

### Phase 3: Client App Core (6-8 weeks)
- Shipment tracking
- Payment integration
- Profile management
- Testing

### Phase 4: Polish & Launch (4 weeks)
- UI/UX refinement
- Performance optimization
- App store submission
- Beta testing

**Total Estimated Time: 20-26 weeks**

---

## 10. Resources & References

### Design Resources
- Material Design 3 (Android): https://m3.material.io/
- Human Interface Guidelines (iOS): https://developer.apple.com/design/

### Development Resources
- Flutter: https://flutter.dev/docs
- React Native: https://reactnative.dev/docs
- Laravel Sanctum: https://laravel.com/docs/sanctum

### Third-Party Services
- Firebase: https://firebase.google.com/
- Paystack: https://paystack.com/docs/
- Google Maps Platform: https://developers.google.com/maps

---

## Appendix A: Database Schema for Mobile

### device_tokens
```sql
CREATE TABLE device_tokens (
    id BIGINT PRIMARY KEY,
    user_id UUID REFERENCES users(id),
    token VARCHAR(255) NOT NULL,
    platform ENUM('ios', 'android') NOT NULL,
    device_name VARCHAR(255),
    app_version VARCHAR(20),
    last_used_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(user_id, token)
);
```

### mobile_sessions
```sql
CREATE TABLE mobile_sessions (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES users(id),
    token_id BIGINT REFERENCES personal_access_tokens(id),
    device_info JSON,
    ip_address VARCHAR(45),
    last_activity_at TIMESTAMP,
    created_at TIMESTAMP
);
```

---

## Appendix B: Wireframe Suggestions

### Driver App - Main Screens
```
┌─────────────────────┐
│      Dashboard      │
├─────────────────────┤
│  ┌───────────────┐  │
│  │ Today's Stats │  │
│  │ Trips: 5      │  │
│  │ Delivered: 12 │  │
│  │ Pending: 8    │  │
│  └───────────────┘  │
│                     │
│  Active Trip        │
│  ┌───────────────┐  │
│  │ TRIP-20260131 │  │
│  │ To: Accra     │  │
│  │ 5 deliveries  │  │
│  │ [Continue →]  │  │
│  └───────────────┘  │
│                     │
│  Upcoming Trips     │
│  • Trip to Kumasi   │
│  • Trip to Takoradi │
└─────────────────────┘

┌─────────────────────┐
│   Delivery Detail   │
├─────────────────────┤
│  CON51-26-C2-001    │
│  ─────────────────  │
│  Receiver:          │
│  John Doe           │
│  +233 20 123 4567   │
│                     │
│  Location:          │
│  123 Main Street    │
│  Accra, Ghana       │
│  [📍 Navigate]      │
│                     │
│  Items:             │
│  • Electronics x2   │
│  • Clothing x5      │
│                     │
│  ┌───────────────┐  │
│  │ Mark Delivered│  │
│  └───────────────┘  │
└─────────────────────┘
```

### Client App - Main Screens
```
┌─────────────────────┐
│      Dashboard      │
├─────────────────────┤
│  Welcome, Kwame!    │
│                     │
│  ┌───────────────┐  │
│  │ Track Shipment│  │
│  │ [Enter ref..] │  │
│  └───────────────┘  │
│                     │
│  Active Shipments   │
│  ┌───────────────┐  │
│  │ CON51-26-C2   │  │
│  │ 🚚 In Transit │  │
│  │ ETA: Feb 3    │  │
│  └───────────────┘  │
│                     │
│  Outstanding: $250  │
│  [Pay Now]          │
└─────────────────────┘

┌─────────────────────┐
│  Shipment Tracking  │
├─────────────────────┤
│  CON51-26-C2-001    │
│  ─────────────────  │
│                     │
│  ● Delivered        │
│  │ Feb 3, 2:30 PM   │
│  │                  │
│  ○ Out for Delivery │
│  │ Feb 3, 9:00 AM   │
│  │                  │
│  ○ Arrived at Port  │
│  │ Feb 1, 11:00 AM  │
│  │                  │
│  ○ Shipped          │
│  │ Jan 20, 3:00 PM  │
│  │                  │
│  ○ Order Placed     │
│    Jan 15, 10:00 AM │
│                     │
│  [View Details]     │
└─────────────────────┘
```

---

*Document Version: 1.0*
*Last Updated: January 2026*
*Author: KasaBaZaar Development Team*
