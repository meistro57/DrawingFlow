# 🤖 AGENTS.md - DrawingFlow Development Guide

**Last Updated:** March 2, 2026
**Project:** DrawingFlow - Steel Fabrication Shop Drawing Workflow System
**Status:** Phase 2 Complete - File Uploads, Filtering, Feature Tests (Future SteelFlow MRP Integration)

---

## 📋 Table of Contents

1. [Project Overview](#project-overview)
2. [The Problem We're Solving](#the-problem-were-solving)
3. [Tech Stack & Architecture](#tech-stack--architecture)
4. [Database Schema](#database-schema)
5. [Business Logic & Workflows](#business-logic--workflows)
6. [Development Patterns](#development-patterns)
7. [Integration Points](#integration-points)
8. [Code Style & Conventions](#code-style--conventions)
9. [Testing Strategy](#testing-strategy)
10. [Deployment & Environments](#deployment--environments)
11. [AI Agent Guidelines](#ai-agent-guidelines)

---

## 🎯 Project Overview

### What is DrawingFlow?

DrawingFlow is a precision-engineered workflow management system for steel fabrication shop drawings. It replaces the chaotic multi-list Microsoft Lists workflow with a unified pipeline that tracks drawings from customer request through approval to fabrication handoff.

### Project Goals

**Primary Mission:** Replace the three-list nightmare (Shop Drawing Request → Drawing Submittal Log → Fabrication Drawing Log) with one intelligent system.

**Success Criteria:**
- Zero manual data re-entry between stages
- Real-time visibility into drawing status
- Automated approval workflow routing
- PDF markup capability built-in
- Mobile-friendly for shop floor use
- Battle-tested standalone, then absorbed into SteelFlow MRP

### Current Status

- **Phase:** Phase 2 Complete (File Uploads + Filtering + Tests)
- **Version:** 0.3.0-alpha
- **Environment:** Development
- **Deployment:** Not yet deployed

---

## 🔥 The Problem We're Solving

### The Current Pain

**User:** Mark (Steel Fabricator & Shop Owner, 30+ years experience)

**Current Workflow (Microsoft Lists Hell):**

1. **List #1: Shop Drawing Request** - Someone in the office creates a request
2. **List #2: Drawing Submittal Log** - Mark designs, submits for approval, tracks status
3. **List #3: Fabrication Drawing Log** - After approval, handoff to fab team

**Pain Points:**
- Manual data entry across 3 separate lists
- No automatic status transitions
- Approval responses require manual updates
- No unified view of project status
- No drawing markup capability
- Clunky status management
- Poor mobile experience
- Information gets lost between handoffs

### The DrawingFlow Solution

**One Unified Pipeline:**
```
Request → Design → Submit → Approve → Fabricate
```

**Key Features:**
- Single source of truth for drawing lifecycle
- Automated status transitions based on approval type
- Built-in PDF viewer with markup tools
- Real-time notifications
- Customer-specific approval workflows
- Seamless fab handoff
- Mobile-responsive for field use

---

## 🛠️ Tech Stack & Architecture

### Backend Stack

**Framework:** Laravel 12.x (PHP 8.3+)
- **Why Laravel 12:** Latest features, improved performance, native async support
- **PHP Version:** 8.3+ for modern syntax and performance

**Database:** MySQL 8.0 / PostgreSQL 15+
- Primary: MySQL 8.0 (matching SteelFlow)
- Alternative: PostgreSQL for advanced features if needed

**Cache & Queue:** Redis 7.x + Laravel Horizon
- Session storage
- Cache layer
- Queue backend
- Real-time features foundation

**Search:** Meilisearch
- Fast document search
- Drawing content indexing
- Sub-millisecond lookups

**File Storage:** 
- Local: For development
- S3-compatible: For production (AWS S3, DigitalOcean Spaces, etc.)
- PDF processing: Imagick/GhostScript

### Frontend Stack

**Framework:** Vue.js 3 + Inertia.js
- **Vue 3:** Composition API, better TypeScript support
- **Inertia.js:** SPA feel without API complexity
- **Why:** Matches SteelFlow ecosystem for eventual integration

**State Management:** Pinia
- Replaces Vuex
- Better TypeScript support
- Cleaner syntax

**UI Framework:** Tailwind CSS 3.x + Headless UI
- **Tailwind:** Utility-first CSS, matches SteelFlow
- **Headless UI:** Accessible components
- **Custom Components:** Building on SteelFlow patterns

**Build Tool:** Vite 5.x
- Fast HMR
- Optimized production builds
- Native ES modules

### Development Environment

**Containerization:** Docker + Docker Compose
- **Services:**
  - `app` - PHP 8.3-FPM + Laravel
  - `nginx` - Web server
  - `mysql` - Database
  - `redis` - Cache/queue
  - `meilisearch` - Search engine
  - `mailhog` - Email testing

**Why Docker:** 
- Consistent dev environment
- Matches SteelFlow setup
- Easy onboarding
- Production parity

---

## 🗄️ Database Schema

### Core Tables

#### 1. `customers` (Imported from SteelFlow)

```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) UNIQUE,
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(50),
    zip VARCHAR(20),
    country VARCHAR(100) DEFAULT 'USA',
    notes TEXT,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);
```

**Business Rules:**
- Customer code is unique identifier for integrations
- Soft deletes to preserve historical data
- Active flag controls visibility in dropdowns

---

#### 2. `projects` (Imported from SteelFlow)

```sql
CREATE TABLE projects (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    project_number VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    description TEXT,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(50),
    zip VARCHAR(20),
    start_date DATE,
    target_completion_date DATE,
    status ENUM('estimating', 'active', 'on_hold', 'completed', 'cancelled') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);
```

**Business Rules:**
- Project numbers follow format: YYYY-XXX (e.g., 2026-001)
- Projects belong to one customer
- Status drives workflow availability

---

#### 3. `drawing_requests` (DrawingFlow Core)

```sql
CREATE TABLE drawing_requests (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    request_number VARCHAR(50) UNIQUE NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    requested_by_user_id BIGINT UNSIGNED NOT NULL,
    assigned_to_user_id BIGINT UNSIGNED NULL,
    
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    
    drawing_type ENUM('structural', 'misc', 'railings', 'stairs', 'handrail', 'canopy', 'other') NOT NULL,
    
    job_number VARCHAR(100),
    customer_address TEXT,
    
    required_date DATE,
    requested_date DATE NOT NULL,
    
    status ENUM('pending', 'in_progress', 'ready_to_submit', 'submitted', 'approved', 'on_hold', 'cancelled') DEFAULT 'pending',
    
    notes TEXT,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (requested_by_user_id) REFERENCES users(id),
    FOREIGN KEY (assigned_to_user_id) REFERENCES users(id)
);
```

**Business Rules:**
- Request number format: DR-YYYY-XXXX (e.g., DR-2026-0001)
- Auto-assigned to available detailer based on workload
- Priority affects queue ordering
- Status drives workflow visibility

---

#### 4. `drawing_submittals` (DrawingFlow Core)

```sql
CREATE TABLE drawing_submittals (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    submittal_number VARCHAR(50) UNIQUE NOT NULL,
    drawing_request_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    
    revision VARCHAR(10) DEFAULT 'A',
    
    submitted_by_user_id BIGINT UNSIGNED NOT NULL,
    submitted_at TIMESTAMP NULL,
    
    drawing_discipline ENUM('residential_steel', 'commercial_misc', 'commercial_structural', 'railings', 'stairs', 'other'),
    
    purpose ENUM('for_approval', 'for_information', 'for_construction', 'resubmittal') DEFAULT 'for_approval',
    
    status ENUM(
        'draft',
        'ready_to_submit',
        'submitted',
        'approved',
        'approved_as_noted',
        'revise_and_resubmit',
        'rejected',
        'field_verify_required',
        'superseded'
    ) DEFAULT 'draft',
    
    approval_received_at TIMESTAMP NULL,
    approval_type VARCHAR(50) NULL,
    approval_notes TEXT,
    
    next_action TEXT,
    
    notes TEXT,
    internal_notes TEXT,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (drawing_request_id) REFERENCES drawing_requests(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (submitted_by_user_id) REFERENCES users(id)
);
```

**Business Rules:**
- Submittal number format: SUB-YYYY-XXXX (e.g., SUB-2026-0001)
- Revision increments automatically on resubmittal (A → B → C)
- Status determines workflow stage
- Approval type triggers next action

**Status Transitions:**
```
draft → ready_to_submit → submitted → [approval_type] → fab_ready
                                    ↓
                            revise_and_resubmit → draft (new revision)
```

---

#### 5. `submittal_files` (DrawingFlow Core)

```sql
CREATE TABLE submittal_files (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    submittal_id BIGINT UNSIGNED NOT NULL,
    
    file_type ENUM('drawing', 'calculation', 'specification', 'photo', 'markup', 'approval', 'other') NOT NULL,
    
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT UNSIGNED,
    mime_type VARCHAR(100),
    
    version INTEGER DEFAULT 1,
    is_current BOOLEAN DEFAULT TRUE,
    
    uploaded_by_user_id BIGINT UNSIGNED NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    notes TEXT,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (submittal_id) REFERENCES drawing_submittals(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by_user_id) REFERENCES users(id)
);
```

**Business Rules:**
- Files stored with UUID naming to prevent collisions
- Version tracking for file replacements
- `is_current` flag identifies latest version
- Cascade delete when submittal deleted

---

#### 6. `submittal_approvals` (DrawingFlow Core)

```sql
CREATE TABLE submittal_approvals (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    submittal_id BIGINT UNSIGNED NOT NULL,
    
    approval_type ENUM(
        'approved',
        'approved_as_noted',
        'revise_and_resubmit',
        'rejected',
        'field_verify_required'
    ) NOT NULL,
    
    approved_by VARCHAR(255),
    approved_at TIMESTAMP NOT NULL,
    
    reviewer_name VARCHAR(255),
    reviewer_title VARCHAR(100),
    reviewer_company VARCHAR(255),
    reviewer_email VARCHAR(255),
    
    approval_notes TEXT,
    conditions TEXT,
    
    approval_file_id BIGINT UNSIGNED NULL,
    
    created_by_user_id BIGINT UNSIGNED NOT NULL,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (submittal_id) REFERENCES drawing_submittals(id) ON DELETE CASCADE,
    FOREIGN KEY (approval_file_id) REFERENCES submittal_files(id),
    FOREIGN KEY (created_by_user_id) REFERENCES users(id)
);
```

**Business Rules:**
- Multiple approvals possible (GC → Architect → Engineer chain)
- Latest approval determines submittal status
- Approval file is the stamped/marked PDF returned by customer

---

#### 7. `fab_queue` (DrawingFlow → Fab Handoff)

```sql
CREATE TABLE fab_queue (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    submittal_id BIGINT UNSIGNED NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    
    queue_number VARCHAR(50) UNIQUE NOT NULL,
    
    priority INTEGER DEFAULT 5,
    
    material_requirements TEXT,
    cnc_files_attached BOOLEAN DEFAULT FALSE,
    
    assigned_to_user_id BIGINT UNSIGNED NULL,
    assigned_at TIMESTAMP NULL,
    
    status ENUM('queued', 'in_progress', 'completed', 'on_hold') DEFAULT 'queued',
    
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    
    notes TEXT,
    shop_notes TEXT,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (submittal_id) REFERENCES drawing_submittals(id),
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (assigned_to_user_id) REFERENCES users(id)
);
```

**Business Rules:**
- Queue number format: FAB-YYYY-XXXX
- Priority 1-10 (1 = highest)
- Auto-populated from approved submittal
- Status transitions tracked with timestamps

---

#### 8. `pdf_markups` (DrawingFlow Feature)

```sql
CREATE TABLE pdf_markups (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    submittal_file_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    
    page_number INTEGER NOT NULL,
    
    markup_type ENUM('circle', 'arrow', 'text', 'highlight', 'stamp', 'dimension') NOT NULL,
    
    markup_data JSON NOT NULL,
    -- Stores coordinates, text content, color, size, etc.
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (submittal_file_id) REFERENCES submittal_files(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**Business Rules:**
- Markup data stored as JSON for flexibility
- Multiple markups per page allowed
- User tracking for accountability
- Soft delete preserves markup history

**Example markup_data JSON:**
```json
{
  "type": "circle",
  "x": 450,
  "y": 600,
  "radius": 30,
  "color": "#FF0000",
  "strokeWidth": 3,
  "note": "Check this dimension"
}
```

---

#### 9. `customer_workflows` (DrawingFlow Advanced)

```sql
CREATE TABLE customer_workflows (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    customer_id BIGINT UNSIGNED NOT NULL,
    
    requires_architect_approval BOOLEAN DEFAULT FALSE,
    requires_engineer_approval BOOLEAN DEFAULT FALSE,
    requires_gc_approval BOOLEAN DEFAULT TRUE,
    
    preferred_submittal_method ENUM('email', 'portal', 'physical') DEFAULT 'email',
    submittal_email VARCHAR(255),
    
    approval_sla_days INTEGER DEFAULT 14,
    
    notes TEXT,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);
```

**Business Rules:**
- One workflow per customer
- Defines approval chain requirements
- SLA days for reminder system
- Preferred method affects submittal routing

---

### Relationship Summary

```
customers (1) ─── (∞) projects (1) ─── (∞) drawing_requests
                                              │
                                              │
                                              ↓
                                     drawing_submittals (1) ─── (∞) submittal_files
                                              │                          │
                                              │                          ↓
                                              │                   pdf_markups (∞)
                                              │
                                              ├─── (∞) submittal_approvals
                                              │
                                              ↓
                                         fab_queue (1)
```

---

## 🔄 Business Logic & Workflows

### Core Workflow States

#### Drawing Request Lifecycle

```
PENDING → IN_PROGRESS → READY_TO_SUBMIT → SUBMITTED → APPROVED
   ↓           ↓              ↓
ON_HOLD    CANCELLED      (loops back to IN_PROGRESS if revisions needed)
```

**Status Definitions:**
- **PENDING**: New request, not yet assigned
- **IN_PROGRESS**: Detailer actively working on drawings
- **READY_TO_SUBMIT**: Drawings complete, ready for submittal
- **SUBMITTED**: Sent to customer for approval
- **APPROVED**: Customer approved, ready for fab
- **ON_HOLD**: Waiting on customer info or external dependency
- **CANCELLED**: Request no longer needed

---

#### Submittal Approval Types & Actions

```
SUBMITTED → [Customer Reviews] → [Approval Type] → [Next Action]

Approval Types:
1. APPROVED → Status: approved → Next: fab_queue
2. APPROVED AS NOTED → Status: approved_as_noted → Next: review notes, then fab_queue
3. REVISE & RESUBMIT → Status: revise_and_resubmit → Next: back to IN_PROGRESS, rev++
4. REJECTED → Status: rejected → Next: escalate, major revisions
5. FIELD VERIFY REQUIRED → Status: field_verify_required → Next: assign field tech
```

**Automated Actions by Approval Type:**

| Approval Type | Auto Status Update | Auto Revision | Create Fab Queue | Notify |
|--------------|-------------------|---------------|------------------|--------|
| Approved | ✅ approved | No | ✅ Yes | Detailer, Shop |
| Approved as Noted | ✅ approved_as_noted | No | ✅ Yes (flagged) | Detailer, Shop |
| Revise & Resubmit | ✅ revise_and_resubmit | ✅ B → C → D | ❌ No | Detailer only |
| Rejected | ✅ rejected | No | ❌ No | Detailer, Manager |
| Field Verify | ✅ field_verify_required | No | ⏸️ Queued | Detailer, Field Tech |

---

### Service Layer Architecture

DrawingFlow uses service classes for complex business logic. Models handle relationships and data access, services handle workflows and calculations.

#### Example Services

**`App\Services\SubmittalService`**
```php
class SubmittalService
{
    public function createFromRequest(DrawingRequest $request): DrawingSubmittal
    {
        // Create new submittal from request
        // Auto-populate fields from request
        // Set initial status to 'draft'
        // Create audit trail
    }
    
    public function submit(DrawingSubmittal $submittal): bool
    {
        // Validate submittal is ready
        // Update status to 'submitted'
        // Generate submittal packet
        // Send email notification
        // Log submission
    }
    
    public function processApproval(
        DrawingSubmittal $submittal, 
        string $approvalType, 
        array $approvalData
    ): void {
        // Create approval record
        // Update submittal status based on approval type
        // Handle revision increment if needed
        // Create fab queue entry if approved
        // Send notifications
        // Trigger next workflow steps
    }
    
    public function createRevision(DrawingSubmittal $submittal): DrawingSubmittal
    {
        // Clone submittal
        // Increment revision (A → B)
        // Mark original as 'superseded'
        // Copy relevant files
        // Reset status to 'draft'
    }
}
```

**`App\Services\FabHandoffService`**
```php
class FabHandoffService
{
    public function createFabQueueEntry(DrawingSubmittal $submittal): FabQueue
    {
        // Validate submittal is approved
        // Extract material requirements
        // Calculate priority
        // Create fab queue entry
        // Notify shop team
    }
    
    public function assignToFabricator(FabQueue $entry, User $user): void
    {
        // Assign to user
        // Update status to 'in_progress'
        // Send notification
        // Start time tracking
    }
}
```

**`App\Services\PdfService`**
```php
class PdfService
{
    public function render(SubmittalFile $file): string
    {
        // Convert PDF to base64 for viewer
        // Generate page thumbnails
        // Extract metadata
    }
    
    public function applyMarkups(SubmittalFile $file, array $markups): string
    {
        // Load PDF
        // Apply markup annotations
        // Generate new PDF with markups
        // Save as new version
    }
    
    public function compare(SubmittalFile $file1, SubmittalFile $file2): array
    {
        // Side-by-side comparison
        // Highlight differences
        // Return comparison data
    }
}
```

---

### Event-Driven Workflows

DrawingFlow uses Laravel events for decoupled workflow automation.

#### Key Events

**Drawing Request Events:**
```php
// App\Events\DrawingRequest\
DrawingRequestCreated
DrawingRequestAssigned
DrawingRequestStatusChanged
DrawingRequestCompleted
DrawingRequestCancelled
```

**Submittal Events:**
```php
// App\Events\Submittal\
SubmittalCreated
SubmittalSubmitted
SubmittalApprovalReceived
SubmittalRevisionCreated
SubmittalSuperseded
```

**Fab Queue Events:**
```php
// App\Events\FabQueue\
FabQueueEntryCreated
FabQueueAssigned
FabQueueStarted
FabQueueCompleted
```

#### Event Listeners

```php
// App\Listeners\Submittal\
class NotifyDetailerOfApproval
{
    public function handle(SubmittalApprovalReceived $event): void
    {
        // Send notification to detailer
        // Create system notification
        // Send email if configured
    }
}

class CreateFabQueueOnApproval
{
    public function handle(SubmittalApprovalReceived $event): void
    {
        if ($event->submittal->isApproved()) {
            FabHandoffService::createFabQueueEntry($event->submittal);
        }
    }
}

class IncrementRevisionOnResubmit
{
    public function handle(SubmittalApprovalReceived $event): void
    {
        if ($event->approval->type === 'revise_and_resubmit') {
            SubmittalService::createRevision($event->submittal);
        }
    }
}
```

---

### Notification System

**Channels:**
- Database (in-app notifications)
- Email (via configured mail driver)
- Slack (optional webhook)
- SMS (optional Twilio integration)

**Notification Types:**

| Event | Recipient | Channel | Priority |
|-------|-----------|---------|----------|
| Drawing Request Assigned | Detailer | Email, Database | Normal |
| Submittal Submitted | Manager | Database | Normal |
| Approval Received | Detailer | Email, Database | High |
| Revise & Resubmit | Detailer | Email, Database, Slack | High |
| Field Verify Required | Field Tech | Email, SMS, Database | Urgent |
| Fab Queue Entry Created | Shop Lead | Database, Slack | Normal |
| Drawing Request Overdue | Manager, Detailer | Email, Slack | High |

---

## 💻 Development Patterns

### Laravel Patterns We Follow

#### 1. **Models are Dumb, Services are Smart**

**Models** handle:
- Database relationships
- Attribute casting
- Simple accessors/mutators
- Scopes for queries

**Services** handle:
- Complex business logic
- Multi-model transactions
- External API calls
- File processing
- Email sending

**Example:**

```php
// ❌ BAD - Business logic in model
class DrawingSubmittal extends Model
{
    public function submit()
    {
        DB::transaction(function () {
            $this->status = 'submitted';
            $this->submitted_at = now();
            $this->save();
            
            // Generate packet
            // Send emails
            // Create notifications
            // Log activity
        });
    }
}

// ✅ GOOD - Business logic in service
class SubmittalService
{
    public function submit(DrawingSubmittal $submittal): bool
    {
        return DB::transaction(function () use ($submittal) {
            $submittal->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
            
            $this->packetGenerator->generate($submittal);
            $this->notifier->notifySubmission($submittal);
            $this->logger->logSubmission($submittal);
            
            return true;
        });
    }
}
```

---

#### 2. **Form Requests for Validation**

All incoming data validated via Form Request classes.

```php
// app/Http/Requests/StoreDrawingRequestRequest.php
class StoreDrawingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DrawingRequest::class);
    }
    
    public function rules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'drawing_type' => 'required|in:structural,misc,railings,stairs,handrail,canopy,other',
            'required_date' => 'required|date|after:today',
        ];
    }
    
    public function messages(): array
    {
        return [
            'required_date.after' => 'Required date must be in the future.',
        ];
    }
}
```

---

#### 3. **API Resources for Output**

Consistent JSON structure using API Resources.

```php
// app/Http/Resources/DrawingSubmittalResource.php
class DrawingSubmittalResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'submittal_number' => $this->submittal_number,
            'revision' => $this->revision,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'approval_received_at' => $this->approval_received_at?->toISOString(),
            
            'project' => new ProjectResource($this->whenLoaded('project')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'files' => SubmittalFileResource::collection($this->whenLoaded('files')),
            'approvals' => SubmittalApprovalResource::collection($this->whenLoaded('approvals')),
            
            'permissions' => [
                'can_edit' => $request->user()->can('update', $this->resource),
                'can_submit' => $request->user()->can('submit', $this->resource),
                'can_delete' => $request->user()->can('delete', $this->resource),
            ],
        ];
    }
}
```

---

#### 4. **Policies for Authorization**

```php
// app/Policies/DrawingSubmittalPolicy.php
class DrawingSubmittalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_submittals');
    }
    
    public function view(User $user, DrawingSubmittal $submittal): bool
    {
        return $user->hasPermission('view_submittals')
            && ($user->id === $submittal->submitted_by_user_id || $user->isAdmin());
    }
    
    public function create(User $user): bool
    {
        return $user->hasRole(['detailer', 'manager', 'admin']);
    }
    
    public function submit(User $user, DrawingSubmittal $submittal): bool
    {
        return $submittal->status === 'ready_to_submit'
            && ($user->id === $submittal->submitted_by_user_id || $user->isAdmin());
    }
}
```

---

#### 5. **Job Queue for Long Operations**

```php
// app/Jobs/GenerateSubmittalPacket.php
class GenerateSubmittalPacket implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        public DrawingSubmittal $submittal
    ) {}
    
    public function handle(PacketGenerator $generator): void
    {
        $generator->generate($this->submittal);
        
        event(new SubmittalPacketGenerated($this->submittal));
    }
    
    public function failed(Throwable $exception): void
    {
        Log::error('Submittal packet generation failed', [
            'submittal_id' => $this->submittal->id,
            'exception' => $exception->getMessage(),
        ]);
    }
}
```

---

### Vue.js + Inertia Patterns

#### 1. **Page Components**

```vue
<!-- resources/js/Pages/Submittals/Index.vue -->
<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SubmittalCard from '@/Components/Submittals/SubmittalCard.vue';

