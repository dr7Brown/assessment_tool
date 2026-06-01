================================================================================
  AceICT — Ghana SHS ICT Assessment Platform
  README & Feature Documentation
================================================================================

Last updated : 2026-05-31
Version      : 4.x (Production build)
Developer    : Brownskin
Platform URL : expresslabgh.com/apps/aceict/


────────────────────────────────────────────────────────────────────────────────
  TABLE OF CONTENTS
────────────────────────────────────────────────────────────────────────────────

  1.  Project Overview
  2.  Tech Stack
  3.  System Requirements
  4.  Project Structure
  5.  Installation & Setup
  6.  Database Migrations (run in order)
  7.  Configuration
  8.  User Roles
  9.  Feature List — Core
  10. Feature List — Assessment Engine
  11. Feature List — Analytics & Reporting
  12. Feature List — Communication
  13. Feature List — Google Meet Attendance
  14. Feature List — Offline-First / PWA
  15. API Endpoint Reference
  16. Known Limitations


================================================================================
  1. PROJECT OVERVIEW
================================================================================

AceICT is a full-featured, Ghana-SHS-curriculum-aligned ICT assessment
platform designed for Senior High Schools (SHS). It supports the entire
academic lifecycle: question authoring → test delivery → marking → analytics →
reporting → parent notifications.

The platform is built as a single-page application (SPA) served by a PHP REST
API backed by MariaDB/MySQL. All CSS and JavaScript are bundled inline in
index.html to support offline-first delivery via a Progressive Web App (PWA)
service worker.

Primary design goals:
  • Works offline — students can take tests, teachers can view data without
    network. Data syncs automatically when connectivity returns.
  • Ghana GES aligned — grade boundaries (A1–F9), WASSCE prediction,
    semester periods, and GES district export all follow GES guidelines.
  • Zero dependencies — no npm, no framework, no CDN (except Google Fonts).
    Runs on XAMPP out of the box.


================================================================================
  2. TECH STACK
================================================================================

  Layer          Technology
  ─────────────  ────────────────────────────────────────────────────────
  Frontend       Vanilla HTML5 / CSS3 / ES2022 (no framework)
  Backend        PHP 8.1+ REST API (PDO, JWT HS256)
  Database       MariaDB 10.4+ / MySQL 8.0+
  Web server     Apache 2.4 (via XAMPP) or any PHP host
  Offline        Service Workers (Cache API) + IndexedDB (via AceDB)
  PWA            Web App Manifest, Background Sync, Install Prompt
  AI             Anthropic Claude API (question generation + essay scoring)
  Video          Google Meet (external — platform handles attendance only)


================================================================================
  3. SYSTEM REQUIREMENTS
================================================================================

  Server-side
  ───────────
  • PHP 8.1 or higher (PDO_MySQL extension required)
  • MariaDB 10.4+ or MySQL 8.0+ (InnoDB engine)
  • Apache with mod_rewrite (or any rewrite-capable server)
  • XAMPP 8.x recommended for local development

  Client-side (student / teacher / admin devices)
  ───────────────────────────────────────────────
  • Any modern browser: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+
  • Service Workers require HTTPS in production (localhost works without)
  • Minimum screen: 360px wide (mobile-first responsive design)
  • Offline mode: ~5–50 MB of browser storage used by IndexedDB + Cache API


================================================================================
  4. PROJECT STRUCTURE
================================================================================

  aceict assessments/
  ├── index.html              Main SPA (all CSS + JS inline, ~15 000 lines)
  ├── service-worker.js       PWA service worker (cache-first + network-first)
  ├── manifest.json           Web App Manifest (PWA installability)
  ├── aceict-db.js            IndexedDB wrapper (offline database)  [inlined]
  ├── readme.txt              This file
  │
  ├── api/
  │   └── index.php           REST API router + all handlers (~5 000 lines)
  │
  ├── config/
  │   └── database.php        DB credentials, JWT secret, DB class definition
  │
  └── sql/
      ├── migration_v1.sql    Initial schema (users, schools, tests, questions)
      ├── migration_v2.sql    Attempts, answers, spaced repetition
      ├── migration_v3.sql    Question options, test assignments
      ├── migration_v4.sql    Messages, notifications
      ├── migration_v5.sql    Chat groups + messages
      ├── migration_v6.sql    Departments, teacher_subjects
      ├── migration_v7.sql    Class subjects, student class groups
      ├── migration_v8.sql    Academic periods (year/semester management)
      ├── migration_v9.sql    Chat enhancements, academic period dates
      ├── migration_v10.sql   Question type rename: blank → fill-in
      ├── migration_v11.sql   grade_config table
      ├── migration_v12.sql   Meetings + meeting_attendance tables
      └── migration_v13.sql   updated_at columns + performance indexes


