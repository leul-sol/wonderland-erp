# Wonderland ERP — Hotel Go-Live & Operations Runbook

Operational guide for a hotel that receives the full Wonderland ERP system and starts using it from day one.

**Audience:** General manager, department heads, IT, finance, HR, front office, F&B, inventory.  
**Portal:** Staff web app (Laravel + Inertia + Vue BFF).  
**Backends:** S1 Identity, S2 Workforce/Payroll, S3 Hospitality, S4 Finance & BI.

Related specs: [`specs/platform/roles.yaml`](../specs/platform/roles.yaml), [`specs/ui/phases.yaml`](../specs/ui/phases.yaml), [`web-portal/README.md`](../web-portal/README.md).

---

## Table of contents

1. [What the hotel gets](#1-what-the-hotel-gets)
2. [Roles and who sees what](#2-roles-and-who-sees-what)
3. [Phase A — One-time setup (go-live)](#3-phase-a--one-time-setup-go-live)
4. [Phase B — Daily operations](#4-phase-b--daily-operations)
5. [Phase C — Weekly and HR/payroll cycle](#5-phase-c--weekly-and-hrpayroll-cycle)
6. [Phase D — Monthly finance cycle](#6-phase-d--monthly-finance-cycle)
7. [Notifications and approvals](#7-notifications-and-approvals)
8. [How modules connect](#8-how-modules-connect)
9. [Suggested rollout calendar](#9-suggested-rollout-calendar)
10. [Acceptance checklist](#10-acceptance-checklist)
11. [Operations (backup & reset)](#11-operations-backup--reset)
12. [Role cheat sheets](#12-role-cheat-sheets)

---

## 1. What the hotel gets

One **staff portal** where each employee logs in and sees only the modules for their job. Business rules live in four backend systems:

| System | Responsibility |
|--------|----------------|
| **S1** Identity | Logins, users, roles, permissions, audit trail |
| **S2** Workforce | Employees, leave, attendance, overtime, payroll, severance |
| **S3** Hospitality | Rooms, reservations, folios, F&B, inventory, procurement, group bookings, staff meals |
| **S4** Finance & BI | Chart of accounts, journals, payables/receivables, fiscal periods, budgets, dashboards, reports |

**Design principle:** Staff use the browser portal; APIs enforce permissions and workflows. Cross-system links are automatic (e.g. F&B charge on a folio, PO → payable, staff meals → payroll deduction, payroll → journal).

---

## 2. Roles and who sees what

Twelve seeded roles drive sidebar navigation and quick tasks. Assign roles in **Administration → Users**.

| Role | Primary responsibility in the portal |
|------|--------------------------------------|
| **Super administrator** | Users, roles, permissions, audit; full access |
| **General manager** | Executive oversight; dashboards; GM-tier PO and large journal approval |
| **Finance manager** | GL, reports, fiscal close, finance-tier PO approval, payables |
| **Accountant** | Journals, trial balance, receivables/payables |
| **HR manager** | Employees, org structure, leave, attendance, settings |
| **Payroll officer** | Payroll runs, severance |
| **Department head** | Leave approval; first-tier PO approval |
| **Inventory manager** | Stock, suppliers, purchase orders, goods receipt |
| **Restaurant manager** | Menu, orders, catalog admin, staff meal periods |
| **Receptionist** | Reservations, check-in/out, rooms, guests, folios |
| **Cashier** | Folio settlement, F&B billing, cashier shifts |
| **Report viewer** | Read-only dashboards and reports |

**Home dashboard (`/`):** Role-based KPIs and pending approvals — not the finance BI dashboards (those live under **Finance → Dashboards**).

### 2.1 Examples by role

Use these as templates when creating users in **Administration → Users**. Usernames are examples only — your hotel chooses its own naming (e.g. `firstname.lastname`).

| Role | Example staff | Example username | What they see in the portal | Example task today |
|------|---------------|------------------|----------------------------|-------------------|
| **Super administrator** | IT lead or owner (technical) | `admin.tekalign` | All modules including **Administration** | Create account for new receptionist; review audit log after password reset |
| **General manager** | Hotel GM | `gm.sara` | Home dashboard, **Finance → Dashboards**, read-only across ops, approval notifications | Approve a 120,000 ETB purchase order (tier 3); approve a large manual journal |
| **Finance manager** | Finance & accounts lead | `finance.helina` | **Finance** (full), **Inventory → Purchase orders** (approve tier 2) | Settle supplier payable from last week’s goods receipt; close fiscal period P06 |
| **Accountant** | Junior accountant / bookkeeper | `accounts.yonas` | **Finance → Journals**, **Reports**, **Receivables/Payables** (no user admin) | Post trial balance review; create draft journal for bank charges; settle a receivable |
| **HR manager** | HR & training manager | `hr.meron` | **HR** (employees, leave, attendance, org, settings) | Onboard new housekeeper employee record; approve annual leave for front desk |
| **Payroll officer** | Payroll clerk | `payroll.dawit` | **Payroll → Payroll runs**, **Severance** | Run June payroll → submit for approval → lock after GM/finance sign-off |
| **Department head** | F&B head chef or HK supervisor | `fb.head.tigist` | **HR → Leave** (approve), **Inventory → POs** (approve tier 1), dept-related read | Approve leave for waiter; approve kitchen’s weekly vegetable PO |
| **Inventory manager** | Stores / procurement officer | `stores.biniam` | **Inventory** (items, suppliers, POs, alerts, valuation) | Create PO for linen; receive goods when delivery arrives; act on low-stock alert |
| **Restaurant manager** | Restaurant outlet manager | `restaurant.amen` | **Restaurant and F&B**, **Staff meals** | Update menu price; post dinner order to room 201 folio; open staff meal period |
| **Receptionist** | Front desk agent (day shift) | `frontdesk.hanna` | **Front desk** (rooms, reservations, guests, folios) | Check in walk-in guest → assign room 102 → add minibar charge to folio |
| **Cashier** | Front desk cashier / F&B cashier | `cashier.solomon` | **Front desk** (folios, cashier shifts), **F&B → Orders** | Settle folio for checkout in cash; close cashier shift; take walk-in restaurant payment |
| **Report viewer** | Owner’s rep or auditor (read-only) | `reports.audit` | **Finance → Dashboards**, **Reports**, read-only KPIs on home dashboard | Review executive dashboard occupancy and revenue; export trial balance PDF — no edits |

### 2.2 Example: one purchase order through three roles

A kitchen manager needs ingredients. The same PO moves through different people: 

1. **Inventory manager** (`stores.biniam`) — creates PO #PO-00045 for ETB 18,000 → status `pending_dept_head`  
2. **Department head** (`fb.head.tigist`) — sees bell notification → approves → `pending_finance`  
3. **Finance manager** (`finance.helina`) — approves → `pending_gm` (if amount/tier requires GM) or `approved`  
4. **General manager** (`gm.sara`) — only if tier 3 → final approve  
5. **Inventory manager** — goods receipt → stock updated  
6. **Finance manager** — **Payables** → settle when invoice is paid  

### 2.3 Example: one guest stay (reception + restaurant + cashier)

| Step | Who | Action |
|------|-----|--------|
| 1 | **Receptionist** `frontdesk.hanna` | Reservation for Mr. Bekele, 2 nights |
| 2 | **Receptionist** | Check in → room 201, folio opened |
| 3 | **Restaurant manager** `restaurant.amen` | Dinner order posted to folio room 201 |
| 4 | **Cashier** `cashier.solomon` | Guest settles folio (room + F&B + tax) at checkout |
| 5 | **Receptionist** | Check out → room 201 available |

### 2.4 Example: month-end (finance + GM)

| Who | Action |
|-----|--------|
| **Accountant** | Review trial balance; post adjusting journals |
| **Finance manager** | Notification: fiscal period ending in 3 days → prepare close |
| **Finance manager** | **Fiscal periods** → close June → lock period |
| **General manager** | **Finance → Dashboards → Executive** — review occupancy, revenue, payroll cost |

### 2.5 Who should **not** get which role

| Avoid | Why |
|-------|-----|
| Giving every staff **Super administrator** | They could change roles, reset passwords, see audit — security risk |
| Giving receptionists **Finance manager** | They don’t need GL; increases error and fraud risk |
| Using **Report viewer** for daily ops staff | Read-only — they cannot check in guests or approve POs |
| One shared login for whole front desk | Audit cannot show who checked in a guest; create one user per person |

**Tip:** A person can hold **one primary role**. If someone wears two hats (e.g. GM + finance), assign the role that matches their **approval** duties; use **Super administrator** only for IT, not for daily GM work.

---

## 3. Phase A — One-time setup (go-live)

Complete these steps **once**, in order, before relying on the system for live operations.

### 3.1 Platform and security (Super administrator — Day 1)

| Step | Portal path | Outcome |
|------|-------------|---------|
| 1 | Log in, **Account → Change password** | Secure super.admin account |
| 2 | **Administration → Users** | Create accounts for each staff member |
| 3 | **Administration → Users** → assign roles | Each user sees only their modules |
| 4 | **Administration → Audit** | Confirm actions are logged |
| 5 | Run backup script (see [§11](#11-operations-backup--reset)) | Recovery point before go-live |

### 3.2 Organization and people (HR manager — Week 1)

| Step | Portal path | Outcome |
|------|-------------|---------|
| 1 | **HR → Departments** | FO, Finance, F&B, Housekeeping, etc. |
| 2 | **HR → Positions** | Job titles per department |
| 3 | **HR → Employees** | Master employee records |
| 4 | **HR → Settings** | Leave types, overtime rates, asset types |
| 5 | Link portal users to employees where applicable | Single identity for HR + login |

### 3.3 Hotel and rooms (Front office lead — Week 1)

| Step | Portal path | Outcome |
|------|-------------|---------|
| 1 | **Front desk → Hotel settings** | Property configuration |
| 2 | **Front desk → Room status** | Room types and room numbers |
| 3 | **Front desk → Guests** | Optional: corporate / repeat guest profiles |

### 3.4 Restaurant catalog (Restaurant manager — Week 1)

| Step | Portal path | Outcome |
|------|-------------|---------|
| 1 | **Restaurant and F&B → Catalog admin** | Categories, menu items, dining tables |
| 2 | **Restaurant and F&B → Menu** | Verify prices and tax display |

### 3.5 Inventory and suppliers (Inventory manager — Week 1)

| Step | Portal path | Outcome |
|------|-------------|---------|
| 1 | **Inventory → Item categories** / **Items** | Stock catalog and reorder levels |
| 2 | **Inventory → Suppliers** | Vendor master |
| 3 | **Inventory → Stock alerts** | Confirm low-stock and expiry rules |

### 3.6 Finance foundation (Finance manager / Accountant — Week 1)

| Step | Portal path | Outcome |
|------|-------------|---------|
| 1 | **Finance → Chart of accounts** | Review/adjust COA |
| 2 | **Finance → Fiscal periods** | Current period open |
| 3 | **Finance → Budget** | Optional budget lines |
| 4 | **Finance → Dashboards** | Executive view for GM |

**Exit criteria for Phase A:** All staff can log in with correct roles; rooms and menu exist; inventory catalog and suppliers exist; finance period is open.

---

## 4. Phase B — Daily operations

### 4.1 Guest journey (Receptionist / Cashier)

Core front-desk flow:

```
Reservation → Check in (assign room) → Folio (charges) → Settle → Check out → Room available
```

| Step | Where | Action |
|------|-------|--------|
| 1 | **Front desk → Reservations** or quick task **Check in guest** | Create booking or walk-in |
| 2 | Check-in | Assign room; folio opens |
| 3 | **Front desk → Folios** | Post room charges, incidentals, adjustments |
| 4 | **Restaurant and F&B → Orders** | Post meal/drink to in-house folio (if applicable) |
| 5 | **Front desk → Folios** → settle | Payment (cash, card, partial) |
| 6 | Check-out | Close folio; room returns to available |

**Also daily:**

- **Front desk → Room status** — occupancy and housekeeping state  
- **Front desk → Cashier shifts** — open/close shift, cash reconciliation  

### 4.2 Restaurant (Restaurant manager / Cashier)

| Task | Where |
|------|-------|
| Take orders | **Restaurant and F&B → Orders** |
| Charge guest room | Post order to **folio** |
| Walk-in / cash sale | Finalize bill without folio |
| Update menu | **Catalog admin** |

### 4.3 Inventory and procurement (Inventory manager / Department head)

| Task | Where |
|------|-------|
| Monitor stock | **Inventory → Stock alerts** (and notification bell) |
| Request goods | **Inventory → Purchase orders** → create |
| Approve PO | Per approval tier (see [§7](#7-notifications-and-approvals)) |
| Receive goods | Goods receipt on approved PO |
| Pay supplier | **Finance → Payables** → settle |

**Purchase order approval chain:**

```
Submitted → Department head → Finance manager → General manager → Approved → Receive → Payable
```

### 4.4 Staff meals (Restaurant manager + HR)

| Task | Where |
|------|-------|
| Open meal period | **Staff meals** |
| Record employee meals | Meal orders (not guest folios) |
| Close period | Deductions feed into **payroll** (S2) |

### 4.5 Group bookings (Receptionist)

| Task | Where |
|------|-------|
| Create block | **Group bookings** + rooming list |
| Arrival | Bulk check-in |
| Departure | Settle folios; group check-out |

---

## 5. Phase C — Weekly and HR/payroll cycle

### 5.1 Leave

1. Request entered → **HR → Leave requests**  
2. **Department head** approves (notification)  
3. HR maintains records and balances  

### 5.2 Attendance

- **HR → Attendance** — daily/weekly records for payroll accuracy  

### 5.3 Overtime

- **HR → Overtime** — submit → approve → included in payroll run  

### 5.4 Payroll (monthly — Payroll officer)

```
Create run → Calculate → Submit → Approve → Lock → Journal posts to finance
```

| Step | Where |
|------|-------|
| Create run | **Payroll → Payroll runs** |
| Review deductions | Staff meals and other S2/S3 deductions |
| Approve | Manager approval (notification) |
| Severance | **Payroll → Severance** when staff exit |

### 5.5 Offboarding (HR manager)

- **HR → Offboarding** — exit workflow and severance trigger  

---

## 6. Phase D — Monthly finance cycle

| When | Task | Where |
|------|------|-------|
| Ongoing | Review open payables/receivables | **Finance → Payables** / **Receivables** |
| As needed | Manual adjustments | **Finance → Journals** (draft → approve → post; GM if ≥ 50,000 ETB) |
| Weekly | Trial balance / P&L preview | **Finance → Financial reports** |
| Month-end | Close fiscal period | **Finance → Fiscal periods** (close → lock) |
| Month-end | Budget vs actual | **Finance → Budget** |
| Anytime | Executive view | **Finance → Dashboards** (Executive, Operations, Hotel, Restaurant) |

Finance reconciles operational postings from S3 (folios, POs) and S2 (payroll) rather than re-keying transactions.

---

## 7. Notifications and approvals

The **notification bell** (top bar) and **Notifications** inbox show items the user is allowed to act on. They sync from live APIs on login and every ~2 minutes.

| Notification | Who sees it | Trigger |
|--------------|-------------|---------|
| Leave request | HR / approvers with leave read | Pending leave |
| Purchase order | Users with PO read | Status: pending dept / finance / GM |
| Payroll run | Payroll readers | Run `pending_approval` |
| Journal (finance) | Journal approvers | Manual draft journal |
| Journal (GM) | Journal approvers | Large approved journal needs GM sign-off |
| Low stock / expiry | Inventory readers | Alert lists non-empty |
| Fiscal period | Users who can close periods | Period ending within 7 days or status `closing` |
| Change password | Affected user | `must_change_password` on account |

**No configuration required** beyond normal login, permissions, and running portal migrations. Notifications appear only when real pending data exists.

---

## 8. How modules connect

| From | To | What happens |
|------|-----|--------------|
| F&B order | Guest folio | Restaurant charge on room bill |
| Folio settle | S4 | Revenue/cash journal (via S3 integration) |
| PO approved + received | S4 payables | Vendor liability created |
| Payable settle | S4 | Payment journal |
| Staff meals closed | S2 payroll | Deduction on next run |
| Payroll locked | S4 | Payroll journal |
| Manual journal posted | S4 GL | General ledger updated |

---

## 9. Suggested rollout calendar

| Week | Focus | Success check |
|------|-------|----------------|
| **1** | Users, HR master, rooms, menu, inventory catalog, finance COA | All roles can log in |
| **2** | Reservations, check-in/out, folio settlement | One complete guest stay |
| **3** | F&B on folio, cashier shifts | Restaurant charges on room bills |
| **4** | PO: create → approve → receive → pay | Procurement loop complete |
| **5** | Attendance, leave, first payroll run | Payroll approved and locked |
| **6** | Month-end close, dashboards, backup drill | GM dashboard live; backup verified |

---

## 10. Acceptance checklist

Use this for hotel sign-off (manual UAT walkthrough):

- [ ] Super admin: user created, role assigned, audit entry visible  
- [ ] Receptionist: reservation → check-in → room assigned  
- [ ] Cashier: folio charge → settle → check-out → room available  
- [ ] Restaurant: order posted to in-house folio → on guest bill  
- [ ] Inventory: PO created → dept → finance → GM approved  
- [ ] Inventory: goods received → stock increased  
- [ ] Finance: payable settled  
- [ ] Staff meals: period opened → meals recorded → period closed  
- [ ] HR: leave submitted → approved (bell notification)  
- [ ] Payroll: run created → approved → locked  
- [ ] Finance: trial balance and income statement for period  
- [ ] Finance: fiscal period closed and locked  
- [ ] GM: executive dashboard reviewed  
- [ ] Ops: MySQL backup file exists in `backups/`  

**Automated smoke tests (technical team):**

```powershell
.\scripts\portal-admin-smoke.ps1
.\scripts\portal-smoke.ps1
.\scripts\portal-hr-smoke.ps1
.\scripts\run-uat-e2e.ps1
```

---

## 11. Operations (backup & reset)

### Backup (recommended daily)

```powershell
.\scripts\backup-mysql.ps1
```

Writes `backups/wonderland-mysql-<timestamp>.tar.gz` (all four MySQL databases). See [`ops/backup/README.md`](../ops/backup/README.md).

### Fresh start (destructive — one-time only if needed)

Wipes all business data; re-seeds catalog and recreates `super.admin`:

```powershell
docker compose down -v
Remove-Item web-portal\database\database.sqlite -ErrorAction SilentlyContinue
.\scripts\start.ps1
docker compose exec web-portal php artisan migrate --force
```

After go-live, **do not** run `docker compose down -v` unless intentionally wiping the hotel data.

### Restore from backup

```powershell
.\scripts\restore-mysql.ps1 -Archive backups\wonderland-mysql-XXXXXXXX.tar.gz
```

---

## 12. Role cheat sheets

### Receptionist / Cashier

**Daily:** Room status → Reservations / Check-in → Folios (charges, settle) → Check-out → Cashier shifts  
**Quick tasks:** Check in guest, Settle folio, Room status  

### Restaurant manager

**Daily:** Orders, post to folio, menu checks  
**Setup:** Catalog admin (categories, items, tables)  
**Periodic:** Staff meal periods open/close  

### Inventory manager

**Daily:** Stock alerts, POs, goods receipt  
**Setup:** Items, categories, suppliers, reorder levels  

### Department head

**As needed:** Approve leave, approve PO (tier 1)  
**Watch:** Notification bell  

### HR manager

**Ongoing:** Employees, leave, attendance, overtime, offboarding  
**Setup:** Departments, positions, HR settings  

### Payroll officer

**Monthly:** Payroll runs (create → submit → approve → lock), severance  

### Finance manager / Accountant

**Daily:** Payables, receivables, journals  
**Monthly:** Reports, fiscal period close/lock, budget variance  
**Watch:** Fiscal period notifications, journal approvals  

### General manager

**Daily:** Home dashboard, Finance → Dashboards, notification bell  
**Approvals:** Large POs (tier 3), large journals (≥ 50k ETB)  

### Super administrator

**As needed:** Users, roles, audit  
**Not for daily hotel ops:** Use dedicated operational roles instead  

---

## Document history

| Version | Date | Notes |
|---------|------|-------|
| 1.0 | 2026-06-27 | Initial hotel go-live runbook |