const props = defineProps({
    submittals: Object,
    filters: Object,
});

const statusFilter = ref(props.filters.status || 'all');

const filteredSubmittals = computed(() => {
    if (statusFilter.value === 'all') return props.submittals.data;
    return props.submittals.data.filter(s => s.status === statusFilter.value);
});

function applyFilter() {
    router.get(route('submittals.index'), {
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Drawing Submittals" />
        
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters -->
                <!-- Submittal Grid -->
            </div>
        </div>
    </AppLayout>
</template>
```

---

#### 2. **Composables for Reusable Logic**

```javascript
// resources/js/Composables/useSubmittalStatus.js
import { computed } from 'vue';

export function useSubmittalStatus(submittal) {
    const statusColor = computed(() => {
        const colors = {
            draft: 'gray',
            submitted: 'blue',
            approved: 'green',
            'approved_as_noted': 'yellow',
            'revise_and_resubmit': 'orange',
            rejected: 'red',
        };
        return colors[submittal.status] || 'gray';
    });
    
    const statusIcon = computed(() => {
        const icons = {
            draft: 'PencilIcon',
            submitted: 'PaperAirplaneIcon',
            approved: 'CheckCircleIcon',
            'approved_as_noted': 'ExclamationCircleIcon',
            'revise_and_resubmit': 'ArrowPathIcon',
            rejected: 'XCircleIcon',
        };
        return icons[submittal.status] || 'QuestionMarkCircleIcon';
    });
    
    const canSubmit = computed(() => {
        return submittal.status === 'ready_to_submit' 
            && submittal.permissions.can_submit;
    });
    
    const canEdit = computed(() => {
        return ['draft', 'ready_to_submit'].includes(submittal.status)
            && submittal.permissions.can_edit;
    });
    
    return {
        statusColor,
        statusIcon,
        canSubmit,
        canEdit,
    };
}
```

---

### File Structure

```
app/
├── Console/           # Artisan commands
├── Events/            # Event classes
│   ├── DrawingRequest/
│   ├── Submittal/
│   └── FabQueue/
├── Exceptions/        # Custom exceptions
├── Http/
│   ├── Controllers/   # Route controllers
│   ├── Middleware/    # Custom middleware
│   ├── Requests/      # Form request validation
│   └── Resources/     # API resources
├── Jobs/              # Queue jobs
├── Listeners/         # Event listeners
├── Models/            # Eloquent models
├── Notifications/     # Notification classes
├── Policies/          # Authorization policies
├── Providers/         # Service providers
└── Services/          # Business logic services

resources/
├── js/
│   ├── Components/    # Reusable Vue components
│   ├── Composables/   # Vue composables
│   ├── Layouts/       # Page layouts
│   ├── Pages/         # Inertia pages
│   └── Stores/        # Pinia stores
├── css/
│   └── app.css        # Tailwind entry
└── views/
    └── reports/       # Blade templates for PDFs

database/
├── factories/         # Model factories for testing
├── migrations/        # Database migrations
└── seeders/           # Database seeders

tests/
├── Feature/           # Feature tests
└── Unit/              # Unit tests
```

---

## 🔌 Integration Points

### SteelFlow MRP Integration

**Current Status:** Standalone module, designed for future integration

**Integration Strategy:**

1. **Phase 1: Standalone** (Current)
   - DrawingFlow operates independently
   - Can optionally connect to SteelFlow database for customers/projects
   - Separate deployment

2. **Phase 2: API Integration**
   - DrawingFlow exposes REST API
   - SteelFlow calls DrawingFlow for drawing status
   - Bidirectional data sync via API

3. **Phase 3: Module Absorption**
   - Move DrawingFlow into `app/Modules/DrawingFlow/`
   - Share database, auth, and core services
   - Unified navigation and permissions
   - Single deployment

**Shared Resources (Future):**
- User authentication & authorization
- Customer database
- Project database
- File storage system
- Notification infrastructure
- Reporting engine

---

### External Integrations

**Email Processing:**
- Parse approval emails
- Extract approval type from email content
- Auto-update submittal status
- Attach approval PDFs

**Microsoft 365:**
- OAuth authentication (matches SteelFlow)
- SharePoint document storage (optional)
- Outlook calendar integration for deadlines

**Cloud Storage:**
- AWS S3 for file storage
- DigitalOcean Spaces alternative
- CloudFlare R2 option

**Mobile Access:**
- Responsive design for tablets
- Mobile-optimized PDF viewer
- QR code scanning for shop floor

---

## 📝 Code Style & Conventions

### PHP Style Guide

**PSR-12 Extended Coding Style**

```php
<?php

namespace App\Services;

use App\Models\DrawingSubmittal;
use Illuminate\Support\Facades\DB;

class SubmittalService
{
    /**
     * Submit a drawing for customer approval.
     *
     * @param DrawingSubmittal $submittal
     * @return bool
     * @throws \Exception
     */
    public function submit(DrawingSubmittal $submittal): bool
    {
        if ($submittal->status !== 'ready_to_submit') {
            throw new \Exception('Submittal must be ready to submit');
        }

        return DB::transaction(function () use ($submittal) {
            $submittal->update([
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            // Additional logic...

            return true;
        });
    }
}
```

**Key Rules:**
- Type hints everywhere
- DocBlocks for public methods
- Descriptive variable names
- Early returns over nested conditionals
- Exceptions for exceptional cases

---

### Vue.js Style Guide

**Composition API, `<script setup>` syntax**

```vue
<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    submittal: Object,
    required: true,
});

const emit = defineEmits(['submitted', 'cancelled']);

const isSubmitting = ref(false);

const canSubmit = computed(() => {
    return props.submittal.status === 'ready_to_submit'
        && !isSubmitting.value;
});

async function handleSubmit() {
    if (!canSubmit.value) return;
    
    isSubmitting.value = true;
    
    try {
        await router.post(
            route('submittals.submit', props.submittal.id),
            {},
            {
                onSuccess: () => emit('submitted'),
                onError: (errors) => console.error(errors),
            }
        );
    } finally {
        isSubmitting.value = false;
    }
}

onMounted(() => {
    // Component mounted
});
</script>

<template>
    <button
        @click="handleSubmit"
        :disabled="!canSubmit"
        class="btn btn-primary"
    >
        Submit Drawing
    </button>
</template>
```

**Key Rules:**
- Composition API preferred
- `<script setup>` for cleaner syntax
- Computed properties for derived state
- Emit events, don't mutate props
- Descriptive function names

---

### Database Conventions

**Table Names:**
- Plural, snake_case
- Examples: `drawing_requests`, `submittal_files`, `pdf_markups`

**Column Names:**
- Snake_case
- Foreign keys: `{model}_id` (e.g., `project_id`, `customer_id`)
- Timestamps: `created_at`, `updated_at`, `deleted_at`
- Booleans: `is_{attribute}` or `has_{attribute}` (e.g., `is_current`, `has_approval`)

**Indexes:**
- Foreign keys always indexed
- Status columns indexed
- Search fields indexed (with `FULLTEXT` where appropriate)
- Composite indexes for common query patterns

**Migrations:**
- One table per migration (except pivot tables)
- Clear `up()` and `down()` methods
- Use `$table->foreignId()` for foreign keys
- Include indexes in initial table creation

---

## 🧪 Testing Strategy

### Test Coverage Goals

- **Unit Tests:** 80%+ coverage
- **Feature Tests:** All critical workflows
- **Browser Tests:** Key user journeys (Dusk)

### Unit Tests

```php
// tests/Unit/Services/SubmittalServiceTest.php
use Tests\TestCase;
use App\Services\SubmittalService;
use App\Models\DrawingSubmittal;

class SubmittalServiceTest extends TestCase
{
    protected SubmittalService $service;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubmittalService();
    }
    
    /** @test */
    public function it_submits_a_ready_submittal()
    {
        $submittal = DrawingSubmittal::factory()->create([
            'status' => 'ready_to_submit',
        ]);
        
        $result = $this->service->submit($submittal);
        
        $this->assertTrue($result);
        $this->assertEquals('submitted', $submittal->fresh()->status);
        $this->assertNotNull($submittal->fresh()->submitted_at);
    }
    
    /** @test */
    public function it_throws_exception_for_non_ready_submittal()
    {
        $this->expectException(\Exception::class);
        
        $submittal = DrawingSubmittal::factory()->create([
            'status' => 'draft',
        ]);
        
        $this->service->submit($submittal);
    }
}
```

---

### Feature Tests

```php
// tests/Feature/SubmittalWorkflowTest.php
use Tests\TestCase;
use App\Models\User;
use App\Models\DrawingSubmittal;

class SubmittalWorkflowTest extends TestCase
{
    /** @test */
    public function detailer_can_submit_a_ready_drawing()
    {
        $user = User::factory()->detailer()->create();
        $submittal = DrawingSubmittal::factory()->create([
            'status' => 'ready_to_submit',
            'submitted_by_user_id' => $user->id,
        ]);
        
        $response = $this->actingAs($user)
            ->post(route('submittals.submit', $submittal));
        
        $response->assertRedirect();
        $this->assertEquals('submitted', $submittal->fresh()->status);
    }
    
    /** @test */
    public function non_detailer_cannot_submit_drawing()
    {
        $user = User::factory()->create(); // No detailer role
        $submittal = DrawingSubmittal::factory()->create([
            'status' => 'ready_to_submit',
        ]);
        
        $response = $this->actingAs($user)
            ->post(route('submittals.submit', $submittal));
        
        $response->assertForbidden();
    }
}
```

---

### Browser Tests (Dusk)

```php
// tests/Browser/SubmittalFlowTest.php
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SubmittalFlowTest extends DuskTestCase
{
    /** @test */
    public function detailer_can_complete_full_submittal_flow()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->detailer()->create())
                ->visit('/drawing-requests')
                ->clickLink('Start Drawing')
                ->type('title', 'Test Drawing')
                ->select('drawing_type', 'structural')
                ->press('Create')
                ->assertSee('Drawing request created')
                ->click('@submit-button')
                ->assertSee('Drawing submitted for approval');
        });
    }
}
```

---

## 🚀 Deployment & Environments

### Environments

**Development:**
- Local Docker containers
- `.env` with `APP_ENV=local`
- Debug mode enabled
- Mailhog for email testing
- Xdebug enabled

**Staging:**
- Cloud hosting (DigitalOcean, AWS, etc.)
- `.env` with `APP_ENV=staging`
- Debug mode enabled (restricted IPs)
- Real email via configured driver
- Similar to production

**Production:**
- Cloud hosting with auto-scaling
- `.env` with `APP_ENV=production`
- Debug mode disabled
- Queue workers via Horizon
- Redis for cache/sessions
- CDN for static assets
- Automated backups

---

### Deployment Process

**Via GitHub Actions (CI/CD):**

```yaml
# .github/workflows/deploy.yml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      
      - name: Install Dependencies
        run: composer install --no-dev --optimize-autoloader
      
      - name: Run Tests
        run: php artisan test
      
      - name: Build Assets
        run: |
          npm ci
          npm run build
      
      - name: Deploy to Server
        run: |
          # SSH deploy commands
          # Run migrations
          # Clear caches
          # Restart queue workers