================================================================================
  5. INSTALLATION & SETUP
================================================================================

  Local (XAMPP)
  ─────────────
  1. Copy the project folder into C:\xampp\htdocs\
     Folder name may contain spaces (e.g. "aceict assessments").

  2. Start Apache and MySQL in XAMPP Control Panel.

  3. Open phpMyAdmin → create a database (e.g. royayfxh_aceict).

  4. Run all SQL migrations in order (sql/migration_v1.sql … v13.sql)
     via phpMyAdmin > SQL tab, or import each file.

  5. Edit config/database.php and set:
       DB_HOST, DB_NAME, DB_USER, DB_PASS, JWT_SECRET, ANTHROPIC_API_KEY

  6. Open http://localhost/aceict%20assessments/ in your browser.

  7. Register the first school admin account via the "Admin login" card
     on the landing page, then set up departments, classes, subjects.

  Production (cPanel / shared host)
  ──────────────────────────────────
  1. Upload all files via FTP or File Manager.
  2. Set API_BASE in index.html line ~847 to match your hosting path:
       const API_BASE = '/apps/aceict/api/v1';
  3. Ensure the host supports PHP 8.1+ and has PDO_MySQL.
  4. Add your .env file (or edit config/database.php) with live credentials.
  5. Enable HTTPS — required for Service Workers and PWA install in production.


================================================================================
  6. DATABASE MIGRATIONS (run in order)
================================================================================

  Run each file once against your database. All ALTER TABLE statements use
  IF NOT EXISTS where supported, making re-runs safe on MariaDB 10.x.

  File                  What it does
  ──────────────────    ────────────────────────────────────────────────────────
  migration_v1.sql      Core tables: schools, users, tests, questions
  migration_v2.sql      attempts, answers, streaks, spaced_repetition
  migration_v3.sql      question_options, test_questions, test_assignments
  migration_v4.sql      messages, notifications
  migration_v5.sql      chat_groups, chat_group_members, chat_messages
  migration_v6.sql      departments, teacher_subjects
  migration_v7.sql      class_subjects, student_class_groups
  migration_v8.sql      academic_periods, tests.academic_year, tests.semester
  migration_v9.sql      academic_periods.start_date/end_date, chat enhancements,
                        message_reactions, reply_to_id, is_pinned
  migration_v10.sql     questions.type VARCHAR (was ENUM), blank→fill-in fix
  migration_v11.sql     grade_config (GES A1–F9 grade boundaries per school)
  migration_v12.sql     meetings, meeting_attendance (Google Meet tracking)
  migration_v13.sql     updated_at columns for sync, composite performance indexes

  IMPORTANT: migration_v13.sql uses CREATE INDEX IF NOT EXISTS which requires
  MariaDB 10.1.4+ or MySQL 8.0.12+. If your server is older, remove the
  IF NOT EXISTS keywords from the CREATE INDEX statements.


================================================================================
  7. CONFIGURATION
================================================================================

  config/database.php
  ───────────────────
  DB_HOST          Database host (default: localhost)
  DB_PORT          Database port (default: 3306)
  DB_NAME          Database name
  DB_USER          Database username
  DB_PASS          Database password
  JWT_SECRET       Secret key for signing JWT tokens (min 32 chars)
  JWT_EXPIRY       Token expiry in seconds (default: 604800 = 7 days)
  ANTHROPIC_API_KEY  API key for AI question generation (optional)

  index.html (line ~847)
  ──────────────────────
  API_BASE         Base URL for API calls (must match server path)
                   Example: '/apps/aceict/api/v1'
  USE_MOCK         Set true to silently ignore network errors (dev mode)

  service-worker.js
  ─────────────────
  SHELL_CACHE      Cache name for app shell (bump version to force update)
  API_CACHE        Cache name for API responses
  FONT_CACHE       Cache name for Google Fonts


