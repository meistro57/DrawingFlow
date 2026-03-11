# 🎨 DrawingFlow

[![License](https://img.shields.io/badge/License-MIT-yellow.svg?style=for-the-badge)](LICENSE)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com)
[![Vue](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js)](https://vuejs.org)
[![Tailwind](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css)](https://tailwindcss.com)

**DrawingFlow** is a precision-engineered workflow management system for steel fabrication shop drawings. Built to replace the chaos of multiple Microsoft Lists with one unified, intelligent pipeline from customer request through approval to fabrication handoff.

> *"Because tracking drawings shouldn't require three different lists and a prayer."* - Mark, Steel Fabricator

---

## 🎯 The Problem

Steel fabrication shops deal with complex drawing workflows that involve multiple stakeholders, approval stages, and handoffs. The traditional approach? **Three separate Microsoft Lists:**

1. **Shop Drawing Request** - Incoming work queue
2. **Drawing Submittal Log** - Design, submit, track approvals
3. **Fabrication Drawing Log** - Handoff to fab team

**The result?** Manual data entry, lost information between stages, no automated workflows, zero visibility, and pure frustration.

---

## ✨ The Solution

**DrawingFlow** replaces the three-list nightmare with one intelligent system:

```
📋 Request → 🎨 Design → 📤 Submit → ✅ Approve → 🏭 Fabricate
```

### Core Features

🚀 **Unified Workflow**
- Single source of truth for drawing lifecycle
- Automatic status transitions based on approval type
- Real-time visibility across all stages
- Zero manual data re-entry

📄 **PDF Magic**
- Built-in PDF viewer with markup tools
- Compare revisions side-by-side
- Annotation history tracking
- Export marked-up PDFs

🔔 **Smart Notifications**
- Real-time alerts when approvals arrive
- Automated reminders for stalled drawings
- Slack/Email integration
- Mobile-friendly notifications

👥 **Customer Workflows**
- Define approval chains per customer (GC → Architect → Engineer)
- Track approval speed metrics
- Custom submittal methods (email, portal, physical)
- SLA management and escalation

🏗️ **Fab Handoff**
- Seamless transition from approval to production
- Material requirements extraction
- Priority queue management
- Shop floor tablet view

📊 **Analytics & Insights**
- Time tracking per workflow stage
- Bottleneck identification
- Approval rate by customer
- Workload balancing

---

## 🛠️ Tech Stack

### The Foundation

- **Backend**: Laravel 12 (PHP 8.3+) - Modern, powerful, battle-tested
- **Frontend**: Vue.js 3 + Inertia.js - SPA feel without API complexity
- **Styling**: Tailwind CSS 3 + Headless UI - Beautiful, accessible components
- **Database**: MySQL 8.0 / PostgreSQL 15+ - Rock-solid data layer
- **Cache/Queue**: Redis 7 + Laravel Horizon - Fast, reliable background processing
- **Search**: Meilisearch - Sub-millisecond document search
- **Files**: S3-compatible storage - Scalable file management

### Why This Stack?

✅ Matches SteelFlow MRP ecosystem for eventual integration  
✅ Modern PHP with type safety and performance  
✅ Vue 3 Composition API for maintainable components  
✅ Inertia.js for rapid development without API overhead  
✅ Docker for consistent development environments  

---

## 🚀 Quick Start

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker Engine + Docker Compose)
- [Node.js](https://nodejs.org/) 18+ (for local asset compilation)
- Git

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/meistro57/DrawingFlow.git
cd DrawingFlow

# 2. Copy environment configuration
cp .env.example .env

# 3. Start Docker containers
docker compose up -d

# 4. Install PHP dependencies
docker compose exec app composer install

# 5. Generate application key
docker compose exec app php artisan key:generate

# 6. Run database migrations
docker compose exec app php artisan migrate --seed

# 7. Install frontend dependencies
npm install

# 8. Build assets (development)
npm run dev

# Or build for production
npm run build
```

### Access the Application

- **Web Interface**: http://localhost:8080
- **Mailhog (Email Testing)**: http://localhost:8025
- **Horizon (Queue Dashboard)**: http://localhost:8080/horizon

### Default Credentials

```
Email: mark@drawingflow.local
Email: detailer@drawingflow.local
Password: password
```

### File Upload Limits

- Project attachments are enabled on both create and edit forms.
- Maximum file size: `30MB` per file.
- Maximum request body size is configured higher at the container level to support multi-file uploads.
- If you change upload limits, rebuild/restart containers so PHP and Nginx pick up the new settings.

---

## 📚 Documentation

### For Developers

- **[AGENTS.md](AGENTS.md)** - Comprehensive AI agent guide with database schema, workflows, and development patterns
- **README.md** - Local setup, workflow, and feature overview

### API Documentation

Coming soon - full REST API documentation with Postman collections.

---

## 🗺️ Roadmap

### ✅ Phase 0: Foundation (In Progress)
- [x] GitHub repository setup
- [x] Comprehensive documentation (AGENTS.md)
- [ ] Extract customer/project models from SteelFlow
- [ ] Docker development environment
- [ ] Initial database migrations
- [ ] Authentication system
- [ ] Dashboard skeleton

### 📋 Phase 1: Core Workflow (Weeks 3-4)
- [ ] Drawing request CRUD
- [x] Drawing submittal CRUD with file upload
- [ ] Basic status system
- [ ] Customer & project associations
- [ ] Simple list views
- [x] File attachment system

### 🧠 Phase 2: Workflow Intelligence (Weeks 5-6)
- [ ] Auto-status transitions based on approval type
- [ ] Submittal approval tracking
- [ ] Revision system (A → B → C)
- [ ] Status history timeline
- [ ] Notes/comments system
- [ ] Email notifications
- [ ] Dashboard with "My Queue" view

### 📄 Phase 3: PDF Magic (Weeks 7-8)
- [ ] Embedded PDF viewer
- [ ] Markup tools (circles, arrows, text, highlights)
- [ ] Markup save/export
- [ ] Revision comparison side-by-side
- [ ] Stamp library (approval stamps)
- [ ] Markup history tracking
- [ ] Mobile-responsive viewer

### 💬 Phase 4: Communication Hub (Weeks 9-10)
- [ ] Real-time notifications
- [ ] Internal chat/notes per drawing
- [ ] @mention functionality
- [ ] Email integration (send submittals)
- [ ] Approval email parsing
- [ ] Customer portal (read-only view)
- [ ] Automated reminder system

### 🏭 Phase 5: Fab Handoff (Weeks 11-12)
- [ ] Fab queue management
- [ ] Material requirements field
- [x] CNC file attachment
- [ ] Priority system
- [ ] Shop floor tablet view
- [ ] Bulk handoff tools
- [ ] Print/export fabrication packets

### 📊 Phase 6: Intelligence & Insights (Weeks 13-14)
- [ ] Analytics dashboard (time per stage, approval rates, bottlenecks)
- [ ] Saved filters & custom views
- [ ] Advanced search
- [ ] Bulk operations
- [ ] Templates for common drawing types
- [ ] Drawing numbering automation

### 📈 Phase 7: Project Management (Weeks 15-16)
- [ ] Project timeline view
- [ ] Drawing dependencies
- [ ] Milestone tracking
- [ ] Critical path visualization
- [ ] Gantt chart view
- [ ] Budget tracking per project

### 🔌 Phase 8: Integrations & Polish (Weeks 17-18)
- [ ] Microsoft Lists import tool
- [ ] Calendar integration
- [ ] Slack/Teams webhooks
- [ ] API for SteelFlow integration
- [ ] Mobile app optimization
- [ ] Keyboard shortcuts
- [ ] QR code generation

### 🚀 Phase 9: Advanced Features (Weeks 19-20)
- [ ] Conditional routing workflows
- [ ] Multi-reviewer approval chains
- [ ] Customer-specific workflow customization
- [ ] Predictive approval timelines (AI/ML)
- [ ] Advanced file management (DWG preview)
- [ ] Automated submittal packet generation

### 🎯 Phase 10: Battle Testing & SteelFlow Integration (Weeks 21-24)
- [ ] Real-world testing
- [ ] Performance optimization
- [ ] Bug fixes & refinements
- [ ] User feedback implementation
- [ ] Documentation
- [ ] SteelFlow integration planning
- [ ] Database migration strategy
- [ ] Production deployment

---

## 🏗️ Architecture

### Database Schema Overview

```
customers (1) ─── (∞) projects (1) ─── (∞) drawing_requests
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

### Service Layer

DrawingFlow follows a service-oriented architecture:

- **Models** - Handle database relationships and data access
- **Services** - Contain business logic and complex workflows
- **Events** - Trigger automated actions across the system
- **Jobs** - Process long-running tasks in background queues
- **Policies** - Control authorization and permissions

See [AGENTS.md](AGENTS.md) for complete architectural details.

---

## 🤝 Integration with SteelFlow MRP

DrawingFlow is designed as a **standalone module** that will eventually integrate into the [SteelFlow MRP](https://github.com/meistro57/SteelFlow-MRP) ecosystem.

### Integration Strategy

**Phase 1: Standalone** (Current)
- Independent operation
- Optional connection to SteelFlow database
- Separate deployment

**Phase 2: API Integration**
- REST API for data exchange
- Bidirectional sync
- Unified authentication

**Phase 3: Module Absorption**
- Move to `app/Modules/DrawingFlow/` in SteelFlow
- Shared database, auth, and core services
- Single unified deployment

### Shared Resources (Future)

- User authentication & authorization
- Customer database
- Project database
- File storage system
- Notification infrastructure
- Reporting engine

---

## 🧪 Testing

DrawingFlow follows a comprehensive testing strategy:

### Test Coverage Goals

- **Unit Tests**: 80%+ coverage
- **Feature Tests**: All critical workflows
- **Browser Tests**: Key user journeys

### Running Tests

```bash
# Run all tests
docker-compose exec app php artisan test

# Run specific test suite
docker-compose exec app php artisan test --testsuite=Feature

# Run with coverage report
docker-compose exec app php artisan test --coverage

# Run browser tests (Dusk)
docker-compose exec app php artisan dusk
```

---

## 🤖 AI-Powered Development

DrawingFlow is built with AI pair programming in mind. The comprehensive [AGENTS.md](AGENTS.md) file provides:

- Complete database schema with SQL
- Business logic and workflow definitions
- Service architecture patterns
- Code style guidelines
- Development best practices
- AI-specific collaboration guidelines

This makes it easy for AI assistants to:
- Generate accurate code that follows project patterns
- Understand business requirements and constraints
- Suggest implementations that match existing architecture
- Debug issues with full context

---

## 📦 Project Structure

```
DrawingFlow/
├── app/
│   ├── Console/           # Artisan commands
│   ├── Events/            # Event classes (DrawingRequest, Submittal, FabQueue)
│   ├── Http/
│   │   ├── Controllers/   # Route controllers
│   │   ├── Requests/      # Form validation
│   │   └── Resources/     # API resources
│   ├── Jobs/              # Queue jobs
│   ├── Listeners/         # Event listeners
│   ├── Models/            # Eloquent models
│   ├── Notifications/     # Notification classes
│   ├── Policies/          # Authorization
│   └── Services/          # Business logic
├── database/
│   ├── factories/         # Model factories
│   ├── migrations/        # Database migrations
│   └── seeders/           # Database seeders
├── resources/
│   ├── css/               # Tailwind CSS
│   ├── js/
│   │   ├── Components/    # Vue components
│   │   ├── Composables/   # Reusable logic
│   │   ├── Layouts/       # Page layouts
│   │   ├── Pages/         # Inertia pages
│   │   └── Stores/        # Pinia stores
│   └── views/             # Blade templates (PDFs)
├── tests/
│   ├── Feature/           # Feature tests
│   ├── Unit/              # Unit tests
│   └── Browser/           # Dusk browser tests
├── docker/                # Docker configuration
├── docs/                  # Additional documentation
├── AGENTS.md              # AI development guide
└── README.md              # You are here
```

---

## 🌟 Key Features in Detail

### Drawing Request Workflow

1. **Request Creation** - Office staff creates drawing request
2. **Assignment** - Auto-assigned to detailer based on workload
3. **Design Work** - Detailer creates drawings
4. **Submittal** - Package and submit to customer
5. **Approval Tracking** - Monitor approval status
6. **Fab Handoff** - Seamless transition to fabrication

### Approval Types

DrawingFlow automatically handles different approval responses:

| Approval Type | Action | Fab Queue | Revision |
|--------------|--------|-----------|----------|
| **Approved** | → Fab Queue | ✅ Created | No |
| **Approved as Noted** | → Fab Queue (flagged) | ✅ Created | No |
| **Revise & Resubmit** | → Back to Design | ❌ Not Created | ✅ A → B |
| **Rejected** | → Escalate | ❌ Not Created | No |
| **Field Verify Required** | → Assign Field Tech | ⏸️ On Hold | No |

### PDF Markup Tools

- **Circle** - Highlight areas of concern
- **Arrow** - Point to specific elements
- **Text** - Add comments and notes
- **Highlight** - Mark important sections
- **Stamp** - Apply approval stamps
- **Dimension** - Call out measurements

All markups are:
- Saved with user attribution
- Tracked in history
- Exportable as new PDF
- Mobile-friendly

---

## 🎨 Screenshots

*Coming soon - we're building the thing first!*

---

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on:

- Code of conduct
- Development workflow
- Pull request process
- Coding standards
- Testing requirements

### Getting Help

- 📖 Read the [AGENTS.md](AGENTS.md) for comprehensive project documentation
- 🐛 Report bugs via [GitHub Issues](https://github.com/meistro57/DrawingFlow/issues)
- 💡 Suggest features via [GitHub Discussions](https://github.com/meistro57/DrawingFlow/discussions)
- 📧 Email: [Your contact email here]

---

## 📄 License

DrawingFlow is open-source software licensed under the [MIT license](LICENSE).

---

## 🙏 Acknowledgments

### Inspired By

- **FabTrol** - The legendary MRP system that set the standard for steel fabrication software
- **SteelFlow MRP** - The spiritual successor, providing patterns and architecture

### Built For

Steel fabricators who deserve modern tools that actually understand their workflow.

### Special Thanks

- The steel fabrication community for feedback and insights
- Laravel community for an amazing framework
- Vue.js team for making frontend development enjoyable
- All contributors who help make DrawingFlow better

---

## 🚀 The Vision

DrawingFlow started as a solution to a specific pain point: managing shop drawings in a steel fabrication shop. But the vision is bigger.

We're building a system that:
- **Respects craftsmanship** - Built by fabricators for fabricators
- **Embraces modern tech** - No compromises on developer experience
- **Stays practical** - Solves real problems, not imaginary ones
- **Remains maintainable** - Code that future developers won't hate
- **Integrates seamlessly** - Part of a larger ecosystem (SteelFlow MRP)

### Why Open Source?

Because the steel fabrication industry deserves better software. By open-sourcing DrawingFlow, we hope to:

1. Enable shops to customize it for their specific needs
2. Learn from the community and improve continuously
3. Lower the barrier to entry for modern shop management software
4. Give back to the industry that's given us so much

---

## 📊 Project Status

**Current Phase**: Foundation (Phase 0)  
**Status**: Active Development  
**Version**: 0.1.0-alpha  
**Next Milestone**: Phase 1 - Core Workflow Implementation

### Recent Activity

Check out the [CHANGELOG.md](CHANGELOG.md) for detailed version history.

---

## 🎯 Who Is This For?

### Primary Users

- **Steel Fabricators** - Manage shop drawings from request to fab
- **Detailers** - Track submittal status and revisions
- **Shop Managers** - Visibility across all active drawings
- **Project Managers** - Monitor project drawing timelines

### Industries

- Structural Steel Fabrication
- Miscellaneous Metals
- Railings & Ornamental Iron
- Industrial Fabrication
- Custom Metal Work

---

## 💬 Community

Join the DrawingFlow community:

- **GitHub Discussions** - Feature requests, questions, ideas
- **Discord** - *Coming soon*
- **Twitter** - *Coming soon*

---

## 🔮 Future Possibilities

Ideas we're exploring for future versions:

- **Mobile App** - Native iOS/Android for field use
- **AI-Powered Approval Prediction** - Learn from history to predict approval times
- **3D Model Integration** - Preview 3D models alongside 2D drawings
- **Collaborative Markup** - Real-time multi-user markup sessions
- **Voice Commands** - Shop floor control via voice
- **AR Overlay** - Augmented reality drawing overlay for field verification
- **Blockchain Certification** - Immutable approval records
- **Integration Marketplace** - Connect with other fabrication tools

Got ideas? Share them in [GitHub Discussions](https://github.com/meistro57/DrawingFlow/discussions)!

---

<div align="center">

**Built with ❤️ for steel fabricators who deserve better tools.**

[⭐ Star this repo](https://github.com/meistro57/DrawingFlow) | [🐛 Report Bug](https://github.com/meistro57/DrawingFlow/issues) | [💡 Request Feature](https://github.com/meistro57/DrawingFlow/discussions)

</div>