```

---

## 🤖 AI Agent Guidelines

### When You're Working on DrawingFlow

**Context You Should Know:**

1. **User:** Mark is a steel fabricator with 30+ years experience. He knows the industry inside-out but prefers practical solutions over over-engineered complexity.

2. **Problem:** We're replacing a fragmented Microsoft Lists workflow with a unified system.

3. **Tech Stack:** Laravel 12 + Vue 3 + Inertia + Tailwind, mirroring SteelFlow MRP.

4. **End Goal:** Standalone module that eventually integrates into SteelFlow MRP.

---

### Code Generation Guidelines

**DO:**
- Follow PSR-12 for PHP
- Use type hints everywhere
- Write tests for new features
- Use service classes for business logic
- Leverage Laravel best practices
- Write descriptive commit messages
- Add comments for complex logic
- Use API resources for JSON output
- Follow established naming conventions

**DON'T:**
- Put business logic in models
- Skip validation
- Hardcode values (use config)
- Write long methods (extract to smaller methods)
- Ignore existing patterns
- Skip tests
- Over-engineer simple features

---

### Feature Development Process

**When Mark asks for a new feature:**

1. **Understand the requirement:**
   - Ask clarifying questions
   - Understand the real-world use case
   - Identify edge cases

2. **Plan the implementation:**
   - Database changes needed?
   - Service classes required?
   - Frontend components?
   - API endpoints?
   - Tests to write?

3. **Show the plan before coding:**
   - Outline database migrations
   - Describe service methods
   - Sketch component hierarchy
   - Get approval on approach

4. **Implement incrementally:**
   - Start with database migrations
   - Then models & relationships
   - Then service layer
   - Then API endpoints
   - Then frontend components
   - Finally, tests

5. **Test thoroughly:**
   - Unit tests for services
   - Feature tests for workflows
   - Browser tests for critical flows

---

### Debugging Approach

**When Mark reports a bug:**

1. **Reproduce the issue:**
   - Ask for steps to reproduce
   - Check logs for errors
   - Verify data state

2. **Identify root cause:**
   - Database query issue?
   - Business logic error?
   - Frontend bug?
   - Configuration problem?

3. **Propose solution:**
   - Explain what's wrong
   - Suggest fix approach
   - Consider side effects

4. **Implement & test:**
   - Write failing test
   - Fix the bug
   - Verify test passes
   - Check for regressions

---

### Communication Style

**With Mark:**
- Be direct and practical
- Avoid jargon unless necessary
- Explain trade-offs clearly
- Offer alternatives when appropriate
- Acknowledge when something is complex
- Suggest simpler approaches when possible

**Example Good Response:**
```
I see what you're after. Here are two approaches:

1. Simple: Add a status filter dropdown. Takes 30 minutes, works great for most cases.
2. Advanced: Full query builder with saved filters. Takes 4 hours, super flexible but might be overkill.

I'd start with #1 and add #2 later if needed. Sound good?
```

**Example Bad Response:**
```
We'll need to implement a polymorphic filter factory with a strategy pattern 
and repository abstraction layer using dependency injection to achieve optimal 
separation of concerns...
```

---

### When You're Stuck

**If you don't understand something:**
- Ask Mark to clarify
- Reference this AGENTS.md
- Check SteelFlow MRP repo for patterns
- Suggest we document the decision

**If multiple approaches exist:**
- Present 2-3 options with trade-offs
- Recommend one based on simplicity/maintainability
- Let Mark decide

**If something seems overcomplex:**
- Say so
- Suggest simpler alternative
- Explain why simpler might be better

---

### References & Resources

**SteelFlow MRP:**
- GitHub: https://github.com/meistro57/SteelFlow-MRP
- Contains patterns and structures to mirror

**Laravel Documentation:**
- https://laravel.com/docs/12.x

**Vue 3 Documentation:**
- https://vuejs.org/guide/introduction.html

**Inertia.js Documentation:**
- https://inertiajs.com/

**Tailwind CSS Documentation:**
- https://tailwindcss.com/docs

---

## 🎯 Current Development Focus

**Phase 2: File Uploads + Filtering + Tests** (Complete as of March 2026)

**Phase 0: Foundation** ✅ Complete
1. ✅ Create GitHub repository
2. ✅ Write comprehensive AGENTS.md
3. ✅ Extract customer/project models from SteelFlow
4. ✅ Set up fresh Laravel 12 project
5. ✅ Configure Docker environment
6. ✅ Create initial database migrations
7. ✅ Set up authentication
8. ✅ Build dashboard skeleton

**Phase 1: Core Workflow CRUD** ✅ Complete
- ✅ Drawing request CRUD (create, edit, list, show, delete)
- ✅ Status transitions (assign, mark ready, cancel, hold)
- ✅ Submittal workflow (create from request, submit, process approval, create revision)
- ✅ Fab queue (assign, complete, notes)
- ✅ Dashboard with live stats
- ✅ Customer & Project management
- ✅ Services: DrawingRequestService, SubmittalService, FabHandoffService

**Phase 2: File Uploads + Filtering + Tests** ✅ Complete
- ✅ File upload system (SubmittalFileController, FileUpload.vue component)
- ✅ File download and delete for submittal attachments
- ✅ Files section on Submittals/Show with drag-and-drop uploader
- ✅ Status + priority + search filtering on Drawing Requests index
- ✅ Status + search filtering on Submittals index
- ✅ Model factories for Customer, Project, DrawingRequest, DrawingSubmittal
- ✅ Feature tests: DrawingRequestTest (11 tests)
- ✅ Feature tests: SubmittalTest (10 tests)

**Phase 3: Next Up**
- PDF viewer integration (in-browser PDF rendering)
- PDF markup tools (highlight, annotate, stamp)
- Email notifications (submittal submitted, approval received)
- Customer portal / external approval link
- Reporting and export (CSV, PDF reports)
- Role-based access control (admin vs detailer vs viewer)

---

## 📞 Getting Help

**If you're an AI agent working on this project:**
- This document is your primary reference
- When in doubt, ask Mark
- Reference SteelFlow MRP for patterns
- Prioritize simplicity and practicality
- Test everything

**If you're a human contributor:**
- Read this document thoroughly
- Check SteelFlow MRP for context
- Follow established patterns
- Write tests
- Ask questions in issues

---

**Built with ❤️ for steel fabricators who deserve better tools.**