================================================================================
  8. USER ROLES
================================================================================

  Role       Description                    Access level
  ─────────  ────────────────────────────   ──────────────────────────────────
  admin      School headmaster / admin       Full school management + all data
  teacher    Subject teacher                 Own tests, marking, class analytics
  student    Enrolled student                Assigned tests, own results only

  Role assignment is set at registration and stored in users.role.
  Each user belongs to exactly one school (school_id in JWT token).
  A user cannot view data from another school.


================================================================================
  9. FEATURE LIST — CORE
================================================================================

  SCHOOL MANAGEMENT
  • Multi-school architecture — each school is fully isolated
  • School profile: name, address, GES ID, email, phone, region
  • Department management (create, edit, delete departments)
  • Academic year & semester periods (per year group, one active at a time)
  • GES A1–F9 grade boundaries (admin-customizable per school)

  USER MANAGEMENT
  • Student registration + bulk CSV import
  • Teacher registration + bulk CSV import
  • Admin can activate / deactivate any account
  • Force password change on first login
  • Avatar colour assignment per user
  • Class auto-sync from users.class_name

  CLASS MANAGEMENT
  • Create/edit/delete classes (year group, name, teacher)
  • Assign multiple subjects per class
  • Sub-group tagging (for split-class subjects)
  • Class performance averages displayed in class list
  • Student roster per class

  SUBJECT MANAGEMENT
  • Platform subjects (GES ICT syllabus strands + sub-strands)
  • Custom school subjects
  • Teacher–subject assignment (teachers teach only their subjects)
  • Enable/disable platform subjects per school

  STUDENT PROMOTION
  • Bulk promote entire year group to next year
  • Carries over academic history, resets class assignments


================================================================================
  10. FEATURE LIST — ASSESSMENT ENGINE
================================================================================

  QUESTION BANK
  • 6 question types:
      – MCQ          (single correct option, A–D)
      – Multi-select (multiple correct options)
      – Short answer (teacher-marked, sample answers stored)
      – Essay        (teacher-marked, rubric stored)
      – Fill-in      (auto-marked, accepted answers list)
      – True / False (auto-marked)
  • Sub-strand, topic, Bloom's level, difficulty, year group, marks tagging
  • Per-subject filtering (teacher sees own subjects only)
  • Question explanation / worked solution field
  • Edit, delete, clone questions
  • Search by keyword, sub-strand, difficulty, type, author
  • Pagination (50 per page, load more)

  AI QUESTION GENERATION
  • Generate questions using Anthropic Claude API
  • Input: topic, difficulty, count, question type, sub-strand
  • Review generated questions before saving to bank
  • Discard individual questions before import
  • Supports all 6 question types

  BULK QUESTION UPLOAD
  • CSV upload or Excel paste
  • Type-specific templates (separate columns per type):
      MCQ:      question_text, A, B, C, D, correct, sub_strand, difficulty
      T/F:      question_text, correct (True/False), sub_strand, difficulty
      Short:    question_text, accepted_answers (semicolon-separated)
      Fill-in:  question_text, correct_answer (semicolon-separated)
      Essay:    question_text, marking_guide, sub_strand, difficulty
  • Preview parsed questions before import
  • Remove individual questions from preview
  • Up to 200 questions per upload
  • Download type-specific template CSV

  TEST CREATION
  • Create from scratch or duplicate existing test
  • Test types: Formative, Summative, Practice, Mock Exam
  • Time limit (optional)
  • Max attempts per student (0 = unlimited)
  • Due date (optional)
  • Show feedback toggle (show correct answers after submission)
  • Assign to one or more classes
  • Subject tagging
  • Academic year + semester assignment
  • Auto-detect semester based on current date
  • Add questions from bank by sub-strand, type, difficulty
  • Randomise question order
  • Set marks per question
  • Draft / Published / Archived status

  STUDENT TEST-TAKING
  • Clean quiz interface with question navigation grid
  • Flag questions for review
  • Progress bar + question counter
  • Countdown timer (auto-submit on expiry)
  • Auto-save answers as student progresses
  • Resume in-progress attempt (survived page refresh)
  • Offline guard: must be online to START a test (new attempt needs server ID)
  • Answer submission queued if network drops mid-test

  AUTO-MARKING
  • MCQ, Multi-select, True/False: instant auto-marked
  • Fill-in: case-insensitive match against accepted answers list
  • Score calculated as (correct / total marks) × 100

  MANUAL MARKING
  • Marking queue for teacher: all unmarked essays per class/subject
  • Mark per question with optional written feedback
  • Navigate between students with prev/next buttons
  • AI-assisted essay scoring (suggested mark + justification)
  • Bulk AI pre-score entire queue with one click

  RESULTS & FEEDBACK
  • Animated score ring on completion
  • Pass/Fail badge
  • Answer review (if enabled by teacher)
  • Per-question correct/incorrect breakdown
  • Essay: "pending" state until teacher marks
  • Results history page (all past attempts)
  • Attempt review (re-read answers after marking)

  PRACTICE MODE
  • Untimed, instant feedback, no grade recorded
  • Filter by sub-strand or take from all
  • Review wrong answers immediately
  • Topics-to-review list after completion

  LIVE QUIZ (real-time)
  • Teacher launches quiz session with a room code
  • Students join via code (mobile-friendly)
  • Teacher controls question progression
  • Live leaderboard updates in real-time
  • Teacher can end early at any point
  • Final leaderboard at end of session

  SPACED REPETITION
  • Questions due for review tracked per student
  • Dashboard badge shows SR due count
  • Based on Leitner-style scheduling


================================================================================
  11. FEATURE LIST — ANALYTICS & REPORTING
================================================================================

  STUDENT ANALYTICS
  • Dashboard: avg score, tests done, streak, WASSCE readiness
  • Performance page: sub-strand bars, subject bars, Bloom's breakdown
  • WASSCE readiness score (weighted sub-strand performance)
  • Predicted WASSCE grade (A1–F9) with explanation
  • Test history chart (last 10 attempts trend)
  • Class rank (percentile in class)
  • Semester comparison (this semester vs last)
  • Question type accuracy (MCQ vs Essay vs Fill-in etc.)
  • Achievement badges (10 earnable badges)
  • Printable personal performance report (📄 My Report button)

  TEACHER ANALYTICS
  • School analytics: avg score, pass rate, at-risk students
  • By sub-strand performance across all students
  • Marking queue overview
  • Test-level analytics: per-question accuracy + difficulty (📊 button)
  • Class-level performance trends
  • Action alerts on dashboard (essays pending, meetings today)

  ADMIN ANALYTICS
  • School overview: tests, attempts, avg score, pass rate
  • Teacher analytics tab: per-teacher performance table
  • Teacher Accountability Index (0–100 composite score):
      avg score 40% + pass rate 25% + test activity 20% + question bank 15%
  • By-subject performance bars
  • WASSCE Readiness tab: school-wide per-student grade prediction grid
  • Semester trend analysis
  • Action alerts on dashboard

  AT-RISK STUDENT DETECTION
  • Below-threshold students (configurable %, default 45)
  • Declining students (recent 60-day avg vs all-time, >5pt drop)
  • Students with zero attempts (no engagement)
  • Filter by class, set custom threshold
  • "Study plan" button per at-risk student → opens Remediation Modal

  REMEDIATION PLANNER
  • Per sub-strand accuracy heatmap (Critical / Needs work / Strong)
  • 10 most-failed individual questions
  • Recommended "Practise →" links per weak sub-strand
  • Auto-injected on student result screen when score < 75%

  REPORTS
  • Class performance report (filters: period, subject, teacher, class)
  • At-risk table with export
  • Semester result sheet (class-level, printable, GES grades)
  • Term Report Cards (individual A4 per student):
      – Subject scores + GES grades
      – Class rank (N / total)
      – Teacher remarks (editable before print)
      – Head teacher remarks (editable before print)
      – Signature blocks (class teacher, head teacher, parent)
      – Print all button (CSS @page, one card per page)
  • GES District Report CSV (school overview + class + teacher + subject data)
  • WASSCE Readiness CSV export
  • Meeting attendance CSV export

  GRADE BOUNDARIES
  • GES default: A1 (80–100), B2 (70–79), B3 (60–69), C4 (55–59),
                 C5 (50–54), C6 (45–49), D7 (40–44), E8 (35–39), F9 (0–34)
  • Fully customizable per school from admin → Settings → Grade boundaries
  • Integer display (no .00 decimals)
  • Used in: semester results, term reports, remediation, WASSCE prediction


================================================================================
  12. FEATURE LIST — COMMUNICATION
================================================================================

  MESSAGING (Direct)
  • Private messages between users (any role)
  • Unread count badge on sidebar
  • Thread view per conversation

  GROUP CHAT
  • WhatsApp-style group chat per class (auto-created)
  • Admin can create custom groups
  • Reply to specific messages (threaded)
  • Emoji reactions on messages
  • Date separators between days
  • Admin can delete any message
  • Admin can mute members (can_send = 0)
  • Admin can designate group admins
  • Deactivate / reactivate groups

  NOTIFICATIONS
  • In-app notification bell (teacher/student)
  • Unread count badge
  • Notification types: new test assigned, test marked, meeting reminder
  • Mark individual or all as read
  • Persistent (stored in DB)


================================================================================
  13. FEATURE LIST — GOOGLE MEET ATTENDANCE
================================================================================

  MEETING MANAGEMENT (Teacher / Admin)
  • Schedule meetings with: title, class(es), subject, Google Meet link,
    date, start time, end time, description
  • Select multiple classes at once when creating (one meeting per class)
  • Edit: change title, link, date/time, class, subject
  • Cancel (soft-delete, students no longer see it)
  • Auto-transition to "ended" when end time passes
  • Manual status: Scheduled → Live → Ended / Cancelled

  STUDENT EXPERIENCE
  • Upcoming live classes visible in sidebar + dashboard card
  • "Join Meeting" button appears 30 minutes before start time
  • On join: attendance recorded (IP address, device info, timestamp)
  • Redirect to Google Meet link (new tab, secure)
  • Heartbeat every 5 minutes updates active duration
  • Attendance history tab (all past meetings with status)

  ATTENDANCE TRACKING
  • Statuses: Absent (0 min) / Partial (1–9 min) / Late (10–19 min) / Present (20+ min)
  • Heartbeat pings prevent fake attendance
  • IP address + User-Agent stored per join
  • Last-seen timestamp updated every heartbeat

  ANALYTICS (Teacher / Admin)
  • Per-meeting attendance table (all class students, with status)
  • Summary: Present / Late / Partial / Absent counts
  • Attendance rate % per meeting
  • Export to CSV
  • Filter meetings by class, month
  • Sorted: upcoming ASC (closest first), past DESC (most recent first)
  • Pagination: 20 per tab, "Load more" button
  • Join button for teacher/admin to open own meetings

  SUBJECT FILTERING
  • Meetings list respects subject filter from context bar (TCTX/SCTX)
  • Both teacher and student meeting views filter by selected subject


================================================================================
  14. FEATURE LIST — OFFLINE-FIRST / PWA
================================================================================

  SERVICE WORKER (Phase 1)
  • Registers on first load, activates immediately (skipWaiting)
  • Cache strategies:
      App shell (HTML):  Cache-First (loads in ~0ms offline)
      API calls:         Network-First (fresh data, cache fallback)
      Google Fonts:      Cache-First (load once, keep forever)
  • Offline fallback: serves index.html from cache if all else fails
  • New version banner: "🔄 New version available [Reload]" (non-disruptive)
  • Clears stale API cache on reconnect to force fresh data

  INDEXEDDB LOCAL DATABASE (Phase 2)
  • AceDB wrapper (inlined in index.html — no external file dependency)
  • Three stores:
      api_cache   — GET responses keyed by URL+querystring
      sync_queue  — queued POST/PATCH/DELETE mutations
      meta        — last sync timestamp, last warmup, flags
  • Every successful GET automatically cached
  • Offline GET returns last-cached data silently

  OFFLINE MUTATION QUEUE (Phase 3)
  • Failed writes (network down) saved to sync_queue automatically
  • Excluded from queue: /auth/, /live quiz, /meetings heartbeat
  • Starting a new test requires online (needs server-assigned attempt ID)
  • "Saving offline — will upload when you reconnect" response returned
  • Sync badge (amber, bottom-right): shows count of pending items
  • Clicking badge shows list of pending mutations
  • Queue drains automatically on reconnect (in order)
  • 409 Conflict (duplicate submit) treated as success, removed from queue

  INCREMENTAL SYNC (Phase 5)
  • GET /sync?since=UNIX_TIMESTAMP — returns only changed records
  • Role-aware: students get their tests/attempts/meetings;
                teachers get their tests/questions/meetings;
                admins get school-wide tests/meetings/grade_config
  • Stores lastSync timestamp in AceDB meta
  • Runs 3 seconds after login + on every reconnect
  • Invalidates stale API cache entries after sync

  POST-LOGIN CACHE WARMUP (Phase 4)
  • 2.5 seconds after login, pre-fetches 4–5 critical endpoints in background
  • Student warmup: dashboard, tests, subjects, meetings, history
  • Teacher warmup: tests, questions, classes, subjects, meetings
  • Admin warmup: classes, subjects, tests, meetings
  • Gentle 300ms pacing between requests (no server hammering)
  • Stores lastWarmup timestamp

  LOGOUT SECURITY
  • Full cache wipe on logout (api_cache + sync_queue + meta reset)
  • New user sees clean state — no data leakage between sessions

  PWA INSTALL PROMPT (Phase 6)
  • Install bar appears bottom-left after 30 seconds of use
  • Uses native beforeinstallprompt event
  • "Install" / "Not now" buttons
  • Hides permanently after install or dismissal
  • Works on Android Chrome (home screen install, fullscreen app)

  OFFLINE INDICATORS
  • Red bar at top: "⚡ Offline — the app keeps working"
  • Green toast: "✓ Back online — syncing…" (3.5 seconds)
  • Amber badge: "⬆ N pending" when sync queue has items


================================================================================
  15. API ENDPOINT REFERENCE
================================================================================

  Base URL:  /api/v1/
  Auth:      Bearer JWT token in Authorization header
  Format:    JSON request + response
  Response:  { "success": true/false, "data": ..., "error": "..." }

  AUTH
  ────
  POST   /auth/login                  Email + password login
  POST   /auth/register               Register new user
  GET    /auth/me                     Current user profile
  POST   /auth/change-password        Change password (current required)
  POST   /auth/force-change-password  First-login forced password change

  TESTS
  ─────
  GET    /tests                       List tests (role-filtered)
  POST   /tests                       Create test
  GET    /tests/{id}                  Get single test with questions
  PATCH  /tests/{id}                  Update test metadata
  DELETE /tests/{id}                  Delete test
  GET    /tests/{id}/marking          Students + marking status
  GET    /tests/{id}/analytics        Per-question accuracy analytics

  QUESTIONS
  ─────────
  GET    /questions                   Question bank (paginated, filtered)
  POST   /questions                   Create question
  GET    /questions/{id}              Get single question
  PATCH  /questions/{id}              Update question
  DELETE /questions/{id}              Delete question
  POST   /questions-bulk              Bulk import (up to 200 questions)
  POST   /ai-generate                 AI question generation

  ATTEMPTS & ANSWERS
  ──────────────────
  POST   /attempts                    Start new attempt (online required)
  GET    /attempts/{id}               Get attempt detail
  PATCH  /attempts/{id}               Update attempt (mark essay etc.)
  POST   /attempts/{id}/submit        Submit completed attempt
  POST   /answers                     Save student answers
  GET    /answers/{attemptId}         Get student answers for an attempt

  STUDENTS
  ────────
  GET    /students                    List students in school
  GET    /students/dashboard          Student dashboard data
  GET    /students/subjects           Subjects for current student
  GET    /students/performance        Performance analytics

  CLASSES
  ───────
  GET    /classes                     List classes
  POST   /classes                     Create class
  PATCH  /classes/{id}                Update class
  DELETE /classes/{id}                Delete class
  GET    /classes/{id}/students       Students in class
  POST   /classes/{id}/subjects       Assign subject to class

  SUBJECTS
  ────────
  GET    /subjects                    All subjects (platform + custom)
  POST   /subjects                    Create custom subject
  PATCH  /subjects/{id}               Update subject

  TEACHERS
  ────────
  GET    /teachers                    List teachers
  POST   /teachers/{id}/subjects      Assign subjects to teacher

  ANALYTICS
  ─────────
  GET    /analytics/school            School-wide performance data
  GET    /analytics/teachers          Per-teacher performance
  GET    /analytics/question-stats    Question bank statistics
  GET    /analytics/wassce            WASSCE readiness per student
  GET    /analytics/badges            Student achievement badges
  GET    /analytics/alerts            Pending action alerts (essays, meetings)
  GET    /analytics/marking-queue     Unmarked essays count

  MESSAGES & CHAT
  ───────────────
  GET    /messages                    List conversations
  POST   /messages                    Send message
  GET    /chat                        Chat groups
  POST   /chat                        Create group
  GET    /chat/{id}/messages          Messages in group
  POST   /chat/{id}/messages          Send message to group

  NOTIFICATIONS
  ─────────────
  GET    /notifications               List notifications
  PATCH  /notifications/{id}          Mark as read

  MEETINGS
  ────────
  GET    /meetings                    List meetings (role-filtered, subject-filtered)
  POST   /meetings                    Create meeting(s) — accepts class_ids array
  GET    /meetings/{id}               Get single meeting (link included for teacher/admin)
  PATCH  /meetings/{id}               Edit meeting
  DELETE /meetings/{id}               Cancel meeting (soft)
  GET    /meetings/{id}/attendance    Attendance roster for a meeting
  POST   /meetings/{id}/join          Student joins (records attendance, returns meet link)
  POST   /meetings/{id}/heartbeat     Update last_seen + recalculate duration
  GET    /meetings/analytics          Per-meeting attendance analytics
  GET    /meetings/history            Student's own attendance history

  ACADEMIC PERIODS
  ────────────────
  GET    /academic-periods            List periods for school
  POST   /academic-periods            Create/activate period
  PATCH  /academic-periods/{id}       Edit or activate/deactivate
  DELETE /academic-periods/{id}       Delete (blocked if active)

  REPORTS & RESULTS
  ─────────────────
  GET    /semester-results            Class result sheet with GES grades
  GET    /term-report                 Individual report card data
  GET    /at-risk                     At-risk student analysis
  GET    /remediation                 Study recommendations per student
  GET    /sync                        Incremental sync (since=UNIX_TIMESTAMP)

  SETTINGS
  ────────
  GET    /school                      School info
  PATCH  /school                      Update school info
  GET    /grade-config                Grade boundaries
  POST   /grade-config                Save grade boundaries

  LIVE QUIZ
  ─────────
  POST   /live                        Create session
  GET    /live/{code}                 Session state
  POST   /live/{code}/join            Student join
  PATCH  /live/{code}/start           Start quiz
  PATCH  /live/{code}/next            Next question
  PATCH  /live/{code}/end             End session
  POST   /live/{code}/answer          Submit answer
  GET    /live/{code}/results         Final leaderboard

  OTHER
  ─────
  GET    /departments                 List departments
  POST   /departments                 Create department
  GET    /health                      API health check
  POST   /promotion                   Promote year group


================================================================================
  16. KNOWN LIMITATIONS
================================================================================

  1. OFFLINE TEST START
     A student cannot start a brand-new test while offline.
     The server must create the attempt record (generates the attempt ID).
     Once started, the quiz can continue offline (answers are queued).

  2. LIVE QUIZ REQUIRES NETWORK
     Real-time features (live quiz, Google Meet) are inherently online-only.
     These are excluded from the offline mutation queue.

  3. AI FEATURES REQUIRE ANTHROPIC API KEY
     Question generation and AI essay scoring are disabled if
     ANTHROPIC_API_KEY is not configured in config/database.php.

  4. MARIADB VERSION
     migration_v13.sql requires MariaDB 10.1.4+ for IF NOT EXISTS on indexes
     and ADD COLUMN IF NOT EXISTS. Older MySQL 5.7 users must remove the
     IF NOT EXISTS clauses manually.

  5. PWA HTTPS REQUIREMENT
     Service Workers and the PWA install prompt require HTTPS in production.
     Localhost works without HTTPS for local development only.

  6. GOOGLE MEET LINKS
     The platform does not create Google Meet links — teachers paste them in.
     The platform only handles attendance tracking after the student clicks Join.

  7. SMS / EMAIL NOTIFICATIONS
     The parent portal and SMS alert features described in the feature roadmap
     are not yet implemented. In-app notifications are fully functional.

  8. OFFLINE SYNC CONFLICTS
     Conflict resolution uses last-write-wins for most records. Duplicate
     test submissions (409 Conflict) are silently discarded from the queue.
     No merge UI exists for manual conflict resolution.


================================================================================
  END OF README
================================================================================
