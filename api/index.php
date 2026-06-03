<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

// ── CORS ──────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── PARSE REQUEST ─────────────────────────────────────────────
$method  = $_SERVER['REQUEST_METHOD'];
$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$body    = json_decode(file_get_contents('php://input'), true) ?? [];

// Extract the part after /api/v1
// Works for any subfolder: /apps/aceict/api/v1/auth/login  →  auth/login
$marker = '/api/v1';
$pos    = strpos($rawPath, $marker);
$path   = ($pos !== false) ? substr($rawPath, $pos + strlen($marker)) : '/';
$path   = '/' . trim($path, '/');
$parts  = array_values(array_filter(explode('/', trim($path, '/'))));

$resource = $parts[0] ?? '';
$id       = isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;
$sub      = $parts[2] ?? null;

// ── HELPERS ───────────────────────────────────────────────────
function respond(mixed $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode(['success' => $code < 400, 'data' => $data]);
    exit;
}
function err(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}
function need(array $body, string ...$fields): array {
    $out = [];
    foreach ($fields as $f) {
        if (!isset($body[$f]) || $body[$f] === '') err("Missing field: $f");
        $out[$f] = $body[$f];
    }
    return $out;
}

// ── JWT ───────────────────────────────────────────────────────
function b64e(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function b64d(string $s): string { return base64_decode(strtr($s, '-_', '+/')); }
function jwt_encode(array $payload): string {
    $h = b64e(json_encode(['alg'=>'HS256','typ'=>'JWT']));
    $p = b64e(json_encode($payload));
    $s = b64e(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
    return "$h.$p.$s";
}
function jwt_decode(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$h, $p, $s] = $parts;
    if (!hash_equals(b64e(hash_hmac('sha256', "$h.$p", JWT_SECRET, true)), $s)) return null;
    $data = json_decode(b64d($p), true);
    if (($data['exp'] ?? 0) < time()) return null;
    return $data;
}
function require_auth(): array {
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!str_starts_with($h, 'Bearer ')) err('Unauthorised', 401);
    $payload = jwt_decode(substr($h, 7));
    if (!$payload) err('Token invalid or expired', 401);
    return $payload;
}
function require_role(array $auth, string ...$roles): void {
    if (!in_array($auth['role'], $roles, true)) err('Forbidden', 403);
}

// ── ROUTER ────────────────────────────────────────────────────
try {
    switch ($resource) {
        case 'health':
            respond(['status' => 'ok', 'version' => 'v1', 'time' => date('c'),
                     'path_received' => $path, 'parts' => $parts]);
        case 'auth':      handleAuth($method, $parts, $body); break;
        case 'questions': handleQuestions($method, $id, $sub, $body); break;
        case 'tests':     handleTests($method, $id, $sub, $body); break;
        case 'attempts':  handleAttempts($method, $id, $sub, $body); break;
        case 'answers':   handleAnswers($method, $id, $sub, $body); break;
        case 'students':  handleStudents($method, $id, $parts[1] ?? null, $body); break;
        case 'classes':   handleClasses($method, $id, $sub, $body); break;
        case 'analytics': handleAnalytics($method, $parts[1] ?? null, $body); break;
        case 'notifications': handleNotifications($method, $id, $body); break;
        case 'messages':      handleMessages($method, $id, $id ? ($parts[2] ?? null) : ($parts[1] ?? null), $body); break;
        case 'chat':          handleChat($method, $id, $parts[1] ?? null, $parts[2] ?? null, $parts[3] ?? null, $body); break;
        case 'promotion':         handlePromotion($method, $parts[1] ?? null, $body); break;
        case 'academic-periods':  handleAcademicPeriods($method, $id, $body); break;
        case 'school':            handleSchoolSettings($method, $body); break;
        case 'grade-config':      handleGradeConfig($method, $body); break;
        case 'semester-results':  handleSemesterResults($method, $parts[1]??null, $body); break;
        case 'term-report':       handleTermReport($method); break;
        case 'at-risk':           handleAtRisk($method); break;
        case 'remediation':       handleRemediation($method); break;
        case 'meetings':          handleMeetings($method, $parts, $body); break;
        case 'ai-generate':  handleAIGenerate($method, $body); break;
        case 'questions-bulk': handleBulkQuestions($method, $body); break;
        case 'live':        handleLive($method, $parts, $body); break;
        case 'teachers':    handleTeachers($method, $id, $sub, $body); break;
        case 'users-bulk':  handleUsersBulk($method, $body); break;
        case 'departments': handleDepartments($method, $id, $parts, $body); break;
        case 'subjects':     handleSubjects($method, $id, $parts[2] ?? null, $body); break;
        case 'curriculum':    handleCurriculum($method, $body); break;
        case 'strands':       handleStrandsResource($method, $id, $parts[2] ?? null, $body); break;
        case 'sub-strands':   handleSubStrandsResource($method, $id, $parts[2] ?? null, $body); break;
        case 'topics':        handleTopicsResource($method, $id, $body); break;
        case 'activity-log':  handleActivityLog($method, $body); break;
        case 'sync':          handleSync($method); break;
        default:
            err("Endpoint not found: '$resource' (path: $path)", 404);
    }
} catch (PDOException $e) {
    err('Database error: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    err('Server error: ' . $e->getMessage(), 500);
}

// ══════════════════════════════════════════════════════════════
// AUTH
// ══════════════════════════════════════════════════════════════
function handleAuth(string $method, array $parts, array $body): void {
    // Ensure must_change_password column exists (safe on MySQL 8+ / MariaDB 10+)
    try { DB::execute("ALTER TABLE users ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) NOT NULL DEFAULT 0"); } catch(Throwable $e) {}

    $action = $parts[1] ?? '';

    // POST /auth/login
    if ($method === 'POST' && $action === 'login') {
        ['email' => $email, 'password' => $password] = need($body, 'email', 'password');
        $user = DB::fetchOne(
            'SELECT id, school_id, role, first_name, last_name, email, password_hash,
                    avatar_color, is_active, must_change_password
             FROM users WHERE email = ?',
            [strtolower(trim($email))]
        );
        if (!$user || !password_verify($password, $user['password_hash']))
            err('Invalid email or password', 401);
        if (!$user['is_active'])
            err('Account deactivated. Contact your administrator.', 403);
        $token = jwt_encode([
            'user_id'   => $user['id'],
            'school_id' => $user['school_id'],
            'role'      => $user['role'],
            'email'     => $user['email'],
            'exp'       => time() + JWT_EXPIRY,
        ]);
        DB::execute('UPDATE users SET last_login = NOW() WHERE id = ?', [$user['id']]);
        logActivity(['user_id'=>$user['id'],'school_id'=>$user['school_id'],'role'=>$user['role']], 'auth.login', 'user', (int)$user['id'], trim($user['first_name'].' '.$user['last_name']), 'Logged in');
        unset($user['password_hash']);
        $user['must_change_password'] = (int)$user['must_change_password'];
        respond(['token' => $token, 'user' => $user]);
    }

    // POST /auth/register
    if ($method === 'POST' && $action === 'register') {
        ['first_name'=>$fn,'last_name'=>$ln,'email'=>$em,'password'=>$pw,'role'=>$role,'school_id'=>$sid]
            = need($body, 'first_name', 'last_name', 'email', 'password', 'role', 'school_id');
        if (!filter_var($em, FILTER_VALIDATE_EMAIL)) err('Invalid email');
        if (strlen($pw) < 8) err('Password must be at least 8 characters');
        if (DB::fetchOne('SELECT id FROM users WHERE email = ?', [strtolower($em)]))
            err('Email already registered', 409);
        $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
        $uid  = DB::insert(
            'INSERT INTO users (school_id, role, first_name, last_name, email, password_hash, class_name)
             VALUES (?,?,?,?,?,?,?)',
            [(int)$sid, $role, trim($fn), trim($ln), strtolower($em), $hash, $body['class_name'] ?? null]
        );
        if ($role === 'student')
            DB::execute('INSERT INTO streaks (student_id) VALUES (?)', [$uid]);
        respond(['user_id' => $uid, 'message' => 'Account created'], 201);
    }

    // GET /auth/me
    if ($method === 'GET' && $action === 'me') {
        $auth = require_auth();
        $user = DB::fetchOne(
            'SELECT id, school_id, role, first_name, last_name, email, class_name,
                    avatar_color, last_login, must_change_password
             FROM users WHERE id = ?', [$auth['user_id']]
        );
        if (!$user) err('User not found', 404);
        $user['must_change_password'] = (int)$user['must_change_password'];
        respond($user);
    }

    // POST /auth/change-password  (requires current password)
    if ($method === 'POST' && $action === 'change-password') {
        $auth = require_auth();
        ['current_password'=>$cur,'new_password'=>$new] = need($body,'current_password','new_password');
        $user = DB::fetchOne('SELECT password_hash FROM users WHERE id = ?', [$auth['user_id']]);
        if (!password_verify($cur, $user['password_hash'])) err('Current password incorrect', 401);
        if (strlen($new) < 8) err('New password must be at least 8 characters');
        DB::execute('UPDATE users SET password_hash=?, must_change_password=0 WHERE id=?',
            [password_hash($new, PASSWORD_BCRYPT, ['cost'=>12]), $auth['user_id']]);
        respond(['message' => 'Password changed']);
    }

    // POST /auth/force-change-password  (first-login; no current password required)
    if ($method === 'POST' && $action === 'force-change-password') {
        $auth = require_auth();
        $new  = trim($body['new_password'] ?? '');
        if (strlen($new) < 8) err('Password must be at least 8 characters');
        DB::execute('UPDATE users SET password_hash=?, must_change_password=0 WHERE id=?',
            [password_hash($new, PASSWORD_BCRYPT, ['cost'=>12]), $auth['user_id']]);
        respond(['message' => 'Password changed successfully']);
    }

    err("Auth action '$action' not found", 404);
}

// ══════════════════════════════════════════════════════════════
// QUESTIONS
// ══════════════════════════════════════════════════════════════
function handleQuestions(string $method, ?int $id, ?string $sub, array $body): void {
    $auth = require_auth();
    ensureTeacherSubjectSchema(); // teacher_subjects + subject_id/updated_by columns
    ensureQuestionTypeSchema();   // migrate blank→fill-in, widen type column to VARCHAR

    if ($method === 'GET' && !$id) {
        $where  = ['(q.school_id IS NULL OR q.school_id = ?)'];
        $params = [$auth['school_id']];

        // Subject-based scoping for teachers
        if ($auth['role'] === 'teacher') {
            $teacherSubjectIds = array_column(DB::fetchAll(
                'SELECT subject_id FROM teacher_subjects WHERE teacher_id=?',
                [$auth['user_id']]
            ), 'subject_id');
            if (!empty($teacherSubjectIds)) {
                $ph = implode(',', array_fill(0, count($teacherSubjectIds), '?'));
                $where[] = "(q.subject_id IN ($ph) OR q.author_id = ?)";
                foreach ($teacherSubjectIds as $sid) $params[] = $sid;
                $params[] = $auth['user_id'];
            }
            // If teacher has no subjects, they see only their own questions (safe default)
            // (no additional WHERE clause needed as existing school filter still applies)
        }

        if (!empty($_GET['subject_id'])) { $where[] = 'q.subject_id = ?';      $params[] = (int)$_GET['subject_id']; }
        if (!empty($_GET['year_group'])) { $where[] = 'q.year_group = ?';      $params[] = (int)$_GET['year_group']; }
        if (!empty($_GET['sub_strand'])) { $where[] = 'q.sub_strand = ?';      $params[] = $_GET['sub_strand']; }
        if (!empty($_GET['difficulty'])) { $where[] = 'q.difficulty = ?';      $params[] = $_GET['difficulty']; }
        if (!empty($_GET['type']))       { $where[] = 'q.type = ?';            $params[] = $_GET['type']; }
        if (!empty($_GET['search']))     { $where[] = 'q.question_text LIKE ?'; $params[] = '%'.$_GET['search'].'%'; }
        // Admin filter by author
        if (!empty($_GET['author_id']) && $auth['role'] === 'admin') {
            $where[] = 'q.author_id = ?'; $params[] = (int)$_GET['author_id'];
        }
        $limit  = min(100, max(1, (int)($_GET['limit'] ?? 50)));
        $offset = ((int)($_GET['page'] ?? 1) - 1) * $limit;
        $where  = implode(' AND ', $where);
        $total  = DB::fetchOne("SELECT COUNT(*) AS n FROM questions q WHERE $where AND (q.is_active IS NULL OR q.is_active=1)", $params)['n'] ?? 0;
        $items  = DB::fetchAll("SELECT q.id, q.school_id, q.type, q.sub_strand, q.topic,
            q.bloom_level, q.difficulty, q.year_group, q.marks, q.question_text,
            q.explanation, q.rubric, q.author_id, q.subject_id, q.created_at,
            CONCAT(u.first_name,' ',u.last_name) AS author_name,
            (SELECT GROUP_CONCAT(option_label,'|',option_text,'|',is_correct ORDER BY sort_order SEPARATOR ';;')
            FROM question_options WHERE question_id=q.id) AS options_raw
            FROM questions q
            LEFT JOIN users u ON u.id=q.author_id
            WHERE $where AND (q.is_active IS NULL OR q.is_active=1)
            ORDER BY RAND() LIMIT $limit OFFSET $offset", $params);
        respond(['items' => $items, 'total' => (int)$total, 'limit' => $limit]);
    }

    if ($method === 'GET' && $id) {
        $q = DB::fetchOne('SELECT q.*,
            (SELECT JSON_ARRAYAGG(JSON_OBJECT("label",option_label,"text",option_text,"correct",is_correct))
             FROM question_options WHERE question_id=q.id ORDER BY sort_order) AS options
            FROM questions q WHERE q.id=?', [$id]);
        if (!$q) err('Question not found', 404);
        respond($q);
    }

    if ($method === 'POST') {
        require_role($auth, 'teacher', 'admin');
        $d = need($body, 'type', 'question_text');
        if (empty($body['sub_strand']) && empty($body['sub_strand_id'])) err("Missing field: sub_strand or sub_strand_id");
        $subjectId   = isset($body['subject_id'])    ? (int)$body['subject_id']    : null;
        $strandId    = isset($body['strand_id'])     ? (int)$body['strand_id']     : null;
        $subStrandId = isset($body['sub_strand_id']) ? (int)$body['sub_strand_id'] : null;
        $topicId     = isset($body['topic_id'])      ? (int)$body['topic_id']      : null;
        // Legacy text field: use sub_strand label or code for display
        $subStrandText = trim($body['sub_strand'] ?? $body['topic'] ?? '');
        if (!$subStrandText && $subStrandId) {
            $ss = DB::fetchOne('SELECT sub_strand_label FROM subject_sub_strands WHERE id=?', [$subStrandId]);
            $subStrandText = $ss['sub_strand_label'] ?? '';
        }
        DB::beginTransaction();
        try {
            $qid = DB::insert(
                'INSERT INTO questions (school_id,author_id,type,sub_strand,topic,bloom_level,difficulty,year_group,marks,question_text,explanation,rubric,subject_id,strand_id,sub_strand_id,topic_id,is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)',
                [$auth['school_id'],$auth['user_id'],$d['type'],$subStrandText,
                 $subStrandText,$body['bloom_level']??'Remember',$body['difficulty']??'Medium',
                 (int)($body['year_group']??1),(int)($body['marks']??1),
                 $d['question_text'],$body['explanation']??null,$body['rubric']??null,
                 $subjectId,$strandId,$subStrandId,$topicId]
            );
            if (!empty($body['options'])) {
                $letters = ['A','B','C','D','E','F'];
                foreach ($body['options'] as $i => $opt) {
                    DB::execute(
                        'INSERT INTO question_options (question_id,option_label,option_text,is_correct,sort_order) VALUES (?,?,?,?,?)',
                        [$qid, $opt['label']??$letters[$i], $opt['text'], (int)($opt['correct']??0), $i]
                    );
                }
            }
            DB::commit();
            logActivity($auth, 'question.created', 'question', (int)$qid, mb_substr($d['question_text'],0,80), 'Added question to bank');
            respond(['question_id' => $qid], 201);
        } catch (Throwable $e) { DB::rollback(); throw $e; }
    }

    if ($method === 'PUT' && $id) {
        require_role($auth, 'teacher', 'admin');
        $d = need($body, 'question_text');

        $existing = DB::fetchOne('SELECT id, author_id, subject_id FROM questions WHERE id=?', [$id]);
        if (!$existing) err('Question not found', 404);

        if ($auth['role'] !== 'admin' && (int)$existing['author_id'] !== $auth['user_id']) {
            $sid = (int)($existing['subject_id'] ?? 0);
            if (!$sid || !DB::fetchOne(
                'SELECT 1 FROM teacher_subjects WHERE teacher_id=? AND subject_id=?',
                [$auth['user_id'], $sid]
            )) err('You do not have permission to edit this question', 403);
        }

        $newSubjectId   = isset($body['subject_id'])    ? (int)$body['subject_id']    : ($existing['subject_id'] ? (int)$existing['subject_id'] : null);
        $newStrandId    = isset($body['strand_id'])     ? (int)$body['strand_id']     : null;
        $newSubStrandId = isset($body['sub_strand_id']) ? (int)$body['sub_strand_id'] : null;
        $newTopicId     = isset($body['topic_id'])      ? (int)$body['topic_id']      : null;
        $subStrandText  = trim($body['sub_strand'] ?? $body['topic'] ?? '');
        if (!$subStrandText && $newSubStrandId) {
            $ss = DB::fetchOne('SELECT sub_strand_label FROM subject_sub_strands WHERE id=?', [$newSubStrandId]);
            $subStrandText = $ss['sub_strand_label'] ?? '';
        }

        DB::execute(
            'UPDATE questions SET question_text=?, sub_strand=?, topic=?, bloom_level=?,
             difficulty=?, year_group=?, marks=?, explanation=?, rubric=?, type=?,
             subject_id=?, strand_id=?, sub_strand_id=?, topic_id=?, updated_by=?, updated_at=NOW()
             WHERE id=?',
            [
                $d['question_text'], $subStrandText, $subStrandText,
                $body['bloom_level'] ?? 'Remember', $body['difficulty'] ?? 'Medium',
                (int)($body['year_group'] ?? 1), (int)($body['marks'] ?? 1),
                $body['explanation'] ?? null, $body['rubric'] ?? null,
                $body['type'] ?? 'mcq',
                $newSubjectId, $newStrandId ?: null, $newSubStrandId ?: null, $newTopicId ?: null,
                $auth['user_id'], $id,
            ]
        );

        // Rebuild options if provided
        if (!empty($body['options'])) {
            DB::execute('DELETE FROM question_options WHERE question_id=?', [$id]);
            $letters = ['A','B','C','D','E','F'];
            foreach ($body['options'] as $j => $o) {
                DB::execute(
                    'INSERT INTO question_options (question_id, option_label, option_text, is_correct, sort_order)
                     VALUES (?,?,?,?,?)',
                    [$id, $o['label'] ?? $letters[$j], $o['text'], (int)($o['correct'] ?? 0), $j]
                );
            }
        }
        respond(['message' => 'Question updated']);
    }

    if ($method === 'DELETE' && $id) {
        require_role($auth, 'teacher', 'admin');
        // Soft delete — mark inactive
        $existing = DB::fetchOne('SELECT id, question_text FROM questions WHERE id=?', [$id]);
        if (!$existing) err('Question not found', 404);
        DB::execute('UPDATE questions SET is_active=0 WHERE id=?', [$id]);
        logActivity($auth, 'question.deleted', 'question', (int)$id, mb_substr($existing['question_text']??'',0,80));
        respond(['message' => 'Question deleted']);
    }

    err('Questions endpoint error', 404);
}

// ══════════════════════════════════════════════════════════════
// TESTS
// ══════════════════════════════════════════════════════════════
function handleTests(string $method, ?int $id, ?string $sub, array $body): void {
    $auth = require_auth();
    ensureTeacherSubjectSchema(); // teacher_subjects + subject_id/updated_by columns
    ensureClassSubjectSchema();   // class_subjects + student_class_groups
    ensureAcademicPeriodsSchema(); // academic_periods + tests.academic_year

    if ($method === 'GET' && !$id) {
        // Shared period filter params
        $filterAcYear = !empty($_GET['academic_year']) ? trim($_GET['academic_year']) : null;
        $filterSemQ   = isset($_GET['semester']) && in_array((int)$_GET['semester'],[1,2]) ? (int)$_GET['semester'] : null;
        $periodFilter = '';
        $periodParams = [];
        if ($filterAcYear) { $periodFilter .= ' AND t.academic_year=?'; $periodParams[] = $filterAcYear; }
        if ($filterSemQ)   { $periodFilter .= ' AND t.semester=?';      $periodParams[] = $filterSemQ; }

        if ($auth['role'] === 'student') {
            $uid = $auth['user_id'];

            // Auto-sync class_students from users.class_name if not yet present
            syncStudentClass($uid, $auth['school_id']);

            // Which subjects can this student access?
            // Rule: subject is accessible if their class studies it AND
            //       either it has no group split OR the student is in that group.
            $studentSubjectIds = array_column(DB::fetchAll(
                "SELECT DISTINCT csubj.subject_id
                 FROM class_students cst
                 JOIN class_subjects csubj ON csubj.class_id = cst.class_id
                 LEFT JOIN student_class_groups scg
                        ON scg.student_id = cst.student_id AND scg.class_id = cst.class_id
                 WHERE cst.student_id = ?
                   AND (csubj.group_tag IS NULL OR csubj.group_tag = scg.group_tag)",
                [$uid]
            ), 'subject_id');

            // Build subject filter.
            // Rule 1: tests with no subject_id are always visible (legacy / untagged).
            // Rule 2: tests WITH a subject_id are only visible if:
            //   (a) the student's class has that subject configured (class_subjects)
            //   (b) OR the student's class has NO subjects configured at all
            //       (admin hasn't set up class_subjects yet → show all in-class tests).
            $classHasSubjects = !empty($studentSubjectIds);
            $subjFilter = '';
            $subjParams  = [];
            if ($classHasSubjects) {
                // Strict: student must study the subject OR test has no subject
                $ph = implode(',', array_fill(0, count($studentSubjectIds), '?'));
                $subjFilter = "AND (t.subject_id IS NULL OR t.subject_id IN ($ph))";
                $subjParams  = $studentSubjectIds;
            }
            // When $classHasSubjects is false: no subject filter — all tests in
            // assigned class are visible (class_subjects not configured yet).

            $tests = DB::fetchAll(
                "SELECT DISTINCT t.id, t.title, t.type, t.status, t.time_limit_min, t.max_attempts,
                        t.show_feedback, t.available_from, t.due_at, t.subject_id,
                        s.name       AS subject_name,
                        s.short_name AS subject_short,
                        (SELECT COUNT(*) FROM attempts       WHERE test_id=t.id AND student_id=?) AS my_attempts,
                        (SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) AS question_count
                 FROM tests t
                 JOIN test_assignments ta   ON ta.test_id=t.id
                 JOIN class_students   cst2 ON cst2.class_id=ta.class_id AND cst2.student_id=?
                 LEFT JOIN subjects s ON s.id=t.subject_id
                 WHERE t.status='published'
                   AND (t.available_from IS NULL OR t.available_from<=NOW())
                   $subjFilter $periodFilter
                 ORDER BY t.due_at IS NULL, t.due_at ASC",
                array_merge([$uid, $uid], $subjParams, $periodParams)
            );
            respond($tests);
        } else {
            // Teacher: scope by their assigned subjects + own tests
            $uid = $auth['user_id'];
            $teacherSubjectIds = array_column(DB::fetchAll(
                'SELECT subject_id FROM teacher_subjects WHERE teacher_id=?', [$uid]
            ), 'subject_id');
            if (!empty($teacherSubjectIds)) {
                $ph = implode(',', array_fill(0, count($teacherSubjectIds), '?'));
                $rows = DB::fetchAll(
                    "SELECT t.*,
                            (SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) AS question_count,
                            CONCAT(u.first_name,' ',u.last_name) AS creator_name,
                            s.name AS subject_name
                     FROM tests t
                     LEFT JOIN users u ON u.id=t.creator_id
                     LEFT JOIN subjects s ON s.id=t.subject_id
                     WHERE t.school_id=? AND (t.subject_id IN ($ph) OR t.creator_id=?)
                       $periodFilter
                     ORDER BY t.created_at DESC",
                    array_merge([$auth['school_id']], $teacherSubjectIds, [$uid], $periodParams)
                );
            } else {
                // No subjects assigned: fall back to own tests only
                $rows = DB::fetchAll(
                    "SELECT t.*,
                            (SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) AS question_count,
                            CONCAT(u.first_name,' ',u.last_name) AS creator_name
                     FROM tests t
                     LEFT JOIN users u ON u.id=t.creator_id
                     WHERE t.school_id=? AND t.creator_id=?
                       $periodFilter
                     ORDER BY t.created_at DESC",
                    array_merge([$auth['school_id'], $uid], $periodParams)
                );
            }
            // Admin: show all school tests with creator + subject info
            if ($auth['role'] === 'admin') {
                $rows = DB::fetchAll(
                    "SELECT t.*,
                            (SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) AS question_count,
                            CONCAT(u.first_name,' ',u.last_name) AS creator_name,
                            s.name AS subject_name
                     FROM tests t
                     LEFT JOIN users u ON u.id=t.creator_id
                     LEFT JOIN subjects s ON s.id=t.subject_id
                     WHERE t.school_id=?
                       $periodFilter
                     ORDER BY t.created_at DESC",
                    array_merge([$auth['school_id']], $periodParams)
                );
            }
            respond($rows);
        }
    }

    if ($method === 'GET' && $id && !$sub) {
        // Students can access published tests; teachers/admins restricted to own school
        $subjectJoin = 'LEFT JOIN subjects s ON s.id=t.subject_id';
        $subjectCols = ', s.name AS subject_name, s.short_name AS subject_short';
        if ($auth['role'] === 'student') {
            $test = DB::fetchOne(
                "SELECT t.*$subjectCols FROM tests t $subjectJoin WHERE t.id=? AND t.status='published'",
                [$id]
            );
        } else {
            $test = DB::fetchOne(
                "SELECT t.*$subjectCols FROM tests t $subjectJoin WHERE t.id=? AND t.school_id=?",
                [$id, $auth['school_id']]
            );
        }
        if (!$test) err('Test not found — check the test is published and assigned to your class', 404);
        $hideAnswers = ($auth['role'] === 'student');
        $correctCol  = $hideAnswers ? '' : ",'correct',qo.is_correct";
        $test['questions'] = DB::fetchAll(
            "SELECT q.id,q.type,q.sub_strand,q.topic,q.bloom_level,q.difficulty,q.marks,
                    q.question_text,tq.sort_order,tq.section,
                    " . ($hideAnswers ? 'NULL AS explanation,' : 'q.explanation,') . "
                    (SELECT JSON_ARRAYAGG(JSON_OBJECT('id',qo.id,'label',qo.option_label,'text',qo.option_text$correctCol))
                     FROM question_options qo WHERE qo.question_id=q.id ORDER BY qo.sort_order) AS options
             FROM test_questions tq JOIN questions q ON q.id=tq.question_id
             WHERE tq.test_id=? ORDER BY tq.sort_order", [$id]
        );
        // Include assigned class IDs so the edit form can pre-select them
        $test['class_ids'] = array_map('intval', array_column(
            DB::fetchAll('SELECT class_id FROM test_assignments WHERE test_id=? AND class_id IS NOT NULL', [$id]),
            'class_id'
        ));
        respond($test);
    }

    // GET /tests/{id}/analytics — per-question accuracy for teacher/admin
    if ($method === 'GET' && $id && $sub === 'analytics') {
        require_role($auth, 'teacher', 'admin');
        $questions = DB::fetchAll(
            "SELECT q.id, LEFT(q.question_text,120) AS question_text, q.type, q.difficulty,
                    q.sub_strand, q.marks,
                    COUNT(an.id)                                    AS answer_count,
                    SUM(IF(an.is_correct=1,1,0))                    AS correct_count,
                    ROUND(AVG(IF(an.is_correct=1,100,0)),1)         AS accuracy_pct,
                    tq.sort_order
             FROM test_questions tq
             JOIN questions q ON q.id=tq.question_id
             LEFT JOIN answers an ON an.question_id=q.id
                  AND an.attempt_id IN (
                      SELECT id FROM attempts WHERE test_id=? AND status IN ('submitted','marked')
                  )
             WHERE tq.test_id=?
             GROUP BY q.id, q.question_text, q.type, q.difficulty, q.sub_strand, q.marks, tq.sort_order
             ORDER BY accuracy_pct ASC",
            [$id, $id]
        );
        $overview = DB::fetchOne(
            "SELECT ROUND(AVG(IF(max_score>0,(score_auto+score_manual)/max_score*100,0)),1) AS avg_pct,
                    SUM(IF(max_score>0 AND (score_auto+score_manual)/max_score>=0.5,1,0)) AS pass_count,
                    COUNT(*) AS total_attempts, MAX(submitted_at) AS last_submission
             FROM attempts WHERE test_id=? AND status IN ('submitted','marked')",
            [$id]
        );
        respond(['questions' => $questions, 'overview' => $overview]);
    }

    // GET /tests/{id}/marking — students+attempts with essay marking status
    if ($method === 'GET' && $id && $sub === 'marking') {
        require_role($auth, 'teacher', 'admin');
        $attempts = DB::fetchAll(
            "SELECT a.id AS attempt_id, a.student_id,
                    u.first_name, u.last_name, u.class_name, u.avatar_color,
                    a.score_auto + COALESCE(a.score_manual,0) AS total_score,
                    a.max_score, a.status, a.submitted_at,
                    SUM(CASE WHEN q.type IN ('essay','short','fill-in','blank') AND an.is_correct IS NULL THEN 1 ELSE 0 END) AS essays_pending,
                    SUM(CASE WHEN q.type IN ('essay','short','fill-in','blank') THEN 1 ELSE 0 END) AS essays_total,
                    a.time_taken_s,
                    ROUND(IF(a.max_score>0,(a.score_auto+COALESCE(a.score_manual,0))/a.max_score*100,0),1) AS pct_score
             FROM attempts a
             JOIN users u ON u.id=a.student_id
             LEFT JOIN answers an ON an.attempt_id=a.id
             LEFT JOIN questions q ON q.id=an.question_id
             WHERE a.test_id=? AND a.status IN ('submitted','marked')
             GROUP BY a.id, a.student_id, u.first_name, u.last_name,
                      u.class_name, u.avatar_color, a.score_auto, a.score_manual, a.max_score, a.status, a.submitted_at, a.time_taken_s
             ORDER BY essays_pending DESC, u.last_name",
            [$id]
        );
        respond($attempts);
    }

    if ($method === 'GET' && $id && $sub === 'results') {
        require_role($auth, 'teacher', 'admin');

        // ── Test metadata ─────────────────────────────────────
        $test = DB::fetchOne(
            "SELECT t.*, s.name AS subject_name, s.short_name AS subject_short,
                    (SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) AS question_count,
                    CONCAT(u.first_name,' ',u.last_name) AS teacher_name,
                    sc.name AS school_name
             FROM tests t
             LEFT JOIN subjects s  ON s.id  = t.subject_id
             LEFT JOIN users u     ON u.id  = t.creator_id
             LEFT JOIN schools sc  ON sc.id = t.school_id
             WHERE t.id=?", [$id]
        );
        if (!$test) err('Test not found', 404);

        // ── Enrolled students (via test_assignments → class_students) ──
        $enrolled = DB::fetchAll(
            "SELECT DISTINCT u.id AS student_id,
                    CONCAT(u.first_name,' ',u.last_name) AS student_name,
                    u.class_name, u.avatar_color
             FROM test_assignments ta
             JOIN class_students cs ON cs.class_id=ta.class_id
             JOIN users u ON u.id=cs.student_id AND u.is_active=1
             WHERE ta.test_id=?",
            [$id]
        );

        // ── Attempts ──────────────────────────────────────────
        $attempts = DB::fetchAll(
            "SELECT a.id AS attempt_id, a.student_id, a.attempt_num,
                    a.score_auto + COALESCE(a.score_manual,0) AS score,
                    a.max_score,
                    ROUND(IF(a.max_score>0,(a.score_auto+COALESCE(a.score_manual,0))/a.max_score*100,0),1) AS pct_score,
                    a.status, a.submitted_at, a.time_taken_s,
                    CONCAT(u.first_name,' ',u.last_name) AS student_name,
                    u.class_name, u.avatar_color,
                    SUM(CASE WHEN q.type='essay' AND an.is_correct IS NULL THEN 1 ELSE 0 END) AS essays_pending
             FROM attempts a
             JOIN users u ON u.id=a.student_id
             LEFT JOIN answers an ON an.attempt_id=a.id
             LEFT JOIN questions q ON q.id=an.question_id
             WHERE a.test_id=? AND a.status IN ('submitted','marked')
             GROUP BY a.id, a.student_id, a.attempt_num, a.score_auto, a.score_manual,
                      a.max_score, a.status, a.submitted_at, a.time_taken_s,
                      u.first_name, u.last_name, u.class_name, u.avatar_color
             ORDER BY a.submitted_at DESC",
            [$id]
        );

        // ── Absent students (enrolled but no submitted/marked attempt) ──
        $attemptedIds = array_column($attempts, 'student_id');
        $absent = array_values(array_filter($enrolled, fn($s) =>
            !in_array($s['student_id'], array_map('intval', $attemptedIds))
        ));

        // ── Summary stats ─────────────────────────────────────
        $completed    = count($attempts);
        $enrolledCount = count($enrolled);
        $passCount    = count(array_filter($attempts, fn($a) => floatval($a['pct_score']) >= 50));
        $avgPct       = $completed ? round(array_sum(array_column($attempts,'pct_score')) / $completed, 1) : 0;
        $avgTime      = $completed ? round(array_sum(array_column($attempts,'time_taken_s')) / $completed) : 0;
        $highPct      = $completed ? max(array_column($attempts,'pct_score')) : 0;
        $lowPct       = $completed ? min(array_column($attempts,'pct_score')) : 0;
        $atRiskIds    = array_column(array_filter($attempts, fn($a) => floatval($a['pct_score']) < 50), 'student_id');

        // ── Question statistics ───────────────────────────────
        $qStats = DB::fetchAll(
            "SELECT q.id AS question_id, q.question_text, q.type, q.marks, q.sub_strand,
                    COUNT(an.id) AS attempt_count,
                    SUM(CASE WHEN an.is_correct=1 THEN 1 ELSE 0 END) AS correct_count,
                    ROUND(AVG(CASE WHEN an.is_correct IS NOT NULL
                        THEN IF(an.is_correct=1,100,0) ELSE NULL END), 1) AS correct_pct,
                    ROUND(AVG(an.marks_awarded), 2) AS avg_marks
             FROM test_questions tq
             JOIN questions q ON q.id=tq.question_id
             LEFT JOIN answers an ON an.question_id=q.id
                 AND an.attempt_id IN (SELECT id FROM attempts WHERE test_id=? AND status IN ('submitted','marked'))
             WHERE tq.test_id=?
             GROUP BY q.id, q.question_text, q.type, q.marks, q.sub_strand
             ORDER BY tq.sort_order",
            [$id, $id]
        );

        respond([
            'test'      => $test,
            'stats'     => [
                'enrolled'         => $enrolledCount,
                'completed'        => $completed,
                'absent'           => count($absent),
                'completion_rate'  => $enrolledCount ? round($completed/$enrolledCount*100) : 0,
                'avg_pct'          => $avgPct,
                'avg_time_s'       => $avgTime,
                'pass_count'       => $passCount,
                'pass_rate'        => $completed ? round($passCount/$completed*100) : 0,
                'highest_pct'      => $highPct,
                'lowest_pct'       => $lowPct,
                'at_risk_count'    => count($atRiskIds),
            ],
            'attempts'       => $attempts,
            'absent'         => $absent,
            'question_stats' => $qStats,
        ]);
    }

    if ($method === 'POST') {
        require_role($auth, 'teacher', 'admin');
        $d = need($body, 'title');
        $subjectId = isset($body['subject_id']) ? (int)$body['subject_id'] : null;
        $desc      = isset($body['description']) ? trim($body['description']) : null;
        // Determine academic period from the first assigned class (or fallback)
        $semFromBody = isset($body['semester']) && in_array((int)$body['semester'],[1,2]) ? (int)$body['semester'] : null;
        $acYear      = isset($body['academic_year']) ? trim($body['academic_year']) : null;
        $semester    = $semFromBody;

        if (!$acYear || !$semester) {
            $firstCid  = !empty($body['class_ids']) ? (int)$body['class_ids'][0] : null;
            $yearGroup = null;
            if ($firstCid) {
                $clsRow = DB::fetchOne('SELECT year_group FROM classes WHERE id=?', [$firstCid]);
                $yearGroup = $clsRow ? (int)$clsRow['year_group'] : null;
            }
            $period = $yearGroup ? _currentPeriod($auth['school_id'], $yearGroup) : _currentPeriod($auth['school_id'], 1);
            if (!$acYear)    $acYear   = $period['academic_year'];
            if (!$semester)  $semester = (int)$period['semester'];
        }

        $tid = DB::insert(
            'INSERT INTO tests (school_id,creator_id,title,description,type,time_limit_min,max_attempts,randomise_qs,randomise_opts,show_feedback,available_from,due_at,subject_id,semester,academic_year)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$auth['school_id'],$auth['user_id'],trim($d['title']),$desc,$body['type']??'quiz',
             (int)($body['time_limit_min']??0),(int)($body['max_attempts']??1),
             (int)($body['randomise_qs']??1),(int)($body['randomise_opts']??1),
             (int)($body['show_feedback']??1),$body['available_from']??null,$body['due_at']??null,$subjectId,$semester,$acYear]
        );
        // question_ids accepts [{id,section}] objects or plain integers
        if (!empty($body['question_ids'])) {
            foreach ($body['question_ids'] as $order => $item) {
                $qid = is_array($item) ? (int)$item['id'] : (int)$item;
                $sec = is_array($item) ? ($item['section'] ?? null) : null;
                DB::execute('INSERT INTO test_questions (test_id,question_id,sort_order,section) VALUES (?,?,?,?)',
                    [$tid, $qid, $order, $sec]);
            }
        }
        if (!empty($body['class_ids'])) {
            foreach ($body['class_ids'] as $cid)
                DB::execute('INSERT INTO test_assignments (test_id,class_id) VALUES (?,?)', [$tid,(int)$cid]);
        }
        logActivity($auth, 'test.created', 'test', (int)$tid, trim($d['title']), 'Created test: '.trim($d['title']));
        respond(['test_id'=>$tid,'message'=>'Test created'], 201);
    }

    if ($method === 'PATCH' && $id) {
        require_role($auth, 'teacher', 'admin');
        $allowed = ['title','status','time_limit_min','max_attempts','randomise_qs','randomise_opts','show_feedback','due_at','available_from','description','type','subject_id','semester'];
        $sets=[]; $params=[];
        foreach ($allowed as $f) {
            if (array_key_exists($f,$body)) { $sets[]="$f=?"; $params[]=$body[$f]; }
        }
        // Always record who last updated
        $sets[]   = 'updated_by=?';
        $params[] = $auth['user_id'];
        if ($sets) {
            $params[]=$id; $params[]=$auth['school_id'];
            DB::execute('UPDATE tests SET '.implode(',',$sets).',updated_at=NOW() WHERE id=? AND school_id=?', $params);
        }
        // Replace questions if provided — accepts [{id,section}] or plain integers
        if (isset($body['question_ids']) && is_array($body['question_ids'])) {
            DB::execute('DELETE FROM test_questions WHERE test_id=?', [$id]);
            foreach ($body['question_ids'] as $order => $item) {
                if (!$item) continue;
                $qid = is_array($item) ? (int)$item['id'] : (int)$item;
                $sec = is_array($item) ? ($item['section'] ?? null) : null;
                if ($qid) DB::execute(
                    'INSERT INTO test_questions (test_id,question_id,sort_order,section) VALUES (?,?,?,?)',
                    [$id, $qid, $order, $sec]
                );
            }
        }
        // Replace class assignments if provided
        if (isset($body['class_ids']) && is_array($body['class_ids'])) {
            DB::execute('DELETE FROM test_assignments WHERE test_id=? AND class_id IS NOT NULL', [$id]);
            foreach ($body['class_ids'] as $cid) {
                if ($cid) DB::execute('INSERT INTO test_assignments (test_id,class_id) VALUES (?,?)', [$id,(int)$cid]);
            }
        }
        respond(['message'=>'Test updated']);
    }

    // DELETE /tests/{id} — teacher or admin deletes their own test
    if ($method === 'DELETE' && $id) {
        require_role($auth, 'teacher', 'admin');
        $test = DB::fetchOne('SELECT id, creator_id FROM tests WHERE id=? AND school_id=?', [$id, $auth['school_id']]);
        if (!$test) err('Test not found', 404);
        // Teachers may only delete tests they created (admins can delete any)
        if ($auth['role'] === 'teacher' && (int)$test['creator_id'] !== (int)$auth['user_id'])
            err('You can only delete tests you created', 403);
        // Cascade: remove assignments, questions, attempts (answers cascade from attempts)
        $delTest = DB::fetchOne('SELECT title FROM tests WHERE id=?', [$id]);
        DB::execute('DELETE FROM test_assignments WHERE test_id=?', [$id]);
        DB::execute('DELETE FROM test_questions   WHERE test_id=?', [$id]);
        DB::execute('DELETE FROM attempts         WHERE test_id=?', [$id]);
        DB::execute('DELETE FROM tests            WHERE id=?',      [$id]);
        logActivity($auth, 'test.deleted', 'test', (int)$id, $delTest['title']??'', 'Test deleted');
        respond(['message' => 'Test deleted']);
    }

    err('Tests endpoint error', 404);
}

// ══════════════════════════════════════════════════════════════
// ATTEMPTS
// ══════════════════════════════════════════════════════════════
function handleAttempts(string $method, ?int $id, ?string $sub, array $body): void {
    $auth = require_auth();

    if ($method === 'POST') {
        $tid = (int)($body['test_id'] ?? 0);
        if (!$tid) err('test_id required');
        $uid = $auth['user_id'];
        $test = DB::fetchOne('SELECT id,max_attempts,time_limit_min,status FROM tests WHERE id=?', [$tid]);
        if (!$test) err('Test not found', 404);
        if ($test['status'] !== 'published') err('Test not available');
        $existing = DB::fetchOne('SELECT COUNT(*) AS n FROM attempts WHERE test_id=? AND student_id=?', [$tid,$uid])['n'];
        if ($test['max_attempts'] > 0 && $existing >= $test['max_attempts']) err('No attempts remaining', 409);
        $inProgress = DB::fetchOne('SELECT id FROM attempts WHERE test_id=? AND student_id=? AND status="in_progress"', [$tid,$uid]);
        if ($inProgress) respond(['attempt_id'=>$inProgress['id'],'resumed'=>true]);
        $maxScore = DB::fetchOne('SELECT COALESCE(SUM(q.marks),0) AS total FROM test_questions tq JOIN questions q ON q.id=tq.question_id WHERE tq.test_id=?', [$tid])['total'];
        $aid = DB::insert('INSERT INTO attempts (test_id,student_id,attempt_num,max_score,ip_address) VALUES (?,?,?,?,?)',
            [$tid,$uid,$existing+1,$maxScore,$_SERVER['REMOTE_ADDR']??null]);
        respond(['attempt_id'=>$aid,'resumed'=>false,'max_score'=>$maxScore], 201);
    }

    if ($method === 'GET' && $id && !$sub) {
        $a = DB::fetchOne('SELECT * FROM attempts WHERE id=?', [$id]);
        if (!$a) err('Attempt not found', 404);
        if ($auth['role']==='student' && $a['student_id']!=$auth['user_id']) err('Forbidden',403);
        $a['answers'] = DB::fetchAll('SELECT an.id AS answer_id, an.question_id, an.selected_opts, an.text_response, an.is_flagged, an.marks_awarded, an.teacher_feedback, an.is_correct, q.type AS question_type, q.marks AS question_marks FROM answers an JOIN questions q ON q.id=an.question_id WHERE an.attempt_id=? ORDER BY q.id', [$id]);
        respond($a);
    }

    // GET /attempts/{id}/review — full per-question answer review for student
    if ($method === 'GET' && $id && $sub === 'review') {
        $a = DB::fetchOne(
            'SELECT a.*, t.show_feedback, t.title AS test_title, t.type AS test_type, t.subject_id
             FROM attempts a JOIN tests t ON t.id=a.test_id
             WHERE a.id=? AND a.student_id=?',
            [$id, $auth['user_id']]
        );
        if (!$a) err('Attempt not found or access denied', 404);
        $questions = DB::fetchAll(
            "SELECT q.id, q.type, q.question_text, q.marks, q.sub_strand, q.topic,
                    q.explanation, q.rubric,
                    an.selected_opts, an.text_response, an.is_correct,
                    an.marks_awarded, an.teacher_feedback,
                    (SELECT JSON_ARRAYAGG(JSON_OBJECT('label',option_label,'text',option_text,'correct',is_correct))
                     FROM question_options WHERE question_id=q.id ORDER BY sort_order) AS options
             FROM answers an
             JOIN questions q ON q.id=an.question_id
             WHERE an.attempt_id=?
             ORDER BY q.id",
            [$id]
        );
        $score = (float)$a['score_auto'] + (float)$a['score_manual'];
        respond([
            'attempt' => [
                'id'            => (int)$a['id'],
                'test_id'       => (int)$a['test_id'],
                'test_title'    => $a['test_title'],
                'test_type'     => $a['test_type'],
                'attempt_num'   => (int)$a['attempt_num'],
                'score'         => $score,
                'max_score'     => (float)$a['max_score'],
                'pct_score'     => $a['max_score'] > 0 ? round($score / $a['max_score'] * 100, 1) : 0,
                'status'        => $a['status'],
                'submitted_at'  => $a['submitted_at'],
                'show_feedback' => (bool)(int)$a['show_feedback'],
            ],
            'questions' => $questions,
        ]);
    }

    if ($method === 'PATCH' && $id && $sub === 'submit') {
        $a = DB::fetchOne('SELECT * FROM attempts WHERE id=?', [$id]);
        if (!$a) err('Attempt not found', 404);
        if ($auth['role']==='student' && $a['student_id']!=$auth['user_id']) err('Forbidden',403);
        if ($a['status']!=='in_progress') err('Already submitted');
        $timeTaken = (int)($body['time_taken_s'] ?? (time()-strtotime($a['started_at'])));
        DB::execute('UPDATE attempts SET status="submitted",submitted_at=NOW(),time_taken_s=? WHERE id=?', [$timeTaken,$id]);
        // Tag attempt with current academic period for this student's year group
        try {
            $testSchool = DB::fetchOne('SELECT school_id FROM tests WHERE id=?', [$a['test_id']]);
            $stuClass   = DB::fetchOne('SELECT class_name FROM users WHERE id=?',  [$a['student_id']]);
            if ($testSchool && $stuClass && $stuClass['class_name']) {
                $clsInfo = DB::fetchOne('SELECT year_group FROM classes WHERE school_id=? AND name=?',
                    [(int)$testSchool['school_id'], $stuClass['class_name']]);
                $yg = $clsInfo ? (int)$clsInfo['year_group'] : 1;
                $ap = _currentPeriod((int)$testSchool['school_id'], $yg);
                DB::execute('UPDATE attempts SET academic_year=?, attempt_semester=? WHERE id=?',
                    [$ap['academic_year'], $ap['semester'], $id]);
            }
        } catch (Throwable $e) {}
        // Auto-mark MCQ answers
        $mcqAnswers = DB::fetchAll(
            'SELECT an.id,an.question_id,an.selected_opts FROM answers an
             JOIN questions q ON q.id=an.question_id
             WHERE an.attempt_id=? AND q.type IN ("mcq","tf","multi") AND an.is_correct IS NULL', [$id]
        );
        $autoScore = 0;
        foreach ($mcqAnswers as $ans) {
            $correctOpts = DB::fetchAll('SELECT option_label FROM question_options WHERE question_id=? AND is_correct=1', [$ans['question_id']]);
            $correctLabels = array_column($correctOpts,'option_label');
            $selected = json_decode($ans['selected_opts']??'[]',true);
            sort($correctLabels); sort($selected);
            $isCorrect = ($correctLabels === $selected) ? 1 : 0;
            $marks = $isCorrect ? DB::fetchOne('SELECT marks FROM questions WHERE id=?',[$ans['question_id']])['marks'] : 0;
            $autoScore += $marks;
            DB::execute('UPDATE answers SET is_correct=?,marks_awarded=? WHERE id=?', [$isCorrect,$marks,$ans['id']]);
        }
        // Auto-mark blank (fill-in) answers — match against accepted answers (case-insensitive)
        $blankAnswers = DB::fetchAll(
            'SELECT an.id, an.question_id, an.text_response FROM answers an
             JOIN questions q ON q.id=an.question_id
             WHERE an.attempt_id=? AND q.type IN ("fill-in","blank") AND an.is_correct IS NULL', [$id]
        );
        foreach ($blankAnswers as $ba) {
            $accepted = DB::fetchAll(
                'SELECT option_text FROM question_options WHERE question_id=? AND is_correct=1',
                [$ba['question_id']]
            );
            $acceptedList = array_map(fn($r) => mb_strtolower(trim($r['option_text'])), $accepted);
            $studentAns   = mb_strtolower(trim($ba['text_response'] ?? ''));
            if ($studentAns !== '' && in_array($studentAns, $acceptedList)) {
                $blankMarks = (float)(DB::fetchOne('SELECT marks FROM questions WHERE id=?', [$ba['question_id']])['marks'] ?? 1);
                $autoScore += $blankMarks;
                DB::execute('UPDATE answers SET is_correct=1, marks_awarded=? WHERE id=?', [$blankMarks, $ba['id']]);
            }
            // No match → leave is_correct=NULL so it shows in teacher's marking queue
        }

        DB::execute('UPDATE attempts SET score_auto=? WHERE id=?', [$autoScore,$id]);
        // Check if fully marked
        $unmarked = DB::fetchOne('SELECT COUNT(*) AS n FROM answers WHERE attempt_id=? AND is_correct IS NULL', [$id])['n'];
        if ($unmarked==0) DB::execute('UPDATE attempts SET status="marked" WHERE id=?', [$id]);
        // Update streak
        DB::execute(
            'INSERT INTO streaks (student_id,current_streak,longest_streak,last_activity) VALUES (?,1,1,CURDATE())
             ON DUPLICATE KEY UPDATE
             current_streak=IF(last_activity=CURDATE()-INTERVAL 1 DAY,current_streak+1,IF(last_activity=CURDATE(),current_streak,1)),
             longest_streak=GREATEST(longest_streak,current_streak),last_activity=CURDATE()',
            [$a['student_id']]
        );
        $result = DB::fetchOne(
            'SELECT score_auto, score_manual, max_score,
                    ROUND(IF(max_score>0,(score_auto+score_manual)/max_score*100,0),2) AS pct_score
             FROM attempts WHERE id=?', [$id]
        );
        $testRow = DB::fetchOne('SELECT show_feedback, title FROM tests WHERE id=?', [$a['test_id']]);
        $pct = round((float)($result['pct_score'] ?? 0), 1);
        logActivity($auth, 'attempt.submitted', 'test', (int)$a['test_id'], $testRow['title']??'', "Score: {$pct}%");
        respond(array_merge($result??[], [
            'message'         => 'Submitted',
            'attempt_id'      => (int)$id,
            'unmarked_essays' => (int)$unmarked,
            'show_feedback'   => (bool)(int)($testRow['show_feedback']??0),
            'test_title'      => $testRow['title'] ?? '',
        ]));
    }

    err('Attempts endpoint error', 404);
}

// ══════════════════════════════════════════════════════════════
// ANSWERS
// ══════════════════════════════════════════════════════════════
function handleAnswers(string $method, ?int $id, ?string $sub, array $body): void {
    $auth = require_auth();

    if ($method === 'POST') {
        $aid = (int)($body['attempt_id']??0);
        $qid = (int)($body['question_id']??0);
        if (!$aid||!$qid) err('attempt_id and question_id required');
        $attempt = DB::fetchOne('SELECT student_id,status FROM attempts WHERE id=?', [$aid]);
        if (!$attempt) err('Attempt not found', 404);
        if ($auth['role']==='student' && $attempt['student_id']!=$auth['user_id']) err('Forbidden',403);
        if ($attempt['status']!=='in_progress') err('Attempt already submitted',409);
        $sel = isset($body['selected_opts']) ? json_encode(is_array($body['selected_opts'])?$body['selected_opts']:[$body['selected_opts']]) : null;
        DB::execute(
            'INSERT INTO answers (attempt_id,question_id,selected_opts,text_response,is_flagged,time_on_q_s)
             VALUES (?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE selected_opts=VALUES(selected_opts),text_response=VALUES(text_response),
             is_flagged=VALUES(is_flagged),time_on_q_s=VALUES(time_on_q_s),answered_at=NOW()',
            [$aid,$qid,$sel,$body['text_response']??null,(int)($body['is_flagged']??0),(int)($body['time_on_q_s']??0)]
        );
        respond(['message'=>'Saved']);
    }

    if ($method === 'PATCH' && $id) {
        require_role($auth,'teacher','admin');
        $marks   = $body['marks_awarded'] ?? null;
        $feedback= $body['teacher_feedback'] ?? null;
        DB::execute('UPDATE answers SET marks_awarded=?,teacher_feedback=?,is_correct=?,marked_by=?,marked_at=NOW() WHERE id=?',
            [$marks,$feedback,$marks>0?1:0,$auth['user_id'],$id]);
        $ans = DB::fetchOne('SELECT attempt_id FROM answers WHERE id=?', [$id]);
        if ($ans) {
            $manual = DB::fetchOne('SELECT COALESCE(SUM(marks_awarded),0) AS t FROM answers WHERE attempt_id=? AND marked_by IS NOT NULL', [$ans['attempt_id']])['t'];
            DB::execute('UPDATE attempts SET score_manual=? WHERE id=?', [$manual,$ans['attempt_id']]);
            $still = DB::fetchOne('SELECT COUNT(*) AS n FROM answers WHERE attempt_id=? AND is_correct IS NULL', [$ans['attempt_id']])['n'];
            if ($still==0) DB::execute('UPDATE attempts SET status="marked" WHERE id=?', [$ans['attempt_id']]);
        }
        respond(['message'=>'Marked']);
    }

    err('Answers endpoint error', 404);
}

// ══════════════════════════════════════════════════════════════
// STUDENTS
// ══════════════════════════════════════════════════════════════
function handleStudents(string $method, ?int $id, ?string $sub, array $body): void {
    $auth = require_auth();
    ensureClassSubjectSchema(); // class_subjects + student_class_groups

    // GET /students/subjects — subjects enrolled in via the student's class
    if ($method === 'GET' && !$id && $sub === 'subjects') {
        require_role($auth, 'student');
        $uid = (int)$auth['user_id'];
        syncStudentClass($uid, $auth['school_id']);
        $rows = DB::fetchAll(
            "SELECT DISTINCT s.id, s.name, s.short_name, s.category
             FROM class_students cst
             JOIN class_subjects  csj  ON csj.class_id  = cst.class_id
             LEFT JOIN student_class_groups scg
                    ON scg.student_id = cst.student_id AND scg.class_id = cst.class_id
             JOIN subjects s ON s.id = csj.subject_id
             WHERE cst.student_id = ?
               AND (csj.group_tag IS NULL OR scg.group_tag IS NULL OR csj.group_tag = scg.group_tag)
             ORDER BY s.category, s.sort_order, s.name",
            [$uid]
        );
        respond($rows);
    }

    // GET /students/history — all submitted attempts for this student
    if ($method === 'GET' && !$id && $sub === 'history') {
        $uid = $auth['user_id'];
        respond(DB::fetchAll(
            "SELECT a.id AS attempt_id, a.test_id, t.title AS test_title, t.type AS test_type,
                    t.show_feedback, t.subject_id,
                    s.name AS subject_name, s.short_name AS subject_short,
                    a.attempt_num,
                    ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),1) AS pct_score,
                    a.score_auto + COALESCE(a.score_manual,0) AS score,
                    a.max_score, a.status, a.submitted_at,
                    (SELECT COUNT(*) FROM answers an2
                     JOIN questions q2 ON q2.id=an2.question_id
                     WHERE an2.attempt_id=a.id AND q2.type='essay' AND an2.is_correct IS NULL) AS unmarked_essays
             FROM attempts a
             JOIN tests t ON t.id=a.test_id
             LEFT JOIN subjects s ON s.id=t.subject_id
             WHERE a.student_id=? AND a.status IN ('submitted','marked')
             ORDER BY a.submitted_at DESC LIMIT 50",
            [$uid]
        ));
    }

    if ($method === 'GET' && !$id && $sub === 'dashboard') {
        $uid = $auth['user_id'];
        syncStudentClass($uid, $auth['school_id']);

        // Optional filters
        $filterSubjId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
        $filterSem    = isset($_GET['semester'])    ? (int)$_GET['semester']    : 0;
        $filterYear   = isset($_GET['academic_year']) ? trim($_GET['academic_year']) : '';
        $sF  = $filterSubjId ? 'AND t.subject_id = ?' : '';
        $sP  = $filterSubjId ? [$filterSubjId] : [];
        $semF  = $filterSem  ? 'AND t.semester=?'       : '';
        $semP  = $filterSem  ? [$filterSem]  : [];
        $yearF = $filterYear ? 'AND t.academic_year=?'  : '';
        $yearP = $filterYear ? [$filterYear] : [];
        $periodF = $semF . ' ' . $yearF;
        $periodP = array_merge($semP, $yearP);

        // ── Stats (filter by subject when selected) ──────────────
        if ($filterSubjId) {
            $stats = DB::fetchOne(
                "SELECT COUNT(DISTINCT a.id) AS tests_done,
                        AVG(ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),2)) AS avg_pct,
                        MAX(ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),2)) AS best_pct
                 FROM attempts a JOIN tests t ON t.id=a.test_id
                 WHERE a.student_id=? AND a.status IN ('submitted','marked') AND t.subject_id=?",
                [$uid, $filterSubjId]
            );
        } else {
            $stats = DB::fetchOne(
                'SELECT COUNT(*) AS tests_done,
                        AVG(ROUND(IF(max_score>0,(score_auto+score_manual)/max_score*100,0),2)) AS avg_pct,
                        MAX(ROUND(IF(max_score>0,(score_auto+score_manual)/max_score*100,0),2)) AS best_pct
                 FROM attempts WHERE student_id=? AND status IN ("submitted","marked")',
                [$uid]
            );
        }

        $streak = DB::fetchOne('SELECT current_streak,longest_streak,total_xp FROM streaks WHERE student_id=?', [$uid]);

        // ── Performance breakdown ─────────────────────────────────
        // With subject filter → sub-strand breakdown within that subject
        // Without filter      → per-subject averages
        if ($filterSubjId) {
            $subjectAvg = [];
            $subAvg = DB::fetchAll(
                "SELECT q.sub_strand,
                        ROUND(AVG(IF(an.is_correct=1,100,0)),1) AS avg_pct,
                        COUNT(DISTINCT a.id) AS attempt_count
                 FROM answers an
                 JOIN questions q  ON q.id  = an.question_id
                 JOIN attempts  a  ON a.id  = an.attempt_id
                 JOIN tests     t  ON t.id  = a.test_id
                 WHERE a.student_id=? AND t.subject_id=?
                   AND an.is_correct IS NOT NULL AND q.sub_strand IS NOT NULL
                 GROUP BY q.sub_strand
                 ORDER BY q.sub_strand",
                [$uid, $filterSubjId]
            );
        } else {
            $subAvg = DB::fetchAll(
                'SELECT q.sub_strand, AVG(an.is_correct*100) AS avg_pct
                 FROM answers an JOIN questions q ON q.id=an.question_id JOIN attempts a ON a.id=an.attempt_id
                 WHERE a.student_id=? AND an.marks_awarded IS NOT NULL GROUP BY q.sub_strand',
                [$uid]
            );
            $subjectAvg = DB::fetchAll(
                "SELECT s.id AS subject_id, s.name AS subject_name, s.short_name AS subject_short,
                        AVG(ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),2)) AS avg_pct,
                        COUNT(DISTINCT a.id) AS attempt_count
                 FROM attempts a
                 JOIN tests t ON t.id=a.test_id
                 JOIN subjects s ON s.id=t.subject_id
                 WHERE a.student_id=? AND a.status IN ('submitted','marked')
                 GROUP BY s.id, s.name, s.short_name ORDER BY s.name",
                [$uid]
            );
        }

        // ── Which class-subjects this student studies ─────────────
        $dashSubjIds = array_column(DB::fetchAll(
            "SELECT DISTINCT csubj.subject_id
             FROM class_students cst
             JOIN class_subjects csubj ON csubj.class_id = cst.class_id
             LEFT JOIN student_class_groups scg ON scg.student_id=cst.student_id AND scg.class_id=cst.class_id
             WHERE cst.student_id=?
               AND (csubj.group_tag IS NULL OR csubj.group_tag=scg.group_tag)",
            [$uid]
        ), 'subject_id');

        $dashSubjFilter = '';
        $dashSubjParams = [];
        if ($filterSubjId) {
            $dashSubjFilter = 'AND t.subject_id = ?';
            $dashSubjParams = [$filterSubjId];
        } elseif (!empty($dashSubjIds)) {
            $ph = implode(',', array_fill(0, count($dashSubjIds), '?'));
            $dashSubjFilter = "AND (t.subject_id IS NULL OR t.subject_id IN ($ph))";
            $dashSubjParams = $dashSubjIds;
        }

        // ── Due tests ─────────────────────────────────────────────
        $due = DB::fetchAll(
            "SELECT DISTINCT t.id, t.title, t.due_at, t.time_limit_min, t.subject_id,
                    s.name AS subject_name, s.short_name AS subject_short,
                    (SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) AS question_count,
                    (SELECT COUNT(*) FROM attempts WHERE test_id=t.id AND student_id=?) AS my_attempts
             FROM tests t
             JOIN test_assignments ta ON ta.test_id=t.id
             JOIN class_students   cs ON cs.class_id=ta.class_id AND cs.student_id=?
             LEFT JOIN subjects s ON s.id=t.subject_id
             WHERE t.status='published'
               AND (t.due_at IS NULL OR t.due_at>NOW())
               $dashSubjFilter
             GROUP BY t.id HAVING my_attempts=0
             ORDER BY t.due_at IS NULL, t.due_at ASC LIMIT 10",
            array_merge([$uid, $uid], $dashSubjParams)
        );

        // ── Recent results ────────────────────────────────────────
        $recent = DB::fetchAll(
            "SELECT a.id, t.title, t.subject_id,
                    s.name AS subject_name, s.short_name AS subject_short,
                    ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),2) AS pct_score,
                    a.submitted_at
             FROM attempts a
             JOIN tests t ON t.id=a.test_id
             LEFT JOIN subjects s ON s.id=t.subject_id
             WHERE a.student_id=? AND a.status IN ('submitted','marked') $sF
             ORDER BY a.submitted_at DESC LIMIT 5",
            array_merge([$uid], $sP)
        );

        $srDue = DB::fetchOne('SELECT COUNT(*) AS n FROM spaced_repetition WHERE student_id=? AND next_review<=CURDATE()', [$uid])['n'] ?? 0;

        // ── Test history (for progress chart) ─────────────────────
        $testHistory = DB::fetchAll(
            "SELECT a.submitted_at, t.title, t.semester, t.academic_year,
                    ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),1) AS pct_score,
                    IF(a.max_score>0 AND (a.score_auto+a.score_manual)/a.max_score>=0.5,1,0) AS passed,
                    s.short_name AS subject_short
             FROM attempts a JOIN tests t ON t.id=a.test_id
             LEFT JOIN subjects s ON s.id=t.subject_id
             WHERE a.student_id=? AND a.status IN ('submitted','marked') $sF $periodF
             ORDER BY a.submitted_at ASC LIMIT 30",
            array_merge([$uid], $sP, $periodP)
        );

        // ── Semester comparison ───────────────────────────────────
        $semesterStats = DB::fetchAll(
            "SELECT t.academic_year, t.semester,
                    COUNT(DISTINCT a.id) AS tests_done,
                    ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                    SUM(IF(a.max_score>0 AND (a.score_auto+a.score_manual)/a.max_score>=0.5,1,0)) AS pass_count
             FROM attempts a JOIN tests t ON t.id=a.test_id
             WHERE a.student_id=? AND a.status IN ('submitted','marked') AND t.academic_year IS NOT NULL $sF
             GROUP BY t.academic_year, t.semester ORDER BY t.academic_year DESC, t.semester DESC LIMIT 6",
            array_merge([$uid], $sP)
        );

        // ── Rank in class ─────────────────────────────────────────
        $studentClass = DB::fetchOne('SELECT class_name FROM users WHERE id=?', [$uid]);
        $classRank    = null;
        if ($studentClass && $studentClass['class_name']) {
            $classmates = DB::fetchAll(
                "SELECT a.student_id,
                        AVG(ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),2)) AS avg_pct
                 FROM attempts a
                 JOIN tests t ON t.id=a.test_id
                 JOIN class_students cs ON cs.student_id=a.student_id
                 JOIN classes c ON c.id=cs.class_id
                 WHERE c.school_id=? AND c.name=? AND a.status IN ('submitted','marked') $sF
                 GROUP BY a.student_id ORDER BY avg_pct DESC",
                array_merge([$auth['school_id'], $studentClass['class_name']], $sP)
            );
            $total = count($classmates);
            $pos   = array_search($uid, array_column($classmates,'student_id'));
            if ($pos !== false && $total > 0) {
                $classRank = ['rank' => $pos+1, 'total' => $total,
                              'percentile' => round((1 - $pos/$total)*100)];
            }
        }

        // ── Question type stats (MCQ vs essay etc) ────────────────
        $typeStats = DB::fetchAll(
            "SELECT q.type,
                    COUNT(*) AS total_answers,
                    SUM(IF(an.is_correct=1,1,0)) AS correct,
                    ROUND(AVG(IF(an.is_correct=1,100,0)),1) AS accuracy_pct
             FROM answers an JOIN questions q ON q.id=an.question_id
             JOIN attempts a ON a.id=an.attempt_id JOIN tests t ON t.id=a.test_id
             WHERE a.student_id=? AND an.is_correct IS NOT NULL $sF $periodF
             GROUP BY q.type",
            array_merge([$uid], $sP, $periodP)
        );

        // ── Bloom's level accuracy ────────────────────────────────
        $bloomStats = DB::fetchAll(
            "SELECT q.bloom_level,
                    ROUND(AVG(IF(an.is_correct=1,100,0)),1) AS avg_pct,
                    COUNT(*) AS total
             FROM answers an JOIN questions q ON q.id=an.question_id
             JOIN attempts a ON a.id=an.attempt_id JOIN tests t ON t.id=a.test_id
             WHERE a.student_id=? AND an.is_correct IS NOT NULL AND q.bloom_level IS NOT NULL $sF
             GROUP BY q.bloom_level",
            array_merge([$uid], $sP)
        );

        // ── Completion rate ───────────────────────────────────────
        $assigned = DB::fetchOne(
            "SELECT COUNT(DISTINCT t.id) AS assigned
             FROM tests t JOIN test_assignments ta ON ta.test_id=t.id
             JOIN class_students cs ON cs.class_id=ta.class_id AND cs.student_id=?
             WHERE t.status='published' $sF",
            array_merge([$uid], $sP)
        );
        $completedCount = $stats['tests_done'] ?? 0;
        $assignedCount  = max((int)($assigned['assigned'] ?? 0), $completedCount);
        $completionRate = $assignedCount > 0 ? round($completedCount/$assignedCount*100) : 0;

        respond([
            'stats'           => $stats,
            'streak'          => $streak,
            'substrand_avg'   => $subAvg,
            'subject_avg'     => $subjectAvg ?? [],
            'due'             => $due,
            'recent'          => $recent,
            'sr_due'          => (int)$srDue,
            'filtered_subject_id' => $filterSubjId ?: null,
            'test_history'    => $testHistory,
            'semester_stats'  => $semesterStats,
            'class_rank'      => $classRank,
            'type_stats'      => $typeStats,
            'bloom_stats'     => $bloomStats,
            'completion_rate' => $completionRate,
            'assigned_count'  => $assignedCount,
        ]);
    }

    if ($method === 'GET' && $id) {
        require_role($auth,'teacher','admin');
        $s = DB::fetchOne(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.class_name, u.avatar_color, u.last_login,
                    c.id AS class_id, c.year_group,
                    (SELECT COUNT(*) FROM attempts WHERE student_id=u.id AND status IN ("submitted","marked")) AS tests_done,
                    (SELECT AVG(ROUND(IF(max_score>0,(score_auto+score_manual)/max_score*100,0),2))
                     FROM attempts WHERE student_id=u.id AND status IN ("submitted","marked")) AS avg_pct
             FROM users u
             LEFT JOIN class_students cst ON cst.student_id = u.id
             LEFT JOIN classes c ON c.id = cst.class_id
             WHERE u.id=? AND u.school_id=? AND u.role="student"
             LIMIT 1',
            [$id, $auth['school_id']]
        );
        if (!$s) err('Student not found', 404);

        // Subjects the student studies (respects group splits)
        $s['subjects'] = DB::fetchAll(
            "SELECT DISTINCT sub.id AS subject_id, sub.name AS subject_name,
                    sub.short_name, sub.category, csubj.group_tag
             FROM class_students cst
             JOIN class_subjects csubj ON csubj.class_id = cst.class_id
             LEFT JOIN student_class_groups scg
                    ON scg.student_id = cst.student_id AND scg.class_id = cst.class_id
             JOIN subjects sub ON sub.id = csubj.subject_id
             WHERE cst.student_id = ?
               AND (csubj.group_tag IS NULL OR csubj.group_tag = scg.group_tag)
             ORDER BY sub.category, sub.name",
            [$id]
        );

        // Per-subject performance (attempts that have a subject)
        $s['subject_performance'] = DB::fetchAll(
            "SELECT sub.id AS subject_id, sub.name AS subject_name, sub.short_name,
                    COUNT(DISTINCT a.id) AS tests_done,
                    ROUND(AVG(ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),2)),1) AS avg_pct,
                    ROUND(MAX(ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),2)),1) AS best_pct
             FROM attempts a
             JOIN tests t ON t.id = a.test_id
             JOIN subjects sub ON sub.id = t.subject_id
             WHERE a.student_id = ? AND a.status IN ('submitted','marked')
             GROUP BY sub.id, sub.name, sub.short_name
             ORDER BY sub.name",
            [$id]
        );

        // Full attempt history (last 30)
        $s['recent_attempts'] = DB::fetchAll(
            "SELECT a.id, t.title, t.type,
                    sub.name AS subject_name, sub.short_name AS subject_short,
                    ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),2) AS pct_score,
                    a.score_auto + a.score_manual AS raw_score, a.max_score,
                    a.status, a.submitted_at
             FROM attempts a
             JOIN tests t ON t.id = a.test_id
             LEFT JOIN subjects sub ON sub.id = t.subject_id
             WHERE a.student_id = ? AND a.status IN ('submitted','marked')
             ORDER BY a.submitted_at DESC LIMIT 30",
            [$id]
        );

        $s['streak']       = DB::fetchOne('SELECT * FROM streaks WHERE student_id=?', [$id]);
        $s['substrand_avg'] = DB::fetchAll(
            'SELECT q.sub_strand, AVG(an.is_correct*100) AS avg_pct
             FROM answers an JOIN questions q ON q.id=an.question_id JOIN attempts a ON a.id=an.attempt_id
             WHERE a.student_id=? AND an.marks_awarded IS NOT NULL
             GROUP BY q.sub_strand',
            [$id]
        );
        respond($s);
    }

    if ($method === 'GET' && !$id) {
        require_role($auth,'teacher','admin');
        $cols = "u.id,u.first_name,u.last_name,u.email,u.class_name,u.avatar_color,u.is_active,
                 s.current_streak,s.total_xp,
                 (SELECT COUNT(*) FROM attempts WHERE student_id=u.id AND status IN ('submitted','marked')) AS tests_done,
                 (SELECT AVG(ROUND(IF(max_score>0,(score_auto+score_manual)/max_score*100,0),2)) FROM attempts WHERE student_id=u.id AND status IN ('submitted','marked')) AS avg_pct";
        if ($auth['role'] === 'teacher') {
            // Teachers see students in classes where they are form teacher OR subject teacher
            respond(DB::fetchAll(
                "SELECT DISTINCT $cols
                 FROM users u
                 LEFT JOIN streaks s ON s.student_id=u.id
                 JOIN class_students cs2 ON cs2.student_id=u.id
                 JOIN classes c ON c.id=cs2.class_id
                   AND (c.teacher_id=? OR EXISTS (SELECT 1 FROM class_teachers ct WHERE ct.class_id=c.id AND ct.teacher_id=?))
                 WHERE u.school_id=? AND u.role='student' AND u.is_active=1
                 ORDER BY u.class_name,u.last_name",
                [$auth['user_id'], $auth['user_id'], $auth['school_id']]
            ));
        } else {
            respond(DB::fetchAll(
                "SELECT $cols FROM users u LEFT JOIN streaks s ON s.student_id=u.id
                 WHERE u.school_id=? AND u.role='student' AND u.is_active=1
                 ORDER BY u.class_name,u.last_name",
                [$auth['school_id']]
            ));
        }
    }

    // POST /students — admin creates a student
    if ($method === 'POST' && !$id) {
        require_role($auth,'admin');
        ['first_name'=>$fn,'last_name'=>$ln,'email'=>$em,'password'=>$pw]
            = need($body,'first_name','last_name','email','password');
        if (!filter_var($em, FILTER_VALIDATE_EMAIL)) err('Invalid email address');
        if (strlen($pw) < 6) err('Password must be at least 6 characters');
        if (DB::fetchOne('SELECT id FROM users WHERE email=?',[strtolower(trim($em))]))
            err('Email already registered',409);

        // Resolve class: accept class_id (int) or class_name (string)
        [$className, $classId] = resolveStudentClass($body, $auth['school_id']);

        $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost'=>12]);
        $uid  = DB::insert(
            'INSERT INTO users (school_id,role,first_name,last_name,email,password_hash,class_name,avatar_color,must_change_password)
             VALUES (?,?,?,?,?,?,?,?,1)',
            [$auth['school_id'],'student',trim($fn),trim($ln),strtolower(trim($em)),$hash,
             $className, $body['avatar_color']??'#1A7A4A']
        );
        DB::execute('INSERT INTO streaks (student_id) VALUES (?)', [$uid]);
        // Write junction row so class_students queries work
        if ($classId) {
            DB::execute('INSERT IGNORE INTO class_students (class_id,student_id) VALUES (?,?)', [$classId, $uid]);
        }
        respond(['user_id'=>$uid,'message'=>'Student created'],201);
    }

    // PATCH /students/{id} — admin/teacher updates a student
    if ($method === 'PATCH' && $id) {
        require_role($auth,'admin','teacher');
        $allowed = ['first_name','last_name','email','avatar_color'];
        $sets=[]; $params=[];
        foreach ($allowed as $f) {
            if (array_key_exists($f,$body)) { $sets[]="$f=?"; $params[]=$body[$f]; }
        }

        // Handle class change (accepts class_id or class_name)
        $classChanged = isset($body['class_id']) || array_key_exists('class_name', $body);
        if ($classChanged) {
            [$className, $classId] = resolveStudentClass($body, $auth['school_id']);
            $sets[]   = 'class_name=?';
            $params[] = $className;
            // Re-sync junction table (single class per student)
            DB::execute('DELETE FROM class_students WHERE student_id=?', [$id]);
            if ($classId) {
                DB::execute('INSERT IGNORE INTO class_students (class_id,student_id) VALUES (?,?)', [$classId, $id]);
            }
        }

        // Optional password reset
        if (!empty($body['password']) && strlen($body['password']) >= 6) {
            $sets[]   = 'password_hash=?';
            $params[] = password_hash($body['password'], PASSWORD_BCRYPT, ['cost'=>12]);
        }
        if ($sets) {
            $params[]=$id; $params[]=$auth['school_id'];
            DB::execute('UPDATE users SET '.implode(',',$sets).' WHERE id=? AND school_id=? AND role="student"',$params);
        }
        respond(['message'=>'Student updated']);
    }

    // DELETE /students/{id} — admin deactivates a student
    if ($method === 'DELETE' && $id) {
        require_role($auth,'admin');
        DB::execute('UPDATE users SET is_active=0 WHERE id=? AND school_id=? AND role="student"',[$id,$auth['school_id']]);
        respond(['message'=>'Student deactivated']);
    }

    err('Students endpoint error',404);
}

// ══════════════════════════════════════════════════════════════
// CLASSES
// ══════════════════════════════════════════════════════════════
function handleClasses(string $method, ?int $id, ?string $sub, array $body): void {
    $auth = require_auth();
    require_role($auth,'teacher','admin');
    ensureDeptSchema();          // ensures class_teachers table exists
    ensureClassSubjectSchema();  // ensures class_subjects + student_class_groups

    if ($method === 'GET' && !$id) {
        $where  = 'c.school_id = ?';
        $params = [$auth['school_id']];
        // Teachers see classes where they are the form teacher OR a subject teacher
        if ($auth['role'] === 'teacher') {
            $uid = $auth['user_id'];
            $where .= ' AND (c.teacher_id=? OR EXISTS (SELECT 1 FROM class_teachers ct WHERE ct.class_id=c.id AND ct.teacher_id=?))';
            $params[] = $uid;
            $params[] = $uid;
        }
        $rows = DB::fetchAll(
            "SELECT c.*,
                    CONCAT(u.first_name,' ',u.last_name) AS teacher_name,
                    (SELECT COUNT(*) FROM class_students WHERE class_id=c.id) AS student_count,
                    (SELECT AVG(a.pct_score) FROM attempts a
                     JOIN class_students cs ON cs.student_id=a.student_id
                     WHERE cs.class_id=c.id AND a.status IN ('submitted','marked')) AS class_avg
             FROM classes c
             LEFT JOIN users u ON u.id=c.teacher_id
             WHERE $where ORDER BY c.year_group, c.name",
            $params
        );
        // Attach subject teachers for each class
        foreach ($rows as &$row) {
            $row['subject_teachers'] = DB::fetchAll(
                "SELECT u.id, CONCAT(u.first_name,' ',u.last_name) AS name, ct.subject
                 FROM class_teachers ct JOIN users u ON u.id=ct.teacher_id
                 WHERE ct.class_id=?",
                [$row['id']]
            );
        }
        respond($rows);
    }

    // POST /classes/{id}/teachers — assign subject teacher
    if ($method === 'POST' && $id && $sub === 'teachers') {
        require_role($auth,'admin');
        $tid     = (int)($body['teacher_id'] ?? 0);
        $subject = trim($body['subject'] ?? 'ICT');
        if (!$tid) err('teacher_id required');
        DB::execute('INSERT IGNORE INTO class_teachers (class_id,teacher_id,subject) VALUES (?,?,?)', [$id,$tid,$subject]);
        respond(['message' => 'Teacher assigned to class']);
    }

    // DELETE /classes/{id}/teachers  (body: teacher_id)
    if ($method === 'DELETE' && $id && $sub === 'teachers') {
        require_role($auth,'admin');
        $tid = (int)($body['teacher_id'] ?? 0);
        if (!$tid) err('teacher_id required');
        DB::execute('DELETE FROM class_teachers WHERE class_id=? AND teacher_id=?', [$id,$tid]);
        respond(['message' => 'Teacher removed from class']);
    }

    // ── Class subjects sub-routes ─────────────────────────────────

    // GET /classes/{id}/subjects — list subjects assigned to this class
    if ($method === 'GET' && $id && $sub === 'subjects') {
        require_role($auth,'teacher','admin');
        respond(DB::fetchAll(
            "SELECT cs.id AS assignment_id, cs.subject_id, s.name, s.short_name, s.category, cs.group_tag
             FROM class_subjects cs
             JOIN subjects s ON s.id = cs.subject_id
             WHERE cs.class_id = ?
             ORDER BY s.category, s.name",
            [$id]
        ));
    }

    // GET /classes/{id}/students — students in this class with their group tag
    if ($method === 'GET' && $id && $sub === 'students') {
        require_role($auth,'teacher','admin');
        respond(DB::fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.avatar_color,
                    scg.group_tag,
                    (SELECT COUNT(*) FROM attempts WHERE student_id=u.id AND status IN ('submitted','marked')) AS tests_done,
                    (SELECT ROUND(AVG(ROUND(IF(max_score>0,(score_auto+score_manual)/max_score*100,0),2)),1)
                     FROM attempts WHERE student_id=u.id AND status IN ('submitted','marked')) AS avg_pct
             FROM class_students cst
             JOIN users u ON u.id = cst.student_id
             LEFT JOIN student_class_groups scg ON scg.student_id = u.id AND scg.class_id = cst.class_id
             WHERE cst.class_id = ? AND u.is_active = 1
             ORDER BY u.last_name, u.first_name",
            [$id]
        ));
    }

    // GET /classes/{id}/groups — students + their group assignments
    if ($method === 'GET' && $id && $sub === 'groups') {
        require_role($auth,'teacher','admin');
        respond(DB::fetchAll(
            "SELECT u.id AS student_id, CONCAT(u.first_name,' ',u.last_name) AS name,
                    scg.group_tag
             FROM class_students cst
             JOIN users u ON u.id = cst.student_id
             LEFT JOIN student_class_groups scg ON scg.student_id = u.id AND scg.class_id = cst.class_id
             WHERE cst.class_id = ? AND u.is_active = 1
             ORDER BY scg.group_tag IS NULL, scg.group_tag, u.last_name",
            [$id]
        ));
    }

    // POST /classes/{id}/subjects — assign a subject (body: subject_id, group_tag?)
    if ($method === 'POST' && $id && $sub === 'subjects') {
        require_role($auth,'admin');
        $subjectId = (int)($body['subject_id'] ?? 0);
        $groupTag  = isset($body['group_tag']) ? (trim($body['group_tag']) ?: null) : null;
        if (!$subjectId) err('subject_id required');
        DB::execute(
            'INSERT IGNORE INTO class_subjects (class_id, subject_id, group_tag) VALUES (?,?,?)',
            [$id, $subjectId, $groupTag]
        );
        respond(['message' => 'Subject assigned to class'], 201);
    }

    // PATCH /classes/{id}/subjects — update group tag (body: subject_id, group_tag)
    if ($method === 'PATCH' && $id && $sub === 'subjects') {
        require_role($auth,'admin');
        $subjectId = (int)($body['subject_id'] ?? 0);
        $groupTag  = isset($body['group_tag']) ? (trim($body['group_tag']) ?: null) : null;
        if (!$subjectId) err('subject_id required');
        DB::execute(
            'UPDATE class_subjects SET group_tag=? WHERE class_id=? AND subject_id=?',
            [$groupTag, $id, $subjectId]
        );
        respond(['message' => 'Group tag updated']);
    }

    // DELETE /classes/{id}/subjects — remove subject (body: subject_id)
    if ($method === 'DELETE' && $id && $sub === 'subjects') {
        require_role($auth,'admin');
        $subjectId = (int)($body['subject_id'] ?? 0);
        if (!$subjectId) err('subject_id required');
        DB::execute('DELETE FROM class_subjects WHERE class_id=? AND subject_id=?', [$id, $subjectId]);
        respond(['message' => 'Subject removed from class']);
    }

    // POST /classes/{id}/groups — set or clear a student's group (body: student_id, group_tag)
    if ($method === 'POST' && $id && $sub === 'groups') {
        require_role($auth,'admin');
        $studentId = (int)($body['student_id'] ?? 0);
        $groupTag  = trim($body['group_tag'] ?? '');
        if (!$studentId) err('student_id required');
        if ($groupTag !== '') {
            DB::execute(
                'INSERT INTO student_class_groups (student_id, class_id, group_tag) VALUES (?,?,?)
                 ON DUPLICATE KEY UPDATE group_tag = VALUES(group_tag)',
                [$studentId, $id, $groupTag]
            );
        } else {
            DB::execute('DELETE FROM student_class_groups WHERE student_id=? AND class_id=?', [$studentId, $id]);
        }
        respond(['message' => 'Student group updated']);
    }

    // ─────────────────────────────────────────────────────────────

    if ($method === 'POST' && !$id) {
        require_role($auth,'admin','teacher');
        $d = need($body,'name','year_group');
        $teacherId = isset($body['teacher_id']) ? (int)$body['teacher_id'] : $auth['user_id'];
        $cid = DB::insert('INSERT INTO classes (school_id,teacher_id,name,year_group) VALUES (?,?,?,?)',
            [$auth['school_id'], $teacherId, trim($d['name']), (int)$d['year_group']]);
        // If a teacher_id was supplied, also add to class_teachers
        if ($teacherId) DB::execute('INSERT IGNORE INTO class_teachers (class_id,teacher_id,subject) VALUES (?,?,?)',[$cid,$teacherId,'ICT']);
        respond(['class_id'=>$cid,'message'=>'Class created'],201);
    }

    if ($method === 'PATCH' && $id) {
        require_role($auth,'admin');
        $allowed = ['name','year_group','teacher_id'];
        $sets=[]; $params=[];
        foreach ($allowed as $f) {
            if (array_key_exists($f,$body)) { $sets[]="$f=?"; $params[]=$body[$f]; }
        }
        if ($sets) {
            $params[]=$id; $params[]=$auth['school_id'];
            DB::execute('UPDATE classes SET '.implode(',',$sets).' WHERE id=? AND school_id=?', $params);
        }
        respond(['message'=>'Class updated']);
    }

    if ($method === 'DELETE' && $id) {
        require_role($auth,'admin');
        DB::execute('DELETE FROM class_students WHERE class_id=?', [$id]);
        DB::execute('DELETE FROM classes WHERE id=? AND school_id=?', [$id,$auth['school_id']]);
        respond(['message'=>'Class deleted']);
    }

    err('Classes endpoint error',404);
}

// ══════════════════════════════════════════════════════════════
// ANALYTICS
// ══════════════════════════════════════════════════════════════
function handleAnalytics(string $method, ?string $sub, array $body): void {
    $auth = require_auth();
    require_role($auth,'teacher','admin');
    $sid = $auth['school_id'];

    $uid        = $auth['user_id'];
    $isTeacher  = ($auth['role'] === 'teacher');

    if ($sub === 'school') {
        // Optional filters
        $filterSubjId = isset($_GET['subject_id'])    ? (int)$_GET['subject_id']    : 0;
        $filterSem    = isset($_GET['semester'])       ? (int)$_GET['semester']      : 0;
        $filterAcYear = isset($_GET['academic_year'])  ? trim($_GET['academic_year']) : '';
        $subjF  = $filterSubjId ? 'AND t.subject_id = ?' : '';
        $subjP  = $filterSubjId ? [$filterSubjId] : [];
        $semF   = $filterSem    ? 'AND t.semester=?'      : '';
        $semP   = $filterSem    ? [$filterSem]    : [];
        $yearF  = $filterAcYear ? 'AND t.academic_year=?' : '';
        $yearP  = $filterAcYear ? [$filterAcYear] : [];
        $periodF = $semF . ' ' . $yearF;
        $periodP = array_merge($semP, $yearP);

        if ($isTeacher) {
            $tClassFilter = '(c.teacher_id=? OR EXISTS (SELECT 1 FROM class_teachers ct WHERE ct.class_id=c.id AND ct.teacher_id=?))';

            // Overview — test_count filtered by subject when selected
            $tcSql = $filterSubjId
                ? 'SELECT COUNT(DISTINCT id) FROM tests WHERE creator_id=? AND subject_id=?'
                : 'SELECT COUNT(DISTINCT id) FROM tests WHERE creator_id=?';
            $tcParams = $filterSubjId ? [$uid, $filterSubjId] : [$uid];
            $overview = DB::fetchOne(
                "SELECT COUNT(DISTINCT cs.student_id) AS student_count,
                        AVG(ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),2)) AS school_avg,
                        COUNT(DISTINCT c.id) AS class_count,
                        ($tcSql) AS test_count
                 FROM classes c
                 LEFT JOIN class_students cs ON cs.class_id=c.id
                 LEFT JOIN attempts a ON a.student_id=cs.student_id AND a.status IN ('submitted','marked')
                 WHERE c.school_id=? AND $tClassFilter",
                array_merge($tcParams, [$sid, $uid, $uid])
            );

            // Sub-strand breakdown
            $by_substrand = DB::fetchAll(
                "SELECT q.sub_strand, AVG(an.marks_awarded/q.marks*100) AS avg_pct
                 FROM answers an
                 JOIN questions q ON q.id=an.question_id
                 JOIN attempts  a ON a.id=an.attempt_id
                 JOIN tests     t ON t.id=a.test_id
                 JOIN class_students cs ON cs.student_id=a.student_id
                 JOIN classes c ON c.id=cs.class_id
                 WHERE c.school_id=? AND $tClassFilter AND an.marks_awarded IS NOT NULL $subjF $periodF
                 GROUP BY q.sub_strand ORDER BY avg_pct ASC",
                array_merge([$sid, $uid, $uid], $subjP, $periodP)
            );

            // At-risk
            $at_risk = DB::fetchAll(
                "SELECT DISTINCT u.id, u.first_name, u.last_name, u.class_name,
                        AVG(ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),2)) AS avg_pct
                 FROM users u
                 JOIN class_students cs ON cs.student_id=u.id
                 JOIN classes c ON c.id=cs.class_id
                 JOIN attempts a ON a.student_id=u.id
                 JOIN tests    t ON t.id=a.test_id
                 WHERE c.school_id=? AND $tClassFilter AND a.status IN ('submitted','marked') $subjF $periodF
                 GROUP BY u.id HAVING avg_pct<50 ORDER BY avg_pct ASC LIMIT 20",
                array_merge([$sid, $uid, $uid], $subjP, $periodP)
            );

            // Per-class performance
            $per_class = DB::fetchAll(
                "SELECT c.id AS class_id, c.name AS class_name, c.year_group,
                        COUNT(DISTINCT cs.student_id) AS student_count,
                        ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS class_avg,
                        SUM(IF(a.max_score>0 AND (a.score_auto+a.score_manual)/a.max_score>=0.5,1,0)) AS pass_count,
                        COUNT(DISTINCT a.id) AS attempts_done
                 FROM classes c
                 JOIN class_students cs ON cs.class_id=c.id
                 LEFT JOIN attempts a ON a.student_id=cs.student_id AND a.status IN ('submitted','marked')
                 LEFT JOIN tests t ON t.id=a.test_id
                 WHERE c.school_id=? AND ($tClassFilter) $subjF $periodF
                 GROUP BY c.id, c.name, c.year_group ORDER BY c.year_group, c.name",
                array_merge([$sid, $uid, $uid], $subjP, $periodP)
            );

            // Per-test stats
            $test_stats = DB::fetchAll(
                "SELECT t.id, t.title, t.type, t.semester, t.academic_year,
                        COUNT(DISTINCT a.student_id) AS attempts,
                        ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                        SUM(IF(a.max_score>0 AND (a.score_auto+a.score_manual)/a.max_score>=0.5,1,0)) AS pass_count
                 FROM tests t JOIN attempts a ON a.test_id=t.id AND a.status IN ('submitted','marked')
                 WHERE t.creator_id=? $subjF $periodF
                 GROUP BY t.id, t.title, t.type, t.semester, t.academic_year
                 ORDER BY t.created_at DESC LIMIT 15",
                array_merge([$uid], $subjP, $periodP)
            );

            // Per-subject performance summary
            $by_subject = DB::fetchAll(
                "SELECT s.id AS subject_id, s.name AS subject_name, s.short_name AS subject_short,
                        ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                        COUNT(DISTINCT a.student_id) AS student_count,
                        COUNT(DISTINCT t.id) AS test_count
                 FROM tests t JOIN attempts a ON a.test_id=t.id AND a.status IN ('submitted','marked')
                 JOIN subjects s ON s.id=t.subject_id
                 JOIN class_students cs ON cs.student_id=a.student_id
                 JOIN classes c ON c.id=cs.class_id
                 WHERE c.school_id=? AND ($tClassFilter) $periodF
                 GROUP BY s.id, s.name, s.short_name ORDER BY avg_pct DESC",
                array_merge([$sid, $uid, $uid], $periodP)
            );

        } else {
            // Admin: whole school
            $overview = DB::fetchOne(
                'SELECT COUNT(DISTINCT u.id) AS student_count,
                        (SELECT AVG(ROUND(IF(max_score>0,(score_auto+score_manual)/max_score*100,0),2))
                         FROM attempts a2 JOIN users u2 ON u2.id=a2.student_id
                         WHERE u2.school_id=? AND a2.status IN ("submitted","marked")) AS school_avg,
                        (SELECT COUNT(DISTINCT id) FROM classes WHERE school_id=?) AS class_count,
                        (SELECT COUNT(DISTINCT id) FROM tests WHERE school_id=?) AS test_count
                 FROM users u WHERE u.school_id=? AND u.role="student"',
                [$sid,$sid,$sid,$sid]
            );
            $by_substrand = DB::fetchAll(
                "SELECT q.sub_strand, AVG(an.marks_awarded/q.marks*100) AS avg_pct
                 FROM answers an JOIN questions q ON q.id=an.question_id
                 JOIN attempts a ON a.id=an.attempt_id
                 JOIN tests t ON t.id=a.test_id
                 JOIN users u ON u.id=a.student_id
                 WHERE u.school_id=? AND an.marks_awarded IS NOT NULL $subjF $periodF
                 GROUP BY q.sub_strand ORDER BY avg_pct ASC",
                array_merge([$sid], $subjP, $periodP)
            );
            $at_risk = DB::fetchAll(
                "SELECT u.id, u.first_name, u.last_name, u.class_name,
                        AVG(ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),2)) AS avg_pct
                 FROM users u JOIN attempts a ON a.student_id=u.id
                 JOIN tests t ON t.id=a.test_id
                 WHERE u.school_id=? AND a.status IN ('submitted','marked') $subjF $periodF
                 GROUP BY u.id HAVING avg_pct<50 ORDER BY avg_pct ASC LIMIT 20",
                array_merge([$sid], $subjP, $periodP)
            );

            // Per-class for admin
            $per_class = DB::fetchAll(
                "SELECT c.id AS class_id, c.name AS class_name, c.year_group,
                        COUNT(DISTINCT cs.student_id) AS student_count,
                        ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS class_avg,
                        SUM(IF(a.max_score>0 AND (a.score_auto+a.score_manual)/a.max_score>=0.5,1,0)) AS pass_count
                 FROM classes c
                 JOIN class_students cs ON cs.class_id=c.id
                 LEFT JOIN attempts a ON a.student_id=cs.student_id AND a.status IN ('submitted','marked')
                 LEFT JOIN tests t ON t.id=a.test_id
                 WHERE c.school_id=? $subjF $periodF
                 GROUP BY c.id, c.name, c.year_group ORDER BY c.year_group, c.name",
                array_merge([$sid], $subjP, $periodP)
            );

            // Per-subject
            $by_subject = DB::fetchAll(
                "SELECT s.id AS subject_id, s.name AS subject_name, s.short_name AS subject_short,
                        ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                        COUNT(DISTINCT a.student_id) AS student_count,
                        COUNT(DISTINCT t.id) AS test_count
                 FROM tests t JOIN attempts a ON a.test_id=t.id AND a.status IN ('submitted','marked')
                 JOIN subjects s ON s.id=t.subject_id
                 JOIN users u ON u.id=a.student_id
                 WHERE u.school_id=? $periodF
                 GROUP BY s.id, s.name, s.short_name ORDER BY avg_pct DESC",
                array_merge([$sid], $periodP)
            );

            $test_stats = [];
        }
        respond(compact('overview','by_substrand','at_risk','per_class','by_subject','test_stats'));
    }

    if ($sub === 'marking-queue') {
        ensureTeacherSubjectSchema();

        // Build scope filter: teacher sees tests for their subjects + own tests
        $scopeFilter = 'AND t.school_id=?';
        $scopeParams = [$sid];
        if ($isTeacher) {
            $subjectIds = array_column(DB::fetchAll(
                'SELECT subject_id FROM teacher_subjects WHERE teacher_id=?', [$uid]
            ), 'subject_id');
            if (!empty($subjectIds)) {
                $ph = implode(',', array_fill(0, count($subjectIds), '?'));
                $scopeFilter .= " AND (t.creator_id=? OR t.subject_id IN ($ph))";
                $scopeParams  = array_merge([$sid, $uid], $subjectIds);
            } else {
                $scopeFilter .= ' AND t.creator_id=?';
                $scopeParams[] = $uid;
            }
        }

        // Optional subject filter
        $mqSubjId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
        if ($mqSubjId) { $scopeFilter .= ' AND t.subject_id=?'; $scopeParams[] = $mqSubjId; }

        // Optional class filter — only include tests where at least one student from that class submitted
        $mqClassId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
        if ($mqClassId) {
            $scopeFilter .= ' AND EXISTS (SELECT 1 FROM class_students cs WHERE cs.class_id=? AND cs.student_id=a.student_id)';
            $scopeParams[] = $mqClassId;
        }

        // Return TEST-LEVEL summary (one row per test, not per answer)
        $queue = DB::fetchAll(
            "SELECT t.id AS test_id, t.title, t.type, t.subject_id,
                    s.name AS subject_name, s.short_name AS subject_short,
                    COUNT(DISTINCT a.student_id) AS students_attempted,
                    SUM(CASE WHEN q.type IN ('essay','short','fill-in','blank') AND an.is_correct IS NULL THEN 1 ELSE 0 END) AS essays_pending,
                    SUM(CASE WHEN q.type IN ('essay','short','fill-in','blank') THEN 1 ELSE 0 END) AS essays_total,
                    MIN(a.submitted_at) AS first_submission
             FROM tests t
             JOIN attempts a ON a.test_id=t.id AND a.status='submitted'
             JOIN answers an ON an.attempt_id=a.id
             JOIN questions q ON q.id=an.question_id AND q.type IN ('essay','short','fill-in','blank')
             LEFT JOIN subjects s ON s.id=t.subject_id
             WHERE 1=1 $scopeFilter
             GROUP BY t.id, t.title, t.type, t.subject_id, s.name, s.short_name
             HAVING essays_pending > 0
             ORDER BY first_submission ASC",
            $scopeParams
        );
        respond($queue);
    }

    // GET /analytics/teachers — admin: per-teacher performance stats
    if ($sub === 'teachers') {
        require_role($auth, 'admin');
        $filterSemT  = isset($_GET['semester'])      ? (int)$_GET['semester']    : 0;
        $filterYearT = isset($_GET['academic_year']) ? trim($_GET['academic_year']) : '';
        $semFT  = $filterSemT  ? 'AND t.semester=?'      : '';
        $semPT  = $filterSemT  ? [$filterSemT]  : [];
        $yearFT = $filterYearT ? 'AND t.academic_year=?' : '';
        $yearPT = $filterYearT ? [$filterYearT] : [];
        $pFT = $semFT . ' ' . $yearFT;
        $pPT = array_merge($semPT, $yearPT);

        $teachers = DB::fetchAll(
            "SELECT u.id AS teacher_id,
                    CONCAT(u.first_name,' ',u.last_name) AS teacher_name,
                    u.avatar_color, u.department_id, d.name AS department_name,
                    COUNT(DISTINCT t.id) AS test_count,
                    COUNT(DISTINCT a.student_id) AS students_tested,
                    ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                    SUM(IF(a.max_score>0 AND (a.score_auto+a.score_manual)/a.max_score>=0.5,1,0)) AS pass_count,
                    COUNT(DISTINCT a.id) AS total_attempts,
                    COUNT(DISTINCT tq.question_id) AS question_count
             FROM users u
             LEFT JOIN departments d ON d.id=u.department_id
             LEFT JOIN tests t ON t.creator_id=u.id AND t.school_id=? $pFT
             LEFT JOIN attempts a ON a.test_id=t.id AND a.status IN ('submitted','marked')
             LEFT JOIN test_questions tq ON tq.test_id=t.id
             WHERE u.school_id=? AND u.role='teacher' AND u.is_active=1
             GROUP BY u.id, u.first_name, u.last_name, u.avatar_color, u.department_id, d.name
             ORDER BY avg_pct DESC, test_count DESC",
            array_merge([$sid], $pPT, [$sid])
        );

        // School-wide averages for comparison
        $schoolAvg = DB::fetchOne(
            "SELECT ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                    SUM(IF(a.max_score>0 AND (a.score_auto+a.score_manual)/a.max_score>=0.5,1,0)) AS pass_count,
                    COUNT(DISTINCT a.id) AS total_attempts
             FROM attempts a JOIN tests t ON t.id=a.test_id
             JOIN users u ON u.id=a.student_id
             WHERE u.school_id=? AND a.status IN ('submitted','marked') $pFT",
            array_merge([$sid], $pPT)
        );

        // Per-subject breakdown
        $bySubject = DB::fetchAll(
            "SELECT s.name AS subject_name, s.short_name AS subject_short,
                    COUNT(DISTINCT t.id) AS test_count,
                    ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                    COUNT(DISTINCT a.student_id) AS students_tested
             FROM tests t JOIN subjects s ON s.id=t.subject_id
             LEFT JOIN attempts a ON a.test_id=t.id AND a.status IN ('submitted','marked')
             WHERE t.school_id=? $pFT
             GROUP BY s.id, s.name, s.short_name ORDER BY avg_pct DESC",
            array_merge([$sid], $pPT)
        );

        // Semester trend
        $semTrend = DB::fetchAll(
            "SELECT t.academic_year, t.semester,
                    COUNT(DISTINCT a.id) AS total_attempts,
                    ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                    SUM(IF(a.max_score>0 AND (a.score_auto+a.score_manual)/a.max_score>=0.5,1,0)) AS pass_count
             FROM attempts a JOIN tests t ON t.id=a.test_id
             JOIN users u ON u.id=a.student_id
             WHERE u.school_id=? AND a.status IN ('submitted','marked') AND t.academic_year IS NOT NULL
             GROUP BY t.academic_year, t.semester ORDER BY t.academic_year DESC, t.semester DESC LIMIT 6",
            [$sid]
        );

        respond(compact('teachers', 'schoolAvg', 'bySubject', 'semTrend'));
    }

    // GET /analytics/matrix — admin: subject × class performance pivot (heat map)
    if ($sub === 'matrix') {
        require_role($auth, 'admin');
        $filterSemM  = isset($_GET['semester'])      ? (int)$_GET['semester']    : 0;
        $filterYearM = isset($_GET['academic_year']) ? trim($_GET['academic_year']) : '';
        $filterSubjM = isset($_GET['subject_id'])    ? (int)$_GET['subject_id']  : 0;
        $semFM  = $filterSemM  ? 'AND t.semester=?'      : '';
        $semPM  = $filterSemM  ? [$filterSemM]  : [];
        $yearFM = $filterYearM ? 'AND t.academic_year=?' : '';
        $yearPM = $filterYearM ? [$filterYearM] : [];
        $subjFM = $filterSubjM ? 'AND s.id=?'           : '';
        $subjPM = $filterSubjM ? [$filterSubjM] : [];
        $pFM    = "$semFM $yearFM $subjFM";
        $pPM    = array_merge($semPM, $yearPM, $subjPM);

        $matrix = DB::fetchAll(
            "SELECT s.id AS subject_id, s.name AS subject_name,
                    COALESCE(s.short_name, LEFT(s.name,10)) AS subject_short,
                    c.id AS class_id, c.name AS class_name, c.year_group,
                    ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                    COUNT(DISTINCT t.id) AS test_count,
                    COUNT(DISTINCT a.student_id) AS student_count
             FROM tests t
             JOIN subjects s ON s.id=t.subject_id
             JOIN attempts a ON a.test_id=t.id AND a.status IN ('submitted','marked')
             JOIN class_students cs ON cs.student_id=a.student_id
             JOIN classes c ON c.id=cs.class_id AND c.school_id=?
             WHERE t.school_id=? $pFM
             GROUP BY s.id, s.name, s.short_name, c.id, c.name, c.year_group
             ORDER BY s.name, c.year_group, c.name",
            array_merge([$sid, $sid], $pPM)
        );
        respond(['matrix' => $matrix]);
    }

    // GET /analytics/alerts — pending action alerts for dashboard badge
    // GET /analytics/gradebook?class_id=N&subject_id=N&academic_year=&semester=N
    // GET /analytics/leaderboard?class_id=N&academic_year=&semester=N&limit=20
    if ($sub === 'leaderboard') {
        $classId = (int)($_GET['class_id']     ?? 0);
        $acYear  = trim($_GET['academic_year'] ?? '');
        $semNum  = (int)($_GET['semester']     ?? 0);
        $limit   = max(5, min(50, (int)($_GET['limit'] ?? 20)));

        $classWhere = $classId ? 'AND cs.class_id=?' : '';
        $classParam = $classId ? [$classId] : [];
        $acWhere    = $acYear  ? 'AND t.academic_year=?' : '';
        $semWhere   = $semNum  ? 'AND t.semester=?'      : '';
        $pPeriod    = array_merge($acYear?[$acYear]:[], $semNum?[$semNum]:[]);

        // Top students by average score
        $top = DB::fetchAll(
            "SELECT u.id AS student_id, u.first_name, u.last_name, u.avatar_color,
                    c.name AS class_name, c.year_group,
                    ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                    COUNT(DISTINCT a.id) AS test_count,
                    SUM(IF(a.max_score>0 AND (a.score_auto+a.score_manual)/a.max_score>=0.5,1,0)) AS pass_count
             FROM users u
             JOIN class_students cs ON cs.student_id=u.id
             JOIN classes c ON c.id=cs.class_id AND c.school_id=?
             JOIN attempts a ON a.student_id=u.id AND a.status IN ('submitted','marked')
             JOIN tests t ON t.id=a.test_id $acWhere $semWhere
             WHERE u.school_id=? $classWhere
             GROUP BY u.id, u.first_name, u.last_name, u.avatar_color, c.name, c.year_group
             HAVING test_count >= 2
             ORDER BY avg_pct DESC
             LIMIT ?",
            array_merge([$sid], $pPeriod, [$sid], $classParam, [$limit])
        );

        // Most improved (best avg in last 30 days vs overall)
        $improved = DB::fetchAll(
            "SELECT * FROM (
                SELECT u.id AS student_id, u.first_name, u.last_name, u.avatar_color,
                       c.name AS class_name,
                       ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS overall_avg,
                       ROUND(AVG(IF(a.submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND a.max_score>0,
                           (a.score_auto+a.score_manual)/a.max_score*100, NULL)),1) AS recent_avg,
                       SUM(a.submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS recent_count
                FROM users u
                JOIN class_students cs ON cs.student_id=u.id
                JOIN classes c ON c.id=cs.class_id AND c.school_id=?
                JOIN attempts a ON a.student_id=u.id AND a.status IN ('submitted','marked')
                WHERE u.school_id=? $classWhere
                GROUP BY u.id, u.first_name, u.last_name, u.avatar_color, c.name
             ) sub
             WHERE sub.recent_count >= 1 AND sub.recent_avg IS NOT NULL
               AND sub.recent_avg > sub.overall_avg
             ORDER BY (sub.recent_avg - sub.overall_avg) DESC
             LIMIT 10",
            array_merge([$sid, $sid], $classParam)
        );

        // Top streaks
        $streaks = DB::fetchAll(
            "SELECT u.id AS student_id, u.first_name, u.last_name, u.avatar_color,
                    c.name AS class_name, str.current_streak, str.longest_streak, str.total_xp
             FROM streaks str
             JOIN users u ON u.id=str.student_id
             JOIN class_students cs ON cs.student_id=u.id
             JOIN classes c ON c.id=cs.class_id AND c.school_id=?
             WHERE u.school_id=? $classWhere AND str.current_streak > 0
             ORDER BY str.current_streak DESC
             LIMIT 10",
            array_merge([$sid, $sid], $classParam)
        );

        respond(['top_students'=>$top, 'most_improved'=>$improved, 'top_streaks'=>$streaks]);
    }

    if ($sub === 'gradebook') {
        require_role($auth, 'teacher', 'admin');
        $classId = (int)($_GET['class_id']     ?? 0);
        $subjId  = (int)($_GET['subject_id']   ?? 0);
        $acYear  = trim($_GET['academic_year'] ?? '');
        $semNum  = (int)($_GET['semester']     ?? 0);
        if (!$classId) err('class_id required');

        $cls = DB::fetchOne('SELECT id, name FROM classes WHERE id=? AND school_id=?', [$classId, $sid]);
        if (!$cls) err('Class not found', 404);

        // Students in class
        $students = DB::fetchAll(
            'SELECT u.id, u.first_name, u.last_name, u.avatar_color
             FROM users u JOIN class_students cs ON cs.student_id=u.id
             WHERE cs.class_id=? AND u.is_active=1
             ORDER BY u.last_name, u.first_name',
            [$classId]
        );

        // Published tests assigned to this class
        $subjWhere = $subjId ? 'AND t.subject_id=?' : '';
        $acWhere   = $acYear  ? 'AND t.academic_year=?' : '';
        $semWhere  = $semNum  ? 'AND t.semester=?'      : '';
        $tParams   = array_merge([$classId], $subjId?[$subjId]:[], $acYear?[$acYear]:[], $semNum?[$semNum]:[]);

        $tests = DB::fetchAll(
            "SELECT t.id, t.title, t.subject_id, t.semester, t.academic_year,
                    s.short_name AS subject_short, s.name AS subject_name,
                    (SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) AS question_count
             FROM tests t
             JOIN test_assignments ta ON ta.test_id=t.id AND ta.class_id=?
             LEFT JOIN subjects s ON s.id=t.subject_id
             WHERE t.status='published' $subjWhere $acWhere $semWhere
             ORDER BY t.created_at ASC",
            $tParams
        );

        if (empty($students) || empty($tests)) {
            respond(['class'=>$cls,'students'=>$students,'tests'=>$tests,'attempts'=>[],'class_avg'=>[],'student_avg'=>[]]);
        }

        $stuIds  = array_column($students, 'id');
        $testIds = array_column($tests,    'id');
        $stuPh   = implode(',', array_fill(0, count($stuIds),  '?'));
        $tstPh   = implode(',', array_fill(0, count($testIds), '?'));

        // Best attempt per student per test
        $attRows = DB::fetchAll(
            "SELECT a.student_id, a.test_id, a.id AS attempt_id,
                    ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),1) AS pct_score,
                    a.status, a.submitted_at
             FROM attempts a
             WHERE a.student_id IN ($stuPh) AND a.test_id IN ($tstPh)
               AND a.status IN ('submitted','marked')
             ORDER BY a.submitted_at DESC",
            array_merge($stuIds, $testIds)
        );

        // Keep only best attempt per (student, test)
        $best = [];
        foreach ($attRows as $a) {
            $k = $a['student_id'] . '_' . $a['test_id'];
            if (!isset($best[$k]) || (float)$a['pct_score'] > (float)$best[$k]['pct_score']) $best[$k] = $a;
        }

        // Restructure: student_id → test_id → attempt
        $attempts = [];
        foreach ($best as $a) { $attempts[$a['student_id']][$a['test_id']] = $a; }

        // Class average per test
        $class_avg = [];
        foreach ($tests as $t) {
            $scores = [];
            foreach ($students as $s) { $att = $attempts[$s['id']][$t['id']] ?? null; if ($att) $scores[] = (float)$att['pct_score']; }
            $class_avg[$t['id']] = $scores ? round(array_sum($scores)/count($scores), 1) : null;
        }

        // Student average across all tests
        $student_avg = [];
        foreach ($students as $s) {
            $scores = [];
            foreach ($tests as $t) { $att = $attempts[$s['id']][$t['id']] ?? null; if ($att) $scores[] = (float)$att['pct_score']; }
            $student_avg[$s['id']] = $scores ? round(array_sum($scores)/count($scores), 1) : null;
        }

        respond(['class'=>$cls,'students'=>$students,'tests'=>$tests,'attempts'=>$attempts,'class_avg'=>$class_avg,'student_avg'=>$student_avg]);
    }

    if ($sub === 'alerts') {
        $alerts = [];
        if ($role === 'teacher') {
            $unmarked = (int)(DB::fetchOne(
                "SELECT COUNT(*) AS n FROM answers an
                 JOIN questions q ON q.id=an.question_id
                 JOIN attempts a ON a.id=an.attempt_id
                 JOIN tests t ON t.id=a.test_id
                 WHERE t.creator_id=? AND q.type IN ('essay','short') AND an.is_correct IS NULL
                   AND a.status='submitted'",
                [(int)$auth['user_id']]
            )['n'] ?? 0);
            if ($unmarked) $alerts[] = ['type'=>'warning','icon'=>'✏️',
                'title'=>$unmarked.' essay'.($unmarked>1?'s':'').' to mark',
                'desc'=>'Students are waiting for their marks','action'=>'marking'];

            ensureMeetingsSchema();
            $soonMtg = (int)(DB::fetchOne(
                "SELECT COUNT(*) AS n FROM meetings WHERE teacher_id=? AND status IN ('scheduled','live')
                 AND meeting_date=CURDATE() AND start_time >= SUBTIME(NOW(),SEC_TO_TIME(1800))",
                [(int)$auth['user_id']]
            )['n'] ?? 0);
            if ($soonMtg) $alerts[] = ['type'=>'info','icon'=>'📅',
                'title'=>$soonMtg.' meeting'.($soonMtg>1?'s':'').' today',
                'desc'=>'Check your meetings schedule','action'=>'meetings'];
        }
        if ($role === 'admin') {
            $unmarkedAll = (int)(DB::fetchOne(
                "SELECT COUNT(*) AS n FROM answers an
                 JOIN questions q ON q.id=an.question_id
                 JOIN attempts a ON a.id=an.attempt_id
                 JOIN tests t ON t.id=a.test_id JOIN users u ON u.id=t.creator_id
                 WHERE u.school_id=? AND q.type IN ('essay','short') AND an.is_correct IS NULL
                   AND a.status='submitted'",
                [$sid]
            )['n'] ?? 0);
            if ($unmarkedAll) $alerts[] = ['type'=>'warning','icon'=>'✏️',
                'title'=>$unmarkedAll.' essays pending',
                'desc'=>'School-wide unmarked essays','action'=>'marking'];

            $atRiskCount = (int)(DB::fetchOne(
                "SELECT COUNT(DISTINCT a.student_id) AS n FROM attempts a JOIN tests t ON t.id=a.test_id
                 JOIN users u ON u.id=a.student_id WHERE u.school_id=?
                 AND a.status IN ('submitted','marked')
                 GROUP BY u.school_id
                 HAVING ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),0) < 45",
                [$sid]
            )['n'] ?? 0);
            if ($atRiskCount) $alerts[] = ['type'=>'error','icon'=>'🚨',
                'title'=>'At-risk students detected',
                'desc'=>'Run the at-risk analysis for details','action'=>'at-risk'];
        }
        respond(['alerts'=>$alerts,'count'=>count($alerts)]);
    }

    // GET /analytics/wassce — school-wide WASSCE readiness prediction
    if ($sub === 'wassce') {
        require_role($auth, 'teacher', 'admin');
        ensureGradeConfigSchema();
        $classId    = (int)($_GET['class_id']      ?? 0);
        $acYear     = trim($_GET['academic_year']  ?? '');
        $semNum     = (int)($_GET['semester']      ?? 0);
        $classWhere = $classId ? 'AND cs.class_id=?' : '';
        $classParam = $classId ? [$classId] : [];
        $acWhere    = $acYear  ? 'AND t.academic_year=?' : '';
        $semWhere   = $semNum  ? 'AND t.semester=?'      : '';
        $pPeriod    = array_merge($acYear ? [$acYear] : [], $semNum ? [$semNum] : []);

        $rows = DB::fetchAll(
            "SELECT u.id AS student_id, u.first_name, u.last_name, u.avatar_color,
                    c.id AS class_id, c.name AS class_name, c.year_group,
                    s.id AS subject_id, s.name AS subject_name, s.short_name AS subject_short,
                    ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                    COUNT(DISTINCT a.id) AS test_count
             FROM users u
             JOIN class_students cs ON cs.student_id=u.id
             JOIN classes c ON c.id=cs.class_id AND c.school_id=?
             JOIN attempts a ON a.student_id=u.id AND a.status IN ('submitted','marked')
             JOIN tests t ON t.id=a.test_id AND t.subject_id IS NOT NULL $acWhere $semWhere
             JOIN subjects s ON s.id=t.subject_id
             WHERE u.school_id=? $classWhere
             GROUP BY u.id, u.first_name, u.last_name, u.avatar_color,
                      c.id, c.name, c.year_group, s.id, s.name, s.short_name
             ORDER BY c.year_group, c.name, u.last_name, u.first_name, s.name",
            array_merge([$sid], $pPeriod, [$sid], $classParam)
        );

        $gradeConf = DB::fetchAll('SELECT * FROM grade_config WHERE school_id=? ORDER BY sort_order', [$sid]);
        if (empty($gradeConf)) {
            $gradeConf=[['grade'=>'A1','min_pct'=>80,'max_pct'=>100],['grade'=>'B2','min_pct'=>70,'max_pct'=>79],
                ['grade'=>'B3','min_pct'=>60,'max_pct'=>69],['grade'=>'C4','min_pct'=>55,'max_pct'=>59],
                ['grade'=>'C5','min_pct'=>50,'max_pct'=>54],['grade'=>'C6','min_pct'=>45,'max_pct'=>49],
                ['grade'=>'D7','min_pct'=>40,'max_pct'=>44],['grade'=>'E8','min_pct'=>35,'max_pct'=>39],
                ['grade'=>'F9','min_pct'=>0,'max_pct'=>34]];
        }
        $gradeOf = function(float $pct) use ($gradeConf): string {
            foreach ($gradeConf as $g) { if ($pct>=(float)$g['min_pct']&&$pct<=(float)$g['max_pct']) return $g['grade']; }
            return 'F9';
        };
        $isCredit = fn(string $g) => in_array($g, ['A1','B2','B3','C4','C5','C6']);

        $students = []; $subjects = [];
        foreach ($rows as $r) {
            $sKey = $r['student_id'];
            $subKey = $r['subject_id'];
            if (!isset($students[$sKey])) {
                $students[$sKey] = ['student_id'=>$sKey,'first_name'=>$r['first_name'],'last_name'=>$r['last_name'],
                    'avatar_color'=>$r['avatar_color'],'class_id'=>$r['class_id'],'class_name'=>$r['class_name'],
                    'year_group'=>$r['year_group'],'subjects'=>[],'credit_count'=>0,'subject_count'=>0];
            }
            if (!isset($subjects[$subKey])) {
                $subjects[$subKey] = ['id'=>$subKey,'name'=>$r['subject_name'],'short'=>$r['subject_short']];
            }
            $pct = (float)$r['avg_pct']; $grade = $gradeOf($pct);
            $students[$sKey]['subjects'][$subKey] = ['avg_pct'=>$pct,'grade'=>$grade,'is_credit'=>$isCredit($grade),'test_count'=>(int)$r['test_count']];
            $students[$sKey]['subject_count']++;
            if ($isCredit($grade)) $students[$sKey]['credit_count']++;
        }
        foreach ($students as &$s) {
            $s['wassce_readiness'] = $s['subject_count'] > 0 ? round($s['credit_count'] / $s['subject_count'] * 100) : 0;
        }
        $stuArr = array_values($students);
        respond([
            'students'     => $stuArr,
            'subjects'     => array_values($subjects),
            'grade_config' => $gradeConf,
            'summary'      => [
                'ready'      => count(array_filter($stuArr, fn($s)=>$s['wassce_readiness']>=80)),
                'borderline' => count(array_filter($stuArr, fn($s)=>$s['wassce_readiness']>=50&&$s['wassce_readiness']<80)),
                'at_risk'    => count(array_filter($stuArr, fn($s)=>$s['wassce_readiness']<50)),
                'total'      => count($stuArr),
            ],
        ]);
    }

    // GET /analytics/badges — student achievement badges
    if ($sub === 'badges') {
        $studentId = ($auth['role'] === 'student') ? (int)$auth['user_id'] : (int)($_GET['student_id'] ?? $auth['user_id']);
        $attempts  = DB::fetchAll(
            "SELECT a.id, a.submitted_at, a.started_at,
                    ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),1) AS pct_score,
                    TIMESTAMPDIFF(MINUTE,a.started_at,a.submitted_at) AS duration_min
             FROM attempts a WHERE a.student_id=? AND a.status IN ('submitted','marked')
             ORDER BY a.submitted_at ASC",
            [$studentId]
        );
        $streak = DB::fetchOne('SELECT current_streak,longest_streak FROM streaks WHERE student_id=?', [$studentId]);
        $curStreak = (int)($streak['current_streak'] ?? 0);
        $maxStreak = max($curStreak, (int)($streak['longest_streak'] ?? 0));
        $cnt = count($attempts);
        $badges = [];
        if ($cnt>=1)  $badges[]=['id'=>'pioneer', 'icon'=>'🌟','name'=>'Pioneer',      'desc'=>'Completed your first test'];
        if ($cnt>=10) $badges[]=['id'=>'scholar',  'icon'=>'📚','name'=>'Scholar',      'desc'=>'Completed 10 tests'];
        if ($cnt>=25) $badges[]=['id'=>'veteran',  'icon'=>'🎓','name'=>'Veteran',      'desc'=>'Completed 25 tests'];
        $perfects = array_filter($attempts, fn($a)=>(float)$a['pct_score']>=99);
        if (count($perfects)>=1) $badges[]=['id'=>'perfect','icon'=>'💯','name'=>'Perfect Score','desc'=>'Scored 100% on a test'];
        $high = array_filter($attempts, fn($a)=>(float)$a['pct_score']>=90);
        if (count($high)>=3) $badges[]=['id'=>'highachiver','icon'=>'🎯','name'=>'High Achiever','desc'=>'90%+ on 3 or more tests'];
        $passes = array_filter($attempts, fn($a)=>(float)$a['pct_score']>=50);
        if (count($passes) === $cnt && $cnt >= 5) $badges[]=['id'=>'consistent','icon'=>'✅','name'=>'Consistent','desc'=>'Passed every test'];
        if ($maxStreak>=7)   $badges[]=['id'=>'streak7',  'icon'=>'🔥','name'=>'Week Warrior', 'desc'=>'7-day study streak'];
        if ($maxStreak>=30)  $badges[]=['id'=>'streak30', 'icon'=>'🔥🔥','name'=>'Dedicated',   'desc'=>'30-day study streak'];
        if ($maxStreak>=100) $badges[]=['id'=>'streak100','icon'=>'👑','name'=>'Legend',       'desc'=>'100-day study streak'];
        if ($cnt>=3) {
            $last3 = array_slice($attempts, -3);
            if ((float)$last3[2]['pct_score'] > (float)$last3[0]['pct_score'] + 10) {
                $badges[]=['id'=>'rising','icon'=>'📈','name'=>'Rising Star','desc'=>'Scores improving in recent tests'];
            }
        }
        respond(['badges'=>$badges,'total'=>count($badges),'attempt_count'=>$cnt,'streak'=>$maxStreak]);
    }

    // GET /analytics/question-stats — admin question bank overview
    if ($sub === 'question-stats') {
        require_role($auth, 'admin');
        $schoolId = $auth['school_id'];

        $total = DB::fetchOne(
            'SELECT COUNT(*) AS n FROM questions WHERE (school_id IS NULL OR school_id=?) AND is_active=1',
            [$schoolId]
        )['n'] ?? 0;

        $by_subject = DB::fetchAll(
            "SELECT COALESCE(s.name,'— No subject') AS subject_name,
                    s.short_name AS subject_short, COUNT(*) AS question_count
             FROM questions q
             LEFT JOIN subjects s ON s.id=q.subject_id
             WHERE (q.school_id IS NULL OR q.school_id=?) AND q.is_active=1
             GROUP BY q.subject_id, s.name, s.short_name
             ORDER BY question_count DESC",
            [$schoolId]
        );

        $by_teacher = DB::fetchAll(
            "SELECT CONCAT(u.first_name,' ',u.last_name) AS teacher_name,
                    u.id AS teacher_id, u.avatar_color,
                    COUNT(*) AS question_count
             FROM questions q
             JOIN users u ON u.id=q.author_id
             WHERE (q.school_id IS NULL OR q.school_id=?) AND q.is_active=1
             GROUP BY q.author_id, u.first_name, u.last_name, u.avatar_color
             ORDER BY question_count DESC LIMIT 20",
            [$schoolId]
        );

        $by_difficulty = DB::fetchAll(
            "SELECT COALESCE(difficulty,'—') AS difficulty, COUNT(*) AS question_count
             FROM questions
             WHERE (school_id IS NULL OR school_id=?) AND is_active=1
             GROUP BY difficulty ORDER BY FIELD(difficulty,'Easy','Medium','Hard')",
            [$schoolId]
        );

        $by_type = DB::fetchAll(
            "SELECT type, COUNT(*) AS question_count
             FROM questions
             WHERE (school_id IS NULL OR school_id=?) AND is_active=1
             GROUP BY type ORDER BY question_count DESC",
            [$schoolId]
        );

        respond(compact('total','by_subject','by_teacher','by_difficulty','by_type'));
    }

    err('Analytics endpoint error',404);
}

// ══════════════════════════════════════════════════════════════
// STUDENT PROMOTION
// ══════════════════════════════════════════════════════════════
function ensurePromotionSchema(): void {
    DB::execute("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_graduated TINYINT(1) NOT NULL DEFAULT 0");
    DB::execute("ALTER TABLE users ADD COLUMN IF NOT EXISTS graduation_year VARCHAR(10) NULL");
    DB::execute("CREATE TABLE IF NOT EXISTS promotions (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        school_id     INT UNSIGNED NOT NULL,
        academic_year VARCHAR(20)  NOT NULL,
        student_id    INT UNSIGNED NOT NULL,
        from_class_id INT UNSIGNED NULL,
        to_class_id   INT UNSIGNED NULL,
        action        ENUM('promoted','repeated','transferred','graduated') NOT NULL,
        promoted_by   INT UNSIGNED NOT NULL,
        promoted_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes         TEXT NULL,
        INDEX idx_student (student_id),
        INDEX idx_school  (school_id)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    DB::execute("CREATE TABLE IF NOT EXISTS student_enrollments (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        student_id    INT UNSIGNED NOT NULL,
        school_id     INT UNSIGNED NOT NULL,
        class_id      INT UNSIGNED NOT NULL,
        class_name    VARCHAR(200) NOT NULL,
        academic_year VARCHAR(20)  NOT NULL,
        status        ENUM('active','promoted','repeated','transferred','graduated','withdrawn') DEFAULT 'active',
        enrolled_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ended_at      TIMESTAMP NULL,
        notes         TEXT NULL,
        INDEX idx_student (student_id),
        INDEX idx_class   (class_id),
        UNIQUE KEY uniq_enrol (student_id, class_id, academic_year)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}

function findNextClass(string $name, int $yearGroup, int $schoolId): ?array {
    // Strip leading digit(s) to get the stream suffix: "1 Science 1" → "Science 1"
    $suffix   = trim(preg_replace('/^\d+\s*/', '', $name));
    $nextYear = $yearGroup + 1;
    $nextName = "$nextYear $suffix";
    // Exact match first
    $cls = DB::fetchOne('SELECT * FROM classes WHERE school_id=? AND name=?', [$schoolId, $nextName]);
    if ($cls) return $cls;
    // Fuzzy: same year_group, name contains the suffix
    return DB::fetchOne(
        'SELECT * FROM classes WHERE school_id=? AND year_group=? AND name LIKE ?',
        [$schoolId, $nextYear, "%$suffix"]
    );
}

function handlePromotion(string $method, ?string $sub, array $body): void {
    $auth = require_auth();
    require_role($auth, 'admin');
    ensurePromotionSchema();
    $sid = $auth['school_id'];
    $uid = (int)$auth['user_id'];

    // GET /promotion/classes
    if ($method === 'GET' && $sub === 'classes') {
        respond(DB::fetchAll(
            "SELECT c.id, c.name, c.year_group,
                    COUNT(DISTINCT cs.student_id) AS student_count
             FROM classes c
             LEFT JOIN class_students cs ON cs.class_id=c.id
             WHERE c.school_id=?
             GROUP BY c.id, c.name, c.year_group
             ORDER BY c.year_group, c.name",
            [$sid]
        ));
    }

    // GET /promotion/students?class_id=X
    if ($method === 'GET' && $sub === 'students') {
        $cid = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
        if (!$cid) err('class_id required');
        respond(DB::fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.class_name, u.avatar_color,
                    u.is_graduated, u.graduation_year, c.year_group,
                    (SELECT COUNT(*) FROM attempts WHERE student_id=u.id AND status IN ('submitted','marked')) AS tests_done,
                    (SELECT ROUND(AVG(IF(max_score>0,(score_auto+score_manual)/max_score*100,0)),1)
                     FROM attempts WHERE student_id=u.id AND status IN ('submitted','marked')) AS avg_pct
             FROM users u
             JOIN class_students cs ON cs.student_id=u.id
             JOIN classes c ON c.id=cs.class_id
             WHERE cs.class_id=? AND u.is_active=1 AND u.school_id=?
             ORDER BY u.last_name, u.first_name",
            [$cid, $sid]
        ));
    }

    // POST /promotion/execute
    if ($method === 'POST' && $sub === 'execute') {
        $classId      = (int)($body['class_id']     ?? 0);
        $action       = trim($body['action']         ?? '');
        $studentIds   = $body['student_ids']         ?? null;
        $toClassId    = isset($body['to_class_id'])  ? (int)$body['to_class_id'] : null;
        $academicYear = trim($body['academic_year']  ?? (date('Y').'/'.((int)date('Y')+1)));
        $notes        = trim($body['notes']          ?? '');

        if (!$classId || !$action) err('class_id and action required');
        if (!in_array($action, ['promoted','repeated','transferred','graduated']))
            err('Invalid action');

        $class = DB::fetchOne('SELECT * FROM classes WHERE id=? AND school_id=?', [$classId, $sid]);
        if (!$class) err('Class not found', 404);

        // Build student list
        $q = "SELECT u.id, u.first_name, u.last_name, u.class_name
              FROM users u
              JOIN class_students cs ON cs.student_id=u.id
              WHERE cs.class_id=? AND u.is_active=1 AND u.school_id=?";
        $p = [$classId, $sid];
        if ($studentIds && is_array($studentIds) && count($studentIds)) {
            $ph = implode(',', array_fill(0, count($studentIds), '?'));
            $q .= " AND u.id IN ($ph)";
            $p  = array_merge($p, array_map('intval', $studentIds));
        }
        $students = DB::fetchAll($q, $p);
        if (empty($students)) err('No matching students found', 404);

        // Resolve target class
        $targetClassId   = null;
        $targetClassName = null;

        if ($action === 'promoted') {
            if ((int)$class['year_group'] >= 3) {
                $action = 'graduated'; // auto-graduate Year 3
            } else {
                $next = findNextClass($class['name'], (int)$class['year_group'], $sid);
                if (!$next) err(
                    "No Year ".((int)$class['year_group']+1)." class found matching '{$class['name']}'. ".
                    "Create the next-year classes first.", 422
                );
                $targetClassId   = (int)$next['id'];
                $targetClassName = $next['name'];
            }
        } elseif ($action === 'transferred') {
            if (!$toClassId) err('to_class_id required for transfer');
            $tc = DB::fetchOne('SELECT * FROM classes WHERE id=? AND school_id=?', [$toClassId, $sid]);
            if (!$tc) err('Target class not found', 404);
            $targetClassId   = $toClassId;
            $targetClassName = $tc['name'];
        } elseif ($action === 'repeated') {
            $targetClassId   = $classId;
            $targetClassName = $class['name'];
        }

        $count = 0;
        foreach ($students as $s) {
            $sId = (int)$s['id'];

            // 1. Close existing active enrollment
            DB::execute(
                "UPDATE student_enrollments SET status=?, ended_at=NOW()
                 WHERE student_id=? AND class_id=? AND status='active'",
                [$action, $sId, $classId]
            );

            // 2. Immutable audit log
            DB::execute(
                'INSERT INTO promotions (school_id,academic_year,student_id,from_class_id,to_class_id,action,promoted_by,notes)
                 VALUES (?,?,?,?,?,?,?,?)',
                [$sid, $academicYear, $sId, $classId, $targetClassId, $action, $uid, $notes]
            );

            if ($action === 'graduated') {
                DB::execute(
                    'UPDATE users SET is_graduated=1, graduation_year=? WHERE id=?',
                    [$academicYear, $sId]
                );
                // Remove from active roster (stays in DB for transcripts)
                DB::execute('DELETE FROM class_students WHERE student_id=?', [$sId]);

            } elseif ($targetClassId !== null && $targetClassId !== $classId) {
                // Move to new class
                DB::execute('DELETE FROM class_students WHERE student_id=? AND class_id=?', [$sId, $classId]);
                DB::execute('INSERT IGNORE INTO class_students (class_id,student_id) VALUES (?,?)', [$targetClassId, $sId]);
                DB::execute('UPDATE users SET class_name=? WHERE id=?', [$targetClassName, $sId]);
                // New enrollment record
                DB::execute(
                    'INSERT IGNORE INTO student_enrollments (student_id,school_id,class_id,class_name,academic_year,status)
                     VALUES (?,?,?,?,?,?)',
                    [$sId, $sid, $targetClassId, $targetClassName, $academicYear, 'active']
                );
            } else {
                // Repeated — stays in same class, new year enrollment record
                DB::execute(
                    'INSERT IGNORE INTO student_enrollments (student_id,school_id,class_id,class_name,academic_year,status)
                     VALUES (?,?,?,?,?,?)',
                    [$sId, $sid, $classId, $class['name'], $academicYear, 'active']
                );
            }
            $count++;
        }

        respond([
            'message'       => "$count student".($count!==1?'s':'')." $action successfully.",
            'count'         => $count,
            'action'        => $action,
            'academic_year' => $academicYear,
        ]);
    }

    // GET /promotion/history?student_id=X — full promotion history for a student
    if ($method === 'GET' && $sub === 'history') {
        $studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
        if (!$studentId) err('student_id required');
        respond(DB::fetchAll(
            "SELECT p.id, p.academic_year, p.action, p.promoted_at, p.notes,
                    fc.name AS from_class, fc.year_group AS from_year,
                    tc.name AS to_class,   tc.year_group AS to_year,
                    CONCAT(u.first_name,' ',u.last_name) AS done_by
             FROM promotions p
             LEFT JOIN classes fc ON fc.id=p.from_class_id
             LEFT JOIN classes tc ON tc.id=p.to_class_id
             LEFT JOIN users   u  ON u.id =p.promoted_by
             WHERE p.student_id=? AND p.school_id=?
             ORDER BY p.promoted_at DESC",
            [$studentId, $sid]
        ));
    }

    err('Promotion endpoint error', 404);
}

// ══════════════════════════════════════════════════════════════
// GROUP CHAT
// ══════════════════════════════════════════════════════════════
function ensureChatSchema(): void {
    static $done = false; if ($done) return; $done = true;
    DB::execute("CREATE TABLE IF NOT EXISTS chat_groups (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        school_id   INT UNSIGNED NOT NULL,
        name        VARCHAR(200) NOT NULL,
        description VARCHAR(500) NULL,
        type        ENUM('teachers','students','class','custom') NOT NULL DEFAULT 'custom',
        class_id    INT UNSIGNED NULL,
        created_by  INT UNSIGNED NOT NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_school (school_id)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    DB::execute("CREATE TABLE IF NOT EXISTS chat_group_members (
        group_id     INT UNSIGNED NOT NULL,
        user_id      INT UNSIGNED NOT NULL,
        joined_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_read_at TIMESTAMP NULL,
        PRIMARY KEY (group_id, user_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    DB::execute("CREATE TABLE IF NOT EXISTS chat_messages (
        id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        group_id  INT UNSIGNED NOT NULL,
        sender_id INT UNSIGNED NOT NULL,
        body      TEXT NOT NULL,
        sent_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_group  (group_id, sent_at),
        INDEX idx_sender (sender_id)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    // v9 additions
    try {
        DB::execute("ALTER TABLE academic_periods ADD COLUMN IF NOT EXISTS start_date DATE NULL");
        DB::execute("ALTER TABLE academic_periods ADD COLUMN IF NOT EXISTS end_date   DATE NULL");
        DB::execute("ALTER TABLE chat_groups          ADD COLUMN IF NOT EXISTS is_active  TINYINT(1) NOT NULL DEFAULT 1");
        DB::execute("ALTER TABLE chat_group_members   ADD COLUMN IF NOT EXISTS role       ENUM('member','admin') NOT NULL DEFAULT 'member'");
        DB::execute("ALTER TABLE chat_group_members   ADD COLUMN IF NOT EXISTS can_send   TINYINT(1) NOT NULL DEFAULT 1");
        DB::execute("ALTER TABLE chat_messages        ADD COLUMN IF NOT EXISTS reply_to_id BIGINT UNSIGNED NULL");
        DB::execute("ALTER TABLE chat_messages        ADD COLUMN IF NOT EXISTS is_deleted  TINYINT(1) NOT NULL DEFAULT 0");
        DB::execute("ALTER TABLE chat_messages        ADD COLUMN IF NOT EXISTS deleted_by  INT UNSIGNED NULL");
        DB::execute("ALTER TABLE chat_messages        ADD COLUMN IF NOT EXISTS deleted_at  TIMESTAMP NULL");
        DB::execute("ALTER TABLE chat_messages        ADD COLUMN IF NOT EXISTS is_pinned   TINYINT(1) NOT NULL DEFAULT 0");
        DB::execute("CREATE TABLE IF NOT EXISTS message_reactions (
            message_id BIGINT UNSIGNED NOT NULL,
            user_id    INT UNSIGNED    NOT NULL,
            emoji      VARCHAR(10)     NOT NULL,
            reacted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (message_id, user_id),
            INDEX idx_message (message_id)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (Throwable $e) {}
}

function handleChat(string $method, ?int $id, ?string $part1, ?string $part2, ?string $part3, array $body): void {
    $auth = require_auth();
    ensureChatSchema();
    $sid = $auth['school_id'];
    $uid = (int)$auth['user_id'];

    // Resolve sub from parts:
    //   /chat/{id}/messages        → sub='messages', part3=null
    //   /chat/{id}/messages/{msgId} → sub='messages', part3=msgId
    //   /chat/{id}/members/{userId} → sub='members',  part3=userId
    $sub = $id ? $part2 : $part1;

    // ── GET /chat/groups — list all groups I belong to ────────
    if ($method === 'GET' && !$id && $sub === 'groups') {
        $rows = DB::fetchAll(
            "SELECT g.id, g.name, g.description, g.type, g.class_id, g.created_at,
                    IF(g.is_active,1,0) AS is_active,
                    CONCAT(u.first_name,' ',u.last_name) AS created_by_name,
                    (SELECT COUNT(*) FROM chat_group_members WHERE group_id=g.id) AS member_count,
                    (SELECT body FROM chat_messages WHERE group_id=g.id ORDER BY sent_at DESC LIMIT 1) AS last_message,
                    (SELECT sent_at FROM chat_messages WHERE group_id=g.id ORDER BY sent_at DESC LIMIT 1) AS last_message_at,
                    (SELECT COUNT(*) FROM chat_messages cm WHERE cm.group_id=g.id AND cm.is_deleted=0
                     AND cm.sent_at > COALESCE(
                         (SELECT last_read_at FROM chat_group_members WHERE group_id=g.id AND user_id=?),
                         '2000-01-01')) AS unread_count
             FROM chat_groups g
             JOIN chat_group_members m ON m.group_id=g.id AND m.user_id=?
             JOIN users u ON u.id=g.created_by
             WHERE g.school_id=? AND g.is_active=1
             ORDER BY last_message_at DESC, g.created_at DESC",
            [$uid, $uid, $sid]
        );
        respond($rows);
    }

    // ── GET /chat — all groups (admin) or my groups (others) ──
    if ($method === 'GET' && !$id && !$sub) {
        if ($auth['role'] === 'admin') {
            $rows = DB::fetchAll(
                "SELECT g.id, g.name, g.description, g.type, g.class_id, g.created_at,
                        IF(g.is_active,1,0) AS is_active,
                        CONCAT(u.first_name,' ',u.last_name) AS created_by_name,
                        (SELECT COUNT(*) FROM chat_group_members WHERE group_id=g.id) AS member_count,
                        (SELECT body FROM chat_messages WHERE group_id=g.id ORDER BY sent_at DESC LIMIT 1) AS last_message,
                        (SELECT sent_at FROM chat_messages WHERE group_id=g.id ORDER BY sent_at DESC LIMIT 1) AS last_message_at
                 FROM chat_groups g JOIN users u ON u.id=g.created_by
                 WHERE g.school_id=? ORDER BY last_message_at DESC, g.created_at DESC",
                [$sid]
            );
        } else {
            $rows = DB::fetchAll(
                "SELECT g.id, g.name, g.description, g.type, g.class_id, g.created_at,
                        IF(g.is_active,1,0) AS is_active,
                        (SELECT COUNT(*) FROM chat_group_members WHERE group_id=g.id) AS member_count,
                        (SELECT body FROM chat_messages WHERE group_id=g.id ORDER BY sent_at DESC LIMIT 1) AS last_message,
                        (SELECT sent_at FROM chat_messages WHERE group_id=g.id ORDER BY sent_at DESC LIMIT 1) AS last_message_at,
                        (SELECT COUNT(*) FROM chat_messages cm WHERE cm.group_id=g.id AND cm.is_deleted=0
                         AND cm.sent_at > COALESCE(
                             (SELECT last_read_at FROM chat_group_members cgm WHERE cgm.group_id=g.id AND cgm.user_id=?),
                             '2000-01-01')) AS unread_count
                 FROM chat_groups g
                 JOIN chat_group_members m ON m.group_id=g.id AND m.user_id=?
                 WHERE g.school_id=? AND g.is_active=1
                 ORDER BY last_message_at DESC, g.created_at DESC",
                [$uid, $uid, $sid]
            );
        }
        respond($rows);
    }

    // ── POST /chat — admin creates a group ────────────────────
    if ($method === 'POST' && !$id && !$sub) {
        require_role($auth, 'admin');
        $name = trim($body['name'] ?? '');
        $type = trim($body['type'] ?? 'custom');
        $desc = trim($body['description'] ?? '');
        $classId = isset($body['class_id']) ? (int)$body['class_id'] : null;
        if (!$name) err('Group name is required');
        if (!in_array($type, ['teachers','students','class','custom'])) err('Invalid type');

        $gid = DB::insert(
            'INSERT INTO chat_groups (school_id,name,description,type,class_id,created_by) VALUES (?,?,?,?,?,?)',
            [$sid, $name, $desc ?: null, $type, $classId, $uid]
        );

        // Auto-populate members based on type
        $memberIds = [];
        if ($type === 'teachers') {
            $memberIds = array_column(DB::fetchAll(
                "SELECT id FROM users WHERE school_id=? AND role='teacher' AND is_active=1", [$sid]
            ), 'id');
        } elseif ($type === 'students') {
            $memberIds = array_column(DB::fetchAll(
                "SELECT id FROM users WHERE school_id=? AND role='student' AND is_active=1", [$sid]
            ), 'id');
        } elseif ($type === 'class' && $classId) {
            $memberIds = array_column(DB::fetchAll(
                "SELECT student_id AS id FROM class_students WHERE class_id=?", [$classId]
            ), 'id');
            // Also add all class teachers
            $teacherIds = array_column(DB::fetchAll(
                "SELECT DISTINCT teacher_id AS id FROM class_teachers WHERE class_id=?", [$classId]
            ), 'id');
            $memberIds = array_merge($memberIds, $teacherIds);
        } elseif ($type === 'custom' && !empty($body['member_ids'])) {
            $memberIds = array_map('intval', $body['member_ids']);
        }

        // Always add the admin creator
        $memberIds[] = $uid;
        $memberIds   = array_unique($memberIds);

        foreach ($memberIds as $mid) {
            if ($mid) DB::execute(
                'INSERT IGNORE INTO chat_group_members (group_id,user_id) VALUES (?,?)',
                [$gid, $mid]
            );
        }

        respond(['group_id' => $gid, 'member_count' => count($memberIds), 'message' => 'Group created'], 201);
    }

    // ── GET /chat/{id}/messages — fetch messages ──────────────
    if ($method === 'GET' && $id && $sub === 'messages') {
        // Verify membership
        $member = DB::fetchOne('SELECT 1 FROM chat_group_members WHERE group_id=? AND user_id=?', [$id, $uid]);
        if (!$member && $auth['role'] !== 'admin') err('Not a member of this group', 403);

        $since  = isset($_GET['since']) ? $_GET['since'] : null;
        $limit  = min(100, (int)($_GET['limit'] ?? 50));
        $params = [$id];
        $sinceCond = '';
        if ($since) { $sinceCond = 'AND cm.sent_at > ?'; $params[] = $since; }

        $rawMsgs = DB::fetchAll(
            "SELECT cm.id, cm.group_id, cm.sender_id, cm.body, cm.sent_at,
                    cm.reply_to_id, cm.is_pinned,
                    IF(cm.is_deleted=1, 1, 0) AS is_deleted,
                    cm.deleted_by,
                    (SELECT CONCAT(ud.first_name,' ',ud.last_name) FROM users ud WHERE ud.id=cm.deleted_by LIMIT 1) AS deleted_by_name,
                    CONCAT(u.first_name,' ',u.last_name) AS sender_name,
                    u.avatar_color AS sender_color, u.role AS sender_role,
                    (SELECT cm2.body FROM chat_messages cm2 WHERE cm2.id=cm.reply_to_id LIMIT 1) AS reply_body,
                    (SELECT CONCAT(u2.first_name,' ',u2.last_name) FROM chat_messages cm2 JOIN users u2 ON u2.id=cm2.sender_id WHERE cm2.id=cm.reply_to_id LIMIT 1) AS reply_sender,
                    (SELECT CONCAT('[',GROUP_CONCAT(JSON_OBJECT('emoji',emoji,'user_id',user_id)),']') FROM message_reactions WHERE message_id=cm.id) AS reactions_raw
             FROM chat_messages cm
             JOIN users u ON u.id=cm.sender_id
             WHERE cm.group_id=? $sinceCond
             ORDER BY cm.sent_at DESC LIMIT $limit",
            $params
        );
        // Cast numeric flags so JS truthy checks work correctly
        $msgs = array_map(function($m) {
            $m['is_deleted'] = (int)($m['is_deleted'] ?? 0);
            $m['is_pinned']  = (int)($m['is_pinned']  ?? 0);
            return $m;
        }, $rawMsgs);
        // Return oldest-first for display
        respond(array_reverse($msgs));
    }

    // ── POST /chat/{id}/messages — send a message ─────────────
    if ($method === 'POST' && $id && $sub === 'messages') {
        $member = DB::fetchOne('SELECT role, can_send FROM chat_group_members WHERE group_id=? AND user_id=?', [$id, $uid]);
        if (!$member && $auth['role'] !== 'admin') err('Not a member of this group', 403);
        // Check if messaging is restricted for this member
        if ($member && !(int)$member['can_send'] && $auth['role'] !== 'admin') err('You have been muted in this group', 403);
        $body_text = trim($body['body'] ?? '');
        if (!$body_text) err('Message body is required');
        $replyTo = isset($body['reply_to_id']) ? (int)$body['reply_to_id'] : null;
        $msgId = DB::insert(
            'INSERT INTO chat_messages (group_id,sender_id,body,reply_to_id) VALUES (?,?,?,?)',
            [$id, $uid, $body_text, $replyTo]
        );
        // Update caller's last_read to now
        DB::execute(
            'UPDATE chat_group_members SET last_read_at=NOW() WHERE group_id=? AND user_id=?',
            [$id, $uid]
        );
        respond(['id' => $msgId, 'sent_at' => date('Y-m-d H:i:s')], 201);
    }

    // ── PATCH /chat/{id}/read ─────────────────────────────────
    if ($method === 'PATCH' && $id && $sub === 'read') {
        DB::execute(
            'UPDATE chat_group_members SET last_read_at=NOW() WHERE group_id=? AND user_id=?',
            [$id, $uid]
        );
        respond(['message' => 'Marked read']);
    }

    // ── GET /chat/{id}/members ────────────────────────────────
    if ($method === 'GET' && $id && $sub === 'members') {
        $chk = DB::fetchOne('SELECT role FROM chat_group_members WHERE group_id=? AND user_id=?', [$id, $uid]);
        if (!$chk && $auth['role'] !== 'admin') err('Forbidden', 403);
        $mbrs = DB::fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.role, u.class_name, u.avatar_color,
                    m.joined_at, m.last_read_at, m.role AS group_role,
                    IF(m.can_send,1,0) AS can_send
             FROM chat_group_members m JOIN users u ON u.id=m.user_id
             WHERE m.group_id=? ORDER BY m.role DESC, u.role, u.last_name",
            [$id]
        );
        respond($mbrs);
    }

    // ── DELETE /chat/{id}/messages/{msgId} — delete a message ──
    if ($method === 'DELETE' && $id && $sub === 'messages' && $part3) {
        $msgId = (int)$part3;
        // Allow admin or group admin
        $myRole = DB::fetchOne('SELECT role FROM chat_group_members WHERE group_id=? AND user_id=?', [$id, $uid]);
        $canDel = $auth['role'] === 'admin' || ($myRole && $myRole['role'] === 'admin');
        if (!$canDel) err('Only admins can delete messages', 403);
        $delName = DB::fetchOne("SELECT CONCAT(first_name,' ',last_name) AS n FROM users WHERE id=?", [$uid])['n'] ?? 'Admin';
        DB::execute(
            'UPDATE chat_messages SET is_deleted=1, deleted_by=?, deleted_at=NOW() WHERE id=? AND group_id=?',
            [$uid, $msgId, $id]
        );
        respond(['message' => 'Deleted']);
    }

    // ── POST /chat/{id}/react — toggle reaction ─
    if ($method === 'POST' && $id && $sub === 'react') {
        // part2 is actually msgId here when path is /chat/{id}/messages/react (with msgId in body)
        $msgId = (int)($body['message_id'] ?? 0);
        $emoji = trim($body['emoji'] ?? '');
        if (!$msgId || !$emoji) err('message_id and emoji required');
        // Toggle: remove if same, add/replace if different
        $existing = DB::fetchOne('SELECT emoji FROM message_reactions WHERE message_id=? AND user_id=?', [$msgId, $uid]);
        if ($existing && $existing['emoji'] === $emoji) {
            DB::execute('DELETE FROM message_reactions WHERE message_id=? AND user_id=?', [$msgId, $uid]);
            respond(['toggled' => 'removed']);
        } else {
            DB::execute('INSERT INTO message_reactions (message_id,user_id,emoji) VALUES (?,?,?) ON DUPLICATE KEY UPDATE emoji=VALUES(emoji), reacted_at=NOW()', [$msgId, $uid, $emoji]);
            respond(['toggled' => 'added']);
        }
    }

    // ── PATCH /chat/{id} — update group settings ──────────────
    if ($method === 'PATCH' && $id && !$sub) {
        require_role($auth, 'admin');
        $g = DB::fetchOne('SELECT id FROM chat_groups WHERE id=? AND school_id=?', [$id, $sid]);
        if (!$g) err('Group not found', 404);
        $sets = []; $params = [];
        if (array_key_exists('is_active', $body)) { $sets[] = 'is_active=?'; $params[] = (int)$body['is_active']; }
        if (array_key_exists('name', $body))       { $sets[] = 'name=?';      $params[] = trim($body['name']); }
        if (array_key_exists('description', $body)){ $sets[] = 'description=?'; $params[] = trim($body['description']); }
        if ($sets) {
            $params[] = $id;
            try { DB::execute('UPDATE chat_groups SET '.implode(',', $sets).' WHERE id=?', $params); }
            catch (Throwable $e) { err('Update failed — run migration_v9.sql first: '.$e->getMessage()); }
        }
        respond(['message' => 'Updated', 'is_active' => (int)$body['is_active']]);
    }

    // ── PATCH /chat/{id}/members/{userId} — update member role/mute ─
    if ($method === 'PATCH' && $id && $sub === 'members' && $part3) {
        require_role($auth, 'admin');
        $targetUid = (int)$part3;
        if (!$targetUid) err('user_id required');
        $sets = []; $params = [];
        if (array_key_exists('role', $body))     { $sets[] = 'role=?';     $params[] = in_array($body['role'],['member','admin']) ? $body['role'] : 'member'; }
        if (array_key_exists('can_send', $body)) { $sets[] = 'can_send=?'; $params[] = (int)$body['can_send']; }
        if ($sets) {
            $params[] = $id; $params[] = $targetUid;
            try { DB::execute('UPDATE chat_group_members SET '.implode(',', $sets).' WHERE group_id=? AND user_id=?', $params); }
            catch (Throwable $e) { err('Update failed — run migration_v9.sql: '.$e->getMessage()); }
        }
        respond(['message' => 'Member updated']);
    }

    // ── POST /chat/{id}/members — add member (admin) ──────────
    if ($method === 'POST' && $id && $sub === 'members') {
        require_role($auth, 'admin');
        $newUid = (int)($body['user_id'] ?? 0);
        if (!$newUid) err('user_id required');
        DB::execute('INSERT IGNORE INTO chat_group_members (group_id,user_id) VALUES (?,?)', [$id, $newUid]);
        respond(['message' => 'Member added']);
    }

    // ── DELETE /chat/{id}/members/{userId} — remove member ────
    if ($method === 'DELETE' && $id && $sub === 'members' && $part3) {
        require_role($auth, 'admin');
        $targetUid = (int)$part3;
        DB::execute('DELETE FROM chat_group_members WHERE group_id=? AND user_id=?', [$id, $targetUid]);
        respond(['message' => 'Member removed']);
    }

    // ── POST /chat/setup-class-groups — create default class groups ─
    if ($method === 'POST' && !$id && $part1 === 'setup-class-groups') {
        require_role($auth, 'admin');
        $classes = DB::fetchAll('SELECT id, name, year_group FROM classes WHERE school_id=?', [$sid]);
        $created = 0;
        foreach ($classes as $cls) {
            // Check if class group already exists
            $existing = DB::fetchOne('SELECT id FROM chat_groups WHERE school_id=? AND class_id=? AND type="class"', [$sid, (int)$cls['id']]);
            if ($existing) continue;
            $gid = DB::insert(
                'INSERT INTO chat_groups (school_id,name,description,type,class_id,created_by) VALUES (?,?,?,?,?,?)',
                [$sid, $cls['name'].' Chat', 'Default class group for '.$cls['name'], 'class', (int)$cls['id'], $uid]
            );
            // Add all students and teachers for this class
            $students = DB::fetchAll('SELECT student_id AS id FROM class_students WHERE class_id=?', [(int)$cls['id']]);
            $teachers = DB::fetchAll('SELECT DISTINCT teacher_id AS id FROM class_teachers WHERE class_id=?', [(int)$cls['id']]);
            foreach (array_merge($students, $teachers, [['id'=>$uid]]) as $m) {
                if ($m['id']) DB::execute('INSERT IGNORE INTO chat_group_members (group_id,user_id) VALUES (?,?)', [$gid, (int)$m['id']]);
            }
            $created++;
        }
        respond(['created' => $created, 'message' => "$created class group(s) created"]);
    }

    // ── PATCH /chat/{id}/pin/{msgId} — pin/unpin message ──────
    if ($method === 'PATCH' && $id && $sub === 'pin' && $part3) {
        $msgId = (int)$part3;
        $myRole = DB::fetchOne('SELECT role FROM chat_group_members WHERE group_id=? AND user_id=?', [$id, $uid]);
        if ($auth['role'] !== 'admin' && (!$myRole || $myRole['role'] !== 'admin')) err('Admins only', 403);
        $msg = DB::fetchOne('SELECT is_pinned FROM chat_messages WHERE id=? AND group_id=?', [$msgId, $id]);
        if (!$msg) err('Message not found', 404);
        DB::execute('UPDATE chat_messages SET is_pinned=? WHERE id=?', [$msg['is_pinned'] ? 0 : 1, $msgId]);
        respond(['is_pinned' => !$msg['is_pinned']]);
    }

    // ── DELETE /chat/{id} — admin deletes group ───────────────
    if ($method === 'DELETE' && $id && !$sub) {
        require_role($auth, 'admin');
        $g = DB::fetchOne('SELECT id FROM chat_groups WHERE id=? AND school_id=?', [$id, $sid]);
        if (!$g) err('Group not found', 404);
        DB::execute('DELETE FROM message_reactions  WHERE message_id IN (SELECT id FROM chat_messages WHERE group_id=?)', [$id]);
        DB::execute('DELETE FROM chat_messages       WHERE group_id=?', [$id]);
        DB::execute('DELETE FROM chat_group_members  WHERE group_id=?', [$id]);
        DB::execute('DELETE FROM chat_groups         WHERE id=?',       [$id]);
        respond(['message' => 'Group deleted']);
    }

    err('Chat endpoint error', 404);
}

// ══════════════════════════════════════════════════════════════
// MESSAGES
// ══════════════════════════════════════════════════════════════
function ensureMessageSchema(): void {
    DB::execute("CREATE TABLE IF NOT EXISTS messages (
        id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        school_id      INT UNSIGNED NOT NULL,
        sender_id      INT UNSIGNED NOT NULL,
        sender_role    VARCHAR(20)  NOT NULL,
        recipient_type VARCHAR(20)  NOT NULL
            COMMENT 'individual|class|all_students|all_teachers',
        recipient_id   INT UNSIGNED NULL
            COMMENT 'user_id or class_id; NULL for broadcasts',
        subject        VARCHAR(255) NOT NULL,
        body           TEXT         NOT NULL,
        sent_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_school (school_id),
        INDEX idx_sender (sender_id)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    DB::execute("CREATE TABLE IF NOT EXISTS message_reads (
        message_id INT UNSIGNED NOT NULL,
        user_id    INT UNSIGNED NOT NULL,
        read_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (message_id, user_id)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}

function handleMessages(string $method, ?int $id, ?string $sub, array $body): void {
    $auth = require_auth();
    ensureMessageSchema();
    $sid  = $auth['school_id'];
    $uid  = (int)$auth['user_id'];
    $role = $auth['role'];

    // ── GET /messages/recipients — who can I send to? ───────────
    if ($method === 'GET' && !$id && $sub === 'recipients') {
        require_role($auth, 'teacher', 'admin');
        $recipients = [];

        if ($role === 'teacher') {
            // Teacher: their classes + students in those classes
            $classes = DB::fetchAll(
                "SELECT DISTINCT c.id, c.name, c.year_group,
                        COUNT(DISTINCT cs.student_id) AS student_count
                 FROM classes c
                 LEFT JOIN class_students cs ON cs.class_id=c.id
                 WHERE c.school_id=?
                   AND (c.teacher_id=? OR EXISTS (
                       SELECT 1 FROM class_teachers ct WHERE ct.class_id=c.id AND ct.teacher_id=?
                   ))
                 GROUP BY c.id, c.name, c.year_group
                 ORDER BY c.year_group, c.name",
                [$sid, $uid, $uid]
            );
            $classIds = array_column($classes, 'id');
            $students = [];
            if (!empty($classIds)) {
                $ph = implode(',', array_fill(0, count($classIds), '?'));
                $students = DB::fetchAll(
                    "SELECT DISTINCT u.id, u.first_name, u.last_name, u.class_name
                     FROM users u
                     JOIN class_students cs ON cs.student_id=u.id
                     WHERE cs.class_id IN ($ph) AND u.is_active=1
                     ORDER BY u.class_name, u.last_name",
                    $classIds
                );
            }
            $recipients = [
                'broadcasts' => [
                    ['type'=>'all_students','label'=>'All my students','count'=>array_sum(array_column($classes,'student_count'))]
                ],
                'classes'  => $classes,
                'students' => $students,
                'teachers' => [],
            ];
        } else {
            // Admin: everyone
            $classes  = DB::fetchAll(
                "SELECT id, name, year_group,
                        (SELECT COUNT(*) FROM class_students cs WHERE cs.class_id=classes.id) AS student_count
                 FROM classes WHERE school_id=? ORDER BY year_group, name", [$sid]);
            $teachers = DB::fetchAll(
                "SELECT id, first_name, last_name, department_id FROM users
                 WHERE school_id=? AND role='teacher' AND is_active=1 ORDER BY last_name", [$sid]);
            $students = DB::fetchAll(
                "SELECT id, first_name, last_name, class_name FROM users
                 WHERE school_id=? AND role='student' AND is_active=1 ORDER BY class_name, last_name", [$sid]);
            $recipients = [
                'broadcasts' => [
                    ['type'=>'all_students','label'=>'All students',
                     'count'=>count($students)],
                    ['type'=>'all_teachers','label'=>'All teachers',
                     'count'=>count($teachers)],
                ],
                'classes'  => $classes,
                'students' => $students,
                'teachers' => $teachers,
            ];
        }
        respond($recipients);
    }

    // ── GET /messages/sent ────────────────────────────────────
    if ($method === 'GET' && !$id && $sub === 'sent') {
        require_role($auth, 'teacher', 'admin');
        $rows = DB::fetchAll(
            "SELECT m.id, m.recipient_type, m.recipient_id, m.subject,
                    LEFT(m.body,160) AS preview, m.sent_at,
                    CONCAT(u.first_name,' ',u.last_name) AS sender_name,
                    CASE m.recipient_type
                        WHEN 'all_students' THEN 'All students'
                        WHEN 'all_teachers' THEN 'All teachers'
                        WHEN 'class'        THEN c.name
                        ELSE CONCAT(ru.first_name,' ',ru.last_name)
                    END AS recipient_label,
                    (SELECT COUNT(*) FROM message_reads mr WHERE mr.message_id=m.id) AS read_count
             FROM messages m
             JOIN users u ON u.id=m.sender_id
             LEFT JOIN users   ru ON ru.id=m.recipient_id AND m.recipient_type='individual'
             LEFT JOIN classes c  ON c.id=m.recipient_id  AND m.recipient_type='class'
             WHERE m.sender_id=? AND m.school_id=?
             ORDER BY m.sent_at DESC LIMIT 50",
            [$uid, $sid]
        );
        respond($rows);
    }

    // ── GET /messages — inbox for current user ────────────────
    if ($method === 'GET' && !$id && !$sub) {
        // Build WHERE conditions and their bound params separately.
        // The outer query already binds $uid (for read_at subquery) and $sid
        // (for school_id) via array_merge — do NOT pre-load those here.
        $conditions = [];
        $condParams = [];

        $conditions[] = "(m.recipient_type='individual' AND m.recipient_id=?)";
        $condParams[] = $uid;

        if ($role === 'student') {
            $conditions[] = "(m.recipient_type='class' AND m.recipient_id IN (
                SELECT class_id FROM class_students WHERE student_id=?))";
            $condParams[] = $uid;
            $conditions[] = "m.recipient_type='all_students'";
        }
        if ($role === 'teacher') {
            $conditions[] = "m.recipient_type='all_teachers'";
        }

        $where = implode(' OR ', $conditions);
        $rows  = DB::fetchAll(
            "SELECT m.id, m.sender_id, m.recipient_type, m.subject,
                    LEFT(m.body,200) AS preview, m.body, m.sent_at,
                    CONCAT(u.first_name,' ',u.last_name) AS sender_name,
                    u.avatar_color AS sender_color, u.role AS sender_role,
                    (SELECT read_at FROM message_reads mr
                     WHERE mr.message_id=m.id AND mr.user_id=?) AS read_at
             FROM messages m
             JOIN users u ON u.id=m.sender_id
             WHERE m.school_id=? AND ($where)
             ORDER BY m.sent_at DESC LIMIT 50",
            array_merge([$uid, $sid], $condParams)
        );
        respond($rows);
    }

    // ── GET /messages/{id} — single message ──────────────────
    if ($method === 'GET' && $id && !$sub) {
        $msg = DB::fetchOne(
            "SELECT m.*, CONCAT(u.first_name,' ',u.last_name) AS sender_name, u.avatar_color AS sender_color
             FROM messages m JOIN users u ON u.id=m.sender_id
             WHERE m.id=? AND m.school_id=?", [$id, $sid]
        );
        if (!$msg) err('Message not found', 404);
        // Mark as read
        DB::execute('INSERT IGNORE INTO message_reads (message_id,user_id) VALUES (?,?)', [$id, $uid]);
        respond($msg);
    }

    // ── POST /messages — send ─────────────────────────────────
    if ($method === 'POST') {
        require_role($auth, 'teacher', 'admin');
        $rType = trim($body['recipient_type'] ?? '');
        $rId   = isset($body['recipient_id']) ? (int)$body['recipient_id'] : null;
        $subj  = trim($body['subject'] ?? '');
        $msgBody = trim($body['body'] ?? '');
        if (!$rType || !$subj || !$msgBody) err('recipient_type, subject and body are required');
        if (!in_array($rType, ['individual','class','all_students','all_teachers']))
            err('Invalid recipient_type');

        // Teachers may only message students (not other teachers)
        if ($role === 'teacher' && in_array($rType, ['all_teachers']))
            err('Teachers can only message students', 403);

        $msgId = DB::insert(
            'INSERT INTO messages (school_id,sender_id,sender_role,recipient_type,recipient_id,subject,body)
             VALUES (?,?,?,?,?,?,?)',
            [$sid, $uid, $role, $rType, $rId, $subj, $msgBody]
        );

        // Deliver a notification to each recipient so the bell lights up
        $senderName = DB::fetchOne('SELECT CONCAT(first_name," ",last_name) AS n FROM users WHERE id=?', [$uid])['n'] ?? 'Teacher';
        $recipientIds = [];
        if ($rType === 'individual' && $rId) {
            $recipientIds = [$rId];
        } elseif ($rType === 'class' && $rId) {
            $recipientIds = array_column(DB::fetchAll('SELECT student_id FROM class_students WHERE class_id=?', [$rId]), 'student_id');
        } elseif ($rType === 'all_students') {
            $recipientIds = array_column(DB::fetchAll('SELECT id FROM users WHERE school_id=? AND role="student" AND is_active=1', [$sid]), 'id');
        } elseif ($rType === 'all_teachers') {
            $recipientIds = array_column(DB::fetchAll('SELECT id FROM users WHERE school_id=? AND role="teacher" AND is_active=1', [$sid]), 'id');
        }
        foreach ($recipientIds as $rid) {
            if ((int)$rid === $uid) continue;
            DB::execute(
                'INSERT INTO notifications (user_id,type,title,body,link) VALUES (?,?,?,?,?)',
                [$rid, 'message', 'New message from '.$senderName, $subj, 'messages']
            );
        }

        respond(['message' => 'Sent', 'message_id' => $msgId, 'recipients' => count($recipientIds)], 201);
    }

    // ── PATCH /messages/{id}/read ─────────────────────────────
    if ($method === 'PATCH' && $id && $sub === 'read') {
        DB::execute('INSERT IGNORE INTO message_reads (message_id,user_id) VALUES (?,?)', [$id, $uid]);
        respond(['message' => 'Read']);
    }

    // ── DELETE /messages/{id} ─────────────────────────────────
    if ($method === 'DELETE' && $id) {
        $msg = DB::fetchOne('SELECT sender_id FROM messages WHERE id=? AND school_id=?', [$id, $sid]);
        if (!$msg) err('Not found', 404);
        if ((int)$msg['sender_id'] !== $uid && $role !== 'admin') err('Forbidden', 403);
        DB::execute('DELETE FROM message_reads WHERE message_id=?', [$id]);
        DB::execute('DELETE FROM messages WHERE id=?', [$id]);
        respond(['message' => 'Deleted']);
    }

    err('Messages endpoint error', 404);
}

// ══════════════════════════════════════════════════════════════
// NOTIFICATIONS
// ══════════════════════════════════════════════════════════════
function handleNotifications(string $method, ?int $id, array $body): void {
    $auth = require_auth();
    if ($method === 'GET') {
        respond(DB::fetchAll('SELECT id,type,title,body,link,is_read,created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 30', [$auth['user_id']]));
    }
    if ($method === 'PATCH' && $id) {
        DB::execute('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?', [$id,$auth['user_id']]);
        respond(['message'=>'Read']);
    }
    err('Notifications endpoint error',404);
}


// ══════════════════════════════════════════════════════════════
// AI QUESTION GENERATION  (Anthropic claude-sonnet-4-20250514)
// ══════════════════════════════════════════════════════════════
function handleAIGenerate(string $method, array $body): void {
    if ($method !== 'POST') err('Method not allowed', 405);
    $auth = require_auth();
    require_role($auth, 'teacher', 'admin');

    $topic    = trim($body['topic']    ?? '');
    $sub      = trim($body['sub_strand'] ?? 'S2/SS2');
    $count    = min(10, max(1, (int)($body['count'] ?? 5)));
    $diff     = trim($body['difficulty'] ?? 'Mixed');

    if (!$topic) err('Topic is required');

    $apiKey = ANTHROPIC_API_KEY;
    if (!$apiKey) err('Anthropic API key not configured. Add ANTHROPIC_API_KEY to your .env file.', 503);

    // Build the prompt
    $diffNote = $diff === 'Mixed'
        ? 'Mix of Easy (Remember/Understand), Medium (Apply) and Hard (Analyse) questions.'
        : "$diff difficulty only.";

    $prompt = <<<PROMPT
You are an expert Ghana GES ICT curriculum assessment author for Senior High School.

Generate exactly $count multiple-choice questions on this topic: "$topic"
Sub-strand: $sub
$diffNote
Year group: Ghana SHS (Year 1 or 2)

STRICT RULES:
1. Use Ghanaian context throughout: MTN MoMo, GhIPSS, GhQR, Zipline, KNUST, GRA, NHIA, Tonaton, Jumia Ghana, Bank of Ghana, Cybersecurity Act 2020 (Act 1038), Data Protection Act 2012 (Act 843)
2. Use Ghanaian names: Kofi, Ama, Kweku, Abena, Fatima, Nana, Emmanuel
3. Follow WASSCE question style — clear, unambiguous, one definitively correct answer
4. Each question has exactly 4 options (A, B, C, D)
5. Include a one-sentence explanation for the correct answer

Respond with ONLY a valid JSON array. No markdown, no backticks, no explanation before or after:
[
  {
    "question": "Question text here?",
    "options": [
      {"label": "A", "text": "Option A text", "correct": false},
      {"label": "B", "text": "Option B text", "correct": true},
      {"label": "C", "text": "Option C text", "correct": false},
      {"label": "D", "text": "Option D text", "correct": false}
    ],
    "explanation": "One sentence explaining why the correct answer is correct.",
    "difficulty": "Easy|Medium|Hard",
    "bloom": "Remember|Understand|Apply|Analyse"
  }
]
PROMPT;

    // Call Anthropic API
    $payload = json_encode([
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 2000,
        'messages'   => [['role' => 'user', 'content' => $prompt]],
    ]);

    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => implode("\r\n", [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
                'Content-Length: ' . strlen($payload),
            ]),
            'content'       => $payload,
            'timeout'       => 30,
            'ignore_errors' => true,
        ],
    ]);

    $raw = @file_get_contents('https://api.anthropic.com/v1/messages', false, $ctx);

    if ($raw === false) {
        err('Could not reach Anthropic API. Check your server allows outbound HTTPS.', 502);
    }

    $response = json_decode($raw, true);

    if (isset($response['error'])) {
        err('Anthropic API error: ' . ($response['error']['message'] ?? 'Unknown'), 502);
    }

    $text = $response['content'][0]['text'] ?? '';
    if (!$text) err('Empty response from AI', 502);

    // Strip any accidental markdown fences
    $text = preg_replace('/^```[a-z]*\n?|\n?```$/m', '', trim($text));

    // Find the JSON array (starts with [ ends with ])
    $start = strpos($text, '[');
    $end   = strrpos($text, ']');
    if ($start === false || $end === false) {
        err('AI returned invalid format. Try again.', 502);
    }
    $text = substr($text, $start, $end - $start + 1);

    $questions = json_decode($text, true);
    if (!is_array($questions) || empty($questions)) {
        err('AI returned unparseable JSON. Try again.', 502);
    }

    // Normalise each question to a consistent shape
    $normalised = array_map(function($q) use ($sub) {
        return [
            'question'    => $q['question'] ?? '',
            'options'     => $q['options']  ?? [],
            'explanation' => $q['explanation'] ?? '',
            'difficulty'  => $q['difficulty']  ?? 'Medium',
            'bloom'       => $q['bloom']       ?? 'Understand',
            'sub_strand'  => $sub,
        ];
    }, $questions);

    respond(['questions' => $normalised, 'count' => count($normalised)]);
}

// ══════════════════════════════════════════════════════════════
// BULK QUESTION UPLOAD
// POST /api/v1/questions-bulk
// Accepts JSON array of questions parsed from CSV/Excel on frontend
// ══════════════════════════════════════════════════════════════
function handleBulkQuestions(string $method, array $body): void {
    if ($method !== 'POST') err('Method not allowed', 405);
    $auth = require_auth();
    require_role($auth, 'teacher', 'admin');

    $questions = $body['questions'] ?? [];
    if (!is_array($questions) || empty($questions)) err('No questions provided');

    $imported = 0;
    $failed   = [];
    $letters  = ['A','B','C','D','E','F'];

    DB::beginTransaction();
    try {
        foreach ($questions as $i => $q) {
            $text = trim($q['question_text'] ?? $q['question'] ?? '');
            if (!$text) { $failed[] = "Row $i: empty question text"; continue; }

            $sub    = trim($q['sub_strand']  ?? 'S2/SS2');
            $topic  = trim($q['topic']       ?? $sub);
            $diff   = trim($q['difficulty']  ?? 'Medium');
            $bloom  = trim($q['bloom_level'] ?? 'Remember');
            $marks  = max(1, (int)($q['marks'] ?? 1));
            $type   = trim($q['type']        ?? 'mcq');
            $exp    = trim($q['explanation'] ?? $q['marking_guide'] ?? '');

            // Validate difficulty and type
            if (!in_array($diff, ['Easy','Medium','Hard'])) $diff = 'Medium';
            if (!in_array($type, ['mcq','multi','essay','short','fill-in','tf'])) $type = 'mcq';

            $qid = DB::insert(
                'INSERT INTO questions
                 (school_id, author_id, type, sub_strand, topic, bloom_level,
                  difficulty, year_group, marks, question_text, explanation, is_active)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,1)',
                [
                    $auth['school_id'], $auth['user_id'], $type,
                    $sub, $topic, $bloom, $diff,
                    (int)($q['year_group'] ?? 1), $marks, $text,
                    $exp ?: null,
                ]
            );

            // Build options based on question type
            $opts = [];
            if (!empty($q['options']) && is_array($q['options'])) {
                // JSON/structured format — used by the JS bulk parser for all types
                foreach ($q['options'] as $j => $o) {
                    $opts[] = [
                        'label'   => $o['label'] ?? $letters[$j] ?? $letters[0],
                        'text'    => trim($o['text'] ?? ''),
                        'correct' => (int)($o['correct'] ?? ($o['is_correct'] ?? 0)),
                    ];
                }
            } elseif ($type === 'tf') {
                // CSV True/False: correct column = "True" or "False"
                $ans = strtolower(trim($q['correct'] ?? $q['answer'] ?? 'true'));
                $opts = [
                    ['label'=>'A','text'=>'True', 'correct'=>in_array($ans,['true','yes','t','1'])?1:0],
                    ['label'=>'B','text'=>'False','correct'=>in_array($ans,['false','no','f','0'])?1:0],
                ];
            } elseif (in_array($type, ['short','fill-in'])) {
                // CSV: accepted_answers or correct_answer column, semicolon-separated
                $raw = trim($q['accepted_answers'] ?? $q['correct_answer'] ?? $q['answer'] ?? '');
                foreach (array_filter(array_map('trim', explode(';', $raw))) as $j => $ans) {
                    $opts[] = ['label'=>$letters[$j]??$letters[0],'text'=>$ans,'correct'=>1];
                }
            } elseif ($type === 'essay') {
                $opts = []; // essay has no options; marking_guide already in $exp
            } elseif (isset($q['A']) || isset($q['option_a'])) {
                // CSV MCQ flat format: A, B, C, D + correct column
                $correct = strtoupper(trim($q['correct'] ?? $q['answer'] ?? 'A'));
                foreach (['A','B','C','D'] as $lbl) {
                    $val = trim($q[$lbl] ?? $q['option_'.strtolower($lbl)] ?? '');
                    if ($val) $opts[] = ['label'=>$lbl,'text'=>$val,'correct'=>$lbl===$correct?1:0];
                }
            }

            foreach ($opts as $j => $o) {
                if (!$o['text']) continue;
                DB::execute(
                    'INSERT INTO question_options
                     (question_id, option_label, option_text, is_correct, sort_order)
                     VALUES (?,?,?,?,?)',
                    [$qid, $o['label'], $o['text'], $o['correct'], $j]
                );
            }

            $imported++;
        }
        DB::commit();
    } catch (Throwable $e) {
        DB::rollback();
        err('Import failed at question ' . $imported . ': ' . $e->getMessage(), 500);
    }

    respond([
        'imported' => $imported,
        'failed'   => count($failed),
        'errors'   => $failed,
        'message'  => "$imported question(s) imported successfully",
    ]);
}

// ══════════════════════════════════════════════════════════════
// LIVE QUIZ
// POST   /live                   — teacher creates session
// GET    /live/{code}            — get session state (teacher + student poll)
// POST   /live/{code}/join       — student joins lobby
// PATCH  /live/{code}/start      — teacher starts (releases Q0)
// PATCH  /live/{code}/next       — teacher advances to next question
// PATCH  /live/{code}/end        — teacher force-ends
// POST   /live/{code}/answer     — student submits answer
// GET    /live/{code}/results    — final leaderboard
// ══════════════════════════════════════════════════════════════
function handleLive(string $method, array $parts, array $body): void {
    $auth   = require_auth();
    $code   = strtoupper(trim($parts[1] ?? ''));
    $action = $parts[2] ?? null;

    ensureLiveTables();

    // ── POST /live — teacher creates a new live session ──────────
    if ($method === 'POST' && !$code) {
        require_role($auth, 'teacher', 'admin');
        $testId = (int)($body['test_id'] ?? 0);
        if (!$testId) err('test_id required');
        $test = DB::fetchOne('SELECT id, title FROM tests WHERE id=? AND school_id=?', [$testId, $auth['school_id']]);
        if (!$test) err('Test not found', 404);

        // End any previous open session for this test by this teacher
        DB::execute('UPDATE live_sessions SET status="ended", ended_at=NOW() WHERE test_id=? AND teacher_id=? AND status != "ended"', [$testId, $auth['user_id']]);

        // Generate unique 6-char alphanumeric code
        do {
            $chars    = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $roomCode = '';
            for ($i = 0; $i < 6; $i++) $roomCode .= $chars[random_int(0, strlen($chars) - 1)];
            $clash = DB::fetchOne("SELECT id FROM live_sessions WHERE room_code=? AND status != 'ended'", [$roomCode]);
        } while ($clash);

        $sid = DB::insert(
            'INSERT INTO live_sessions (test_id, room_code, teacher_id, school_id) VALUES (?,?,?,?)',
            [$testId, $roomCode, $auth['user_id'], $auth['school_id']]
        );
        $qCount = DB::fetchOne('SELECT COUNT(*) AS n FROM test_questions WHERE test_id=?', [$testId])['n'] ?? 0;
        respond(['session_id' => $sid, 'room_code' => $roomCode, 'test_title' => $test['title'], 'question_count' => (int)$qCount], 201);
    }

    // ── GET /live/{code} — poll for session state ─────────────────
    if ($method === 'GET' && $code && !$action) {
        $sess = DB::fetchOne(
            'SELECT ls.*, t.title AS test_title, t.time_limit_min, UNIX_TIMESTAMP(ls.started_at) AS started_at_unix FROM live_sessions ls JOIN tests t ON t.id=ls.test_id WHERE ls.room_code=?',
            [$code]
        );
        if (!$sess) err('Room not found', 404);

        $participants = DB::fetchAll(
            'SELECT lp.student_id, lp.score, lp.last_q_answered, u.first_name, u.last_name, u.avatar_color
             FROM live_participants lp JOIN users u ON u.id=lp.student_id
             WHERE lp.session_id=? ORDER BY lp.score DESC',
            [$sess['id']]
        );

        $totalQ    = (int)(DB::fetchOne('SELECT COUNT(*) AS n FROM test_questions WHERE test_id=?', [$sess['test_id']])['n'] ?? 0);
        $questions = [];
        $myAnswers = [];          // keyed by q_index (student only)
        $answerCounts = [];       // keyed by q_index (teacher: how many students answered each Q)
        $totalAnswered = 0;

        if ($sess['status'] === 'active') {
            // Return all questions — students answer at their own pace
            $questions = DB::fetchAll(
                "SELECT q.id, q.question_text, q.marks, q.sub_strand, q.difficulty,
                        (SELECT JSON_ARRAYAGG(JSON_OBJECT('id',qo.id,'label',qo.option_label,'text',qo.option_text))
                         FROM question_options qo WHERE qo.question_id=q.id ORDER BY qo.sort_order) AS options
                 FROM test_questions tq JOIN questions q ON q.id=tq.question_id
                 WHERE tq.test_id=? ORDER BY tq.sort_order",
                [$sess['test_id']]
            );

            // Per-question answer counts (for teacher progress view)
            $rows = DB::fetchAll(
                'SELECT q_index, COUNT(*) AS cnt FROM live_answers WHERE session_id=? GROUP BY q_index',
                [$sess['id']]
            );
            foreach ($rows as $r) {
                $answerCounts[(int)$r['q_index']] = (int)$r['cnt'];
            }
            $totalAnswered = array_sum($answerCounts);

            // Student's own answers for all questions
            if ($auth['role'] === 'student') {
                $sRows = DB::fetchAll(
                    'SELECT q_index, selected_opt, is_correct, marks_awarded
                     FROM live_answers WHERE session_id=? AND student_id=?',
                    [$sess['id'], $auth['user_id']]
                );
                foreach ($sRows as $r) {
                    $myAnswers[(int)$r['q_index']] = [
                        'selected_opt'  => $r['selected_opt'],
                        'is_correct'    => (int)$r['is_correct'],   // cast: 0 or 1, never "0"/"1"
                        'marks_awarded' => (float)($r['marks_awarded'] ?? 0),
                    ];
                }
            }
        }

        // UNIX_TIMESTAMP() is always UTC, as is time() — timezone-safe diff
        $elapsedSeconds = ($sess['started_at_unix'] && $sess['status'] === 'active')
            ? max(0, time() - (int)$sess['started_at_unix'])
            : 0;

        respond([
            'session'           => $sess,
            'elapsed_seconds'   => $elapsedSeconds,
            'participants'      => $participants,
            'participant_count' => count($participants),
            'questions'         => $questions,
            'answer_counts'     => $answerCounts,
            'answered_count'    => $totalAnswered,
            'total_possible'    => count($participants) * $totalQ,
            'total_questions'   => $totalQ,
            'my_answers'        => $myAnswers,
        ]);
    }

    // ── POST /live/{code}/join — student joins lobby ──────────────
    if ($method === 'POST' && $code && $action === 'join') {
        $sess = DB::fetchOne('SELECT * FROM live_sessions WHERE room_code=?', [$code]);
        if (!$sess) err('Room not found — check the code', 404);
        if ($sess['status'] === 'ended') err('This quiz session has already ended', 409);
        DB::execute(
            'INSERT IGNORE INTO live_participants (session_id, student_id) VALUES (?,?)',
            [$sess['id'], $auth['user_id']]
        );
        $title = DB::fetchOne('SELECT title FROM tests WHERE id=?', [$sess['test_id']])['title'] ?? '';
        respond(['joined' => true, 'session_id' => $sess['id'], 'test_title' => $title, 'status' => $sess['status']]);
    }

    // ── POST /live/{code}/answer — student submits answer ─────────
    if ($method === 'POST' && $code && $action === 'answer') {
        $sess = DB::fetchOne('SELECT * FROM live_sessions WHERE room_code=?', [$code]);
        if (!$sess) err('Room not found', 404);
        if ($sess['status'] !== 'active') err('Quiz is not active');
        $uid     = $auth['user_id'];
        $qIdx    = (int)($body['q_index'] ?? $sess['current_q']);
        $selected = strtoupper(trim($body['selected'] ?? ''));
        if (!$selected) err('selected option required');

        // Ignore if already answered this question
        $existing = DB::fetchOne('SELECT id FROM live_answers WHERE session_id=? AND student_id=? AND q_index=?', [$sess['id'], $uid, $qIdx]);
        if ($existing) respond(['already_answered' => true]);

        // Look up correct answer
        $qRow = DB::fetchOne(
            'SELECT q.id, q.marks FROM test_questions tq JOIN questions q ON q.id=tq.question_id WHERE tq.test_id=? ORDER BY tq.sort_order LIMIT 1 OFFSET ' . $qIdx,
            [$sess['test_id']]
        );
        $isCorrect = false; $marks = 0;
        if ($qRow) {
            $correct = DB::fetchOne('SELECT option_label FROM question_options WHERE question_id=? AND is_correct=1', [$qRow['id']]);
            $isCorrect = $correct && strtoupper($correct['option_label']) === $selected;
            $marks = $isCorrect ? (int)($qRow['marks'] ?? 1) : 0;
        }

        DB::execute(
            'INSERT IGNORE INTO live_answers (session_id, student_id, q_index, selected_opt, is_correct, marks_awarded) VALUES (?,?,?,?,?,?)',
            [$sess['id'], $uid, $qIdx, $selected, $isCorrect ? 1 : 0, $marks]
        );
        DB::execute(
            'UPDATE live_participants SET score=score+?, last_q_answered=? WHERE session_id=? AND student_id=?',
            [$marks, $qIdx, $sess['id'], $uid]
        );
        respond(['is_correct' => $isCorrect, 'marks' => $marks]);
    }

    // ── PATCH /live/{code}/start — teacher releases Q0 ────────────
    if ($method === 'PATCH' && $code && $action === 'start') {
        require_role($auth, 'teacher', 'admin');
        $sess = DB::fetchOne('SELECT * FROM live_sessions WHERE room_code=?', [$code]);
        if (!$sess) err('Room not found', 404);
        if ($sess['teacher_id'] != $auth['user_id']) err('Forbidden', 403);
        DB::execute('UPDATE live_sessions SET status="active", current_q=0, started_at=NOW() WHERE id=?', [$sess['id']]);
        respond(['status' => 'active', 'current_q' => 0]);
    }

    // ── PATCH /live/{code}/next — advance question ────────────────
    if ($method === 'PATCH' && $code && $action === 'next') {
        require_role($auth, 'teacher', 'admin');
        $sess = DB::fetchOne('SELECT * FROM live_sessions WHERE room_code=?', [$code]);
        if (!$sess) err('Room not found', 404);
        if ($sess['teacher_id'] != $auth['user_id']) err('Forbidden', 403);
        $totalQ = (int)(DB::fetchOne('SELECT COUNT(*) AS n FROM test_questions WHERE test_id=?', [$sess['test_id']])['n'] ?? 0);
        $nextQ  = (int)$sess['current_q'] + 1;
        if ($nextQ >= $totalQ) {
            DB::execute('UPDATE live_sessions SET status="ended", ended_at=NOW() WHERE id=?', [$sess['id']]);
            respond(['status' => 'ended', 'current_q' => (int)$sess['current_q']]);
        } else {
            DB::execute('UPDATE live_sessions SET current_q=? WHERE id=?', [$nextQ, $sess['id']]);
            respond(['status' => 'active', 'current_q' => $nextQ]);
        }
    }

    // ── PATCH /live/{code}/end — teacher force-ends ───────────────
    if ($method === 'PATCH' && $code && $action === 'end') {
        require_role($auth, 'teacher', 'admin');
        $sess = DB::fetchOne('SELECT * FROM live_sessions WHERE room_code=?', [$code]);
        if (!$sess) err('Room not found', 404);
        if ($sess['teacher_id'] != $auth['user_id']) err('Forbidden', 403);
        DB::execute('UPDATE live_sessions SET status="ended", ended_at=NOW() WHERE id=?', [$sess['id']]);
        respond(['status' => 'ended']);
    }

    // ── GET /live/{code}/results — final leaderboard ──────────────
    if ($method === 'GET' && $code && $action === 'results') {
        $sess = DB::fetchOne(
            'SELECT ls.*, t.title AS test_title FROM live_sessions ls JOIN tests t ON t.id=ls.test_id WHERE ls.room_code=?',
            [$code]
        );
        if (!$sess) err('Room not found', 404);
        $lb = DB::fetchAll(
            'SELECT lp.student_id, lp.score, u.first_name, u.last_name, u.avatar_color,
                    (SELECT COUNT(*) FROM live_answers WHERE session_id=lp.session_id AND student_id=lp.student_id AND is_correct=1) AS correct_count,
                    (SELECT COUNT(*) FROM live_answers WHERE session_id=lp.session_id AND student_id=lp.student_id) AS answered_count
             FROM live_participants lp JOIN users u ON u.id=lp.student_id
             WHERE lp.session_id=? ORDER BY lp.score DESC',
            [$sess['id']]
        );
        respond(['session' => $sess, 'leaderboard' => $lb]);
    }

    err('Live endpoint not found', 404);
}

function ensureLiveTables(): void {
    DB::execute("CREATE TABLE IF NOT EXISTS live_sessions (
        id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        test_id     INT UNSIGNED NOT NULL,
        room_code   VARCHAR(8) UNIQUE NOT NULL,
        teacher_id  INT UNSIGNED NOT NULL,
        school_id   INT UNSIGNED NOT NULL,
        status      ENUM('waiting','active','ended') DEFAULT 'waiting',
        current_q   SMALLINT DEFAULT -1,
        started_at  TIMESTAMP NULL,
        ended_at    TIMESTAMP NULL,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_room (room_code),
        INDEX idx_teacher (teacher_id)
    ) ENGINE=InnoDB");
    DB::execute("CREATE TABLE IF NOT EXISTS live_participants (
        session_id  INT UNSIGNED NOT NULL,
        student_id  INT UNSIGNED NOT NULL,
        score       INT UNSIGNED DEFAULT 0,
        last_q_answered SMALLINT DEFAULT -1,
        joined_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (session_id, student_id)
    ) ENGINE=InnoDB");
    DB::execute("CREATE TABLE IF NOT EXISTS live_answers (
        id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        session_id   INT UNSIGNED NOT NULL,
        student_id   INT UNSIGNED NOT NULL,
        q_index      SMALLINT UNSIGNED NOT NULL,
        selected_opt CHAR(1),
        is_correct   TINYINT(1) DEFAULT 0,
        marks_awarded DECIMAL(5,2) DEFAULT 0,
        answered_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_ans (session_id, student_id, q_index),
        INDEX idx_session_q (session_id, q_index)
    ) ENGINE=InnoDB");
}

// ══════════════════════════════════════════════════════════════
// TEACHERS  (admin management)
// GET    /teachers         — list all teachers in school
// POST   /teachers         — create teacher account
// PATCH  /teachers/{id}    — update teacher
// DELETE /teachers/{id}    — deactivate teacher
// ══════════════════════════════════════════════════════════════
function handleTeachers(string $method, ?int $id, ?string $sub, array $body): void {
    $auth = require_auth();
    require_role($auth, 'admin');
    ensureTeacherSubjectSchema();

    // ── Subject sub-routes ────────────────────────────────────

    // GET /teachers/{id}/subjects
    if ($method === 'GET' && $id && $sub === 'subjects') {
        respond(DB::fetchAll(
            "SELECT ts.subject_id, s.name, s.short_name, s.category
             FROM teacher_subjects ts
             JOIN subjects s ON s.id = ts.subject_id
             WHERE ts.teacher_id = ?
             ORDER BY s.category, s.name",
            [$id]
        ));
    }

    // POST /teachers/{id}/subjects  (body: subject_id)
    if ($method === 'POST' && $id && $sub === 'subjects') {
        $subjectId = (int)($body['subject_id'] ?? 0);
        if (!$subjectId) err('subject_id required');
        DB::execute(
            'INSERT IGNORE INTO teacher_subjects (teacher_id, subject_id, assigned_by) VALUES (?,?,?)',
            [$id, $subjectId, $auth['user_id']]
        );
        respond(['message' => 'Subject assigned to teacher'], 201);
    }

    // DELETE /teachers/{id}/subjects  (body: subject_id)
    if ($method === 'DELETE' && $id && $sub === 'subjects') {
        $subjectId = (int)($body['subject_id'] ?? 0);
        if (!$subjectId) err('subject_id required');
        DB::execute('DELETE FROM teacher_subjects WHERE teacher_id=? AND subject_id=?', [$id, $subjectId]);
        respond(['message' => 'Subject removed from teacher']);
    }

    // ─────────────────────────────────────────────────────────

    if ($method === 'GET' && !$id) {
        $teachers = DB::fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.email, u.avatar_color, u.last_login,
                    u.department_id,
                    d.name AS department_name,
                    (SELECT COUNT(DISTINCT ct.class_id) FROM class_teachers ct WHERE ct.teacher_id=u.id) AS class_count,
                    (SELECT COUNT(DISTINCT t.id) FROM tests t WHERE t.creator_id=u.id) AS test_count,
                    (SELECT GROUP_CONCAT(DISTINCT c2.name ORDER BY c2.name SEPARATOR ', ')
                     FROM class_teachers ct2 JOIN classes c2 ON c2.id=ct2.class_id WHERE ct2.teacher_id=u.id) AS class_names
             FROM users u
             LEFT JOIN departments d ON d.id=u.department_id
             WHERE u.school_id=? AND u.role='teacher' AND u.is_active=1
             ORDER BY u.last_name, u.first_name",
            [$auth['school_id']]
        );
        // Attach assigned subjects to each teacher
        foreach ($teachers as &$t) {
            $t['subjects'] = DB::fetchAll(
                "SELECT ts.subject_id, s.name, s.short_name, s.category
                 FROM teacher_subjects ts JOIN subjects s ON s.id=ts.subject_id
                 WHERE ts.teacher_id=? ORDER BY s.category, s.name",
                [$t['id']]
            );
        }
        respond($teachers);
    }

    if ($method === 'POST') {
        ['first_name'=>$fn,'last_name'=>$ln,'email'=>$em,'password'=>$pw]
            = need($body,'first_name','last_name','email','password');
        if (!filter_var($em, FILTER_VALIDATE_EMAIL)) err('Invalid email address');
        if (strlen($pw) < 6) err('Password must be at least 6 characters');
        if (DB::fetchOne('SELECT id FROM users WHERE email=?',[strtolower(trim($em))]))
            err('Email already registered', 409);
        $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost'=>12]);
        $uid  = DB::insert(
            'INSERT INTO users (school_id,role,first_name,last_name,email,password_hash,avatar_color,must_change_password)
             VALUES (?,?,?,?,?,?,?,1)',
            [$auth['school_id'],'teacher',trim($fn),trim($ln),strtolower(trim($em)),$hash,
             $body['avatar_color'] ?? '#C47D0E']
        );
        respond(['user_id'=>$uid,'message'=>'Teacher created'],201);
    }

    if ($method === 'PATCH' && $id) {
        $allowed = ['first_name','last_name','email','avatar_color','department_id'];
        $sets=[]; $params=[];
        foreach ($allowed as $f) {
            if (array_key_exists($f,$body)) { $sets[]="$f=?"; $params[]=$body[$f]; }
        }
        if (!empty($body['password']) && strlen($body['password']) >= 6) {
            $sets[]    = 'password_hash=?';
            $params[]  = password_hash($body['password'], PASSWORD_BCRYPT, ['cost'=>12]);
        }
        if ($sets) {
            $params[]=$id; $params[]=$auth['school_id'];
            DB::execute('UPDATE users SET '.implode(',',$sets).' WHERE id=? AND school_id=? AND role="teacher"',$params);
        }
        respond(['message'=>'Teacher updated']);
    }

    if ($method === 'DELETE' && $id) {
        DB::execute('UPDATE users SET is_active=0 WHERE id=? AND school_id=? AND role="teacher"',[$id,$auth['school_id']]);
        respond(['message'=>'Teacher deactivated']);
    }

    err('Teachers endpoint error', 404);
}

// ══════════════════════════════════════════════════════════════
// BULK USER IMPORT  (students or teachers from CSV)
// POST /users-bulk   body: { role: "student"|"teacher", users: [...] }
// ══════════════════════════════════════════════════════════════
function handleUsersBulk(string $method, array $body): void {
    if ($method !== 'POST') err('Method not allowed', 405);
    $auth = require_auth();
    require_role($auth, 'admin');
    ensureTeacherSubjectSchema();
    ensureClassSubjectSchema();

    $role  = $body['role'] ?? 'student';
    $users = $body['users'] ?? [];
    if (!in_array($role, ['student','teacher'], true)) err('Invalid role');
    if (!is_array($users) || empty($users)) err('No users provided');

    $sid      = (int)$auth['school_id'];
    $imported = 0;
    $skipped  = [];
    $warnings = [];

    // Top-level class assignment (from the dropdown — applies to all student rows)
    $bulkClassId   = isset($body['class_id']) ? (int)$body['class_id'] : null;
    $bulkClassName = null;
    if ($bulkClassId) {
        $bc = DB::fetchOne('SELECT name FROM classes WHERE id=? AND school_id=?', [$bulkClassId, $sid]);
        $bulkClassName = $bc ? $bc['name'] : null;
        if (!$bulkClassName) $bulkClassId = null; // invalid ID passed
    }

    DB::beginTransaction();
    try {
        foreach ($users as $i => $u) {
            $fn = trim($u['first_name'] ?? $u['firstname'] ?? $u['first'] ?? '');
            $ln = trim($u['last_name']  ?? $u['lastname']  ?? $u['surname'] ?? $u['last'] ?? '');
            $em = strtolower(trim($u['email'] ?? $u['email_address'] ?? ''));
            $pw = trim($u['password'] ?? 'changeme123');

            if (!$fn || !$ln || !$em) {
                $skipped[] = "Row ".($i+1).": missing name or email"; continue;
            }
            if (!filter_var($em, FILTER_VALIDATE_EMAIL)) {
                $skipped[] = "Row ".($i+1).": invalid email ($em)"; continue;
            }
            if (DB::fetchOne('SELECT id FROM users WHERE email=?', [$em])) {
                $skipped[] = "Row ".($i+1).": $em already exists"; continue;
            }

            $hash = password_hash(strlen($pw) >= 6 ? $pw : 'changeme123', PASSWORD_BCRYPT, ['cost'=>10]);

            if ($role === 'student') {
                // Use bulk class (from dropdown) — ignore per-row class_name column
                $classId   = $bulkClassId;
                $className = $bulkClassName;

                $uid = DB::insert(
                    'INSERT INTO users (school_id,role,first_name,last_name,email,password_hash,class_name,must_change_password)
                     VALUES (?,?,?,?,?,?,?,1)',
                    [$sid, 'student', $fn, $ln, $em, $hash, $className]
                );
                DB::execute('INSERT INTO streaks (student_id) VALUES (?)', [$uid]);
                if ($classId) {
                    DB::execute('INSERT IGNORE INTO class_students (class_id,student_id) VALUES (?,?)',
                        [$classId, $uid]);
                }

            } else { // teacher
                $deptName    = trim($u['department'] ?? $u['department_name'] ?? '');
                $subjectsRaw = trim($u['subjects']   ?? $u['subject']        ?? '');

                // Resolve department
                $deptId = null;
                if ($deptName) {
                    $dept = DB::fetchOne('SELECT id FROM departments WHERE school_id=? AND name=?', [$sid, $deptName]);
                    if ($dept) $deptId = (int)$dept['id'];
                    else $warnings[] = "Row ".($i+1).": department \"$deptName\" not found — assigned without department";
                }

                $uid = DB::insert(
                    'INSERT INTO users (school_id,role,first_name,last_name,email,password_hash,department_id,avatar_color,must_change_password)
                     VALUES (?,?,?,?,?,?,?,?,1)',
                    [$sid, 'teacher', $fn, $ln, $em, $hash, $deptId, '#C47D0E']
                );

                // Assign subjects (comma-separated names or short names)
                if ($subjectsRaw) {
                    $names = array_filter(array_map('trim', explode(',', $subjectsRaw)));
                    foreach ($names as $sn) {
                        $subj = DB::fetchOne(
                            'SELECT id FROM subjects WHERE (school_id IS NULL OR school_id=?) AND (name=? OR short_name=?) LIMIT 1',
                            [$sid, $sn, $sn]
                        );
                        if ($subj) {
                            DB::execute(
                                'INSERT IGNORE INTO teacher_subjects (teacher_id,subject_id,assigned_by) VALUES (?,?,?)',
                                [$uid, (int)$subj['id'], (int)$auth['user_id']]
                            );
                        } else {
                            $warnings[] = "Row ".($i+1).": subject \"$sn\" not found — skipped";
                        }
                    }
                }
            }

            $imported++;
        }
        DB::commit();
    } catch (Throwable $e) {
        DB::rollback();
        err('Import failed: '.$e->getMessage(), 500);
    }

    respond([
        'imported' => $imported,
        'skipped'  => count($skipped),
        'warnings' => count($warnings),
        'errors'   => array_merge($skipped, $warnings),
        'message'  => "$imported user(s) imported" .
                      (count($skipped)  ? ", ".count($skipped)." skipped"   : "") .
                      (count($warnings) ? ", ".count($warnings)." warnings" : ""),
    ]);
}

// ══════════════════════════════════════════════════════════════
// DEPARTMENTS
// GET    /departments           — list departments
// POST   /departments           — create department
// PATCH  /departments/{id}      — update
// DELETE /departments/{id}      — delete
// POST   /departments/{id}/teachers  — assign teacher to dept
// DELETE /departments/{id}/teachers/{teacherId} — remove teacher
// ══════════════════════════════════════════════════════════════
function handleDepartments(string $method, ?int $id, array $parts, array $body): void {
    $auth   = require_auth();
    require_role($auth, 'admin');
    $sid    = $auth['school_id'];
    $action = $parts[2] ?? null;

    ensureDeptSchema();

    if ($method === 'GET' && !$id) {
        $depts = DB::fetchAll(
            "SELECT d.*,
                    CONCAT(h.first_name,' ',h.last_name) AS head_name,
                    (SELECT COUNT(*) FROM users WHERE department_id=d.id AND role='teacher' AND is_active=1) AS teacher_count
             FROM departments d
             LEFT JOIN users h ON h.id=d.head_teacher_id
             WHERE d.school_id=? ORDER BY d.name",
            [$sid]
        );
        // Attach teacher list to each dept
        foreach ($depts as &$dept) {
            $dept['teachers'] = DB::fetchAll(
                "SELECT u.id, u.first_name, u.last_name, u.email, u.avatar_color,
                        (SELECT COUNT(DISTINCT ct.class_id) FROM class_teachers ct WHERE ct.teacher_id=u.id) AS class_count
                 FROM users u WHERE u.department_id=? AND u.role='teacher' AND u.is_active=1
                 ORDER BY u.last_name",
                [$dept['id']]
            );
        }
        respond($depts);
    }

    if ($method === 'POST' && !$id) {
        $d   = need($body, 'name');
        $did = DB::insert(
            'INSERT INTO departments (school_id, name, description, head_teacher_id) VALUES (?,?,?,?)',
            [$sid, trim($d['name']), $body['description'] ?? null, $body['head_teacher_id'] ?? null]
        );
        respond(['dept_id' => $did, 'message' => 'Department created'], 201);
    }

    if ($method === 'PATCH' && $id) {
        $allowed = ['name', 'description', 'head_teacher_id'];
        $sets=[]; $params=[];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $body)) { $sets[]="$f=?"; $params[]=$body[$f]; }
        }
        if ($sets) {
            $params[]=$id; $params[]=$sid;
            DB::execute('UPDATE departments SET '.implode(',',$sets).' WHERE id=? AND school_id=?', $params);
        }
        respond(['message' => 'Department updated']);
    }

    if ($method === 'DELETE' && $id && !$action) {
        DB::execute('UPDATE users SET department_id=NULL WHERE department_id=? AND school_id=?', [$id, $sid]);
        DB::execute('DELETE FROM departments WHERE id=? AND school_id=?', [$id, $sid]);
        respond(['message' => 'Department deleted']);
    }

    // POST /departments/{id}/teachers — move a teacher into this department
    if ($method === 'POST' && $id && $action === 'teachers') {
        $tid = (int)($body['teacher_id'] ?? 0);
        if (!$tid) err('teacher_id required');
        DB::execute('UPDATE users SET department_id=? WHERE id=? AND school_id=? AND role="teacher"', [$id, $tid, $sid]);
        respond(['message' => 'Teacher assigned to department']);
    }

    // DELETE /departments/{id}/teachers/{teacherId}
    if ($method === 'DELETE' && $id && $action === 'teachers' && isset($parts[3])) {
        $tid = (int)$parts[3];
        DB::execute('UPDATE users SET department_id=NULL WHERE id=? AND school_id=? AND department_id=?', [$tid, $sid, $id]);
        respond(['message' => 'Teacher removed from department']);
    }

    err('Departments endpoint error', 404);
}

function ensureDeptSchema(): void {
    try {
        DB::execute("CREATE TABLE IF NOT EXISTS departments (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            school_id        INT UNSIGNED NOT NULL,
            name             VARCHAR(200) NOT NULL,
            description      TEXT,
            head_teacher_id  INT UNSIGNED NULL,
            created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_dept_name (school_id, name)
        ) ENGINE=InnoDB");
        DB::execute("CREATE TABLE IF NOT EXISTS class_teachers (
            class_id     INT UNSIGNED NOT NULL,
            teacher_id   INT UNSIGNED NOT NULL,
            subject      VARCHAR(100) DEFAULT 'ICT',
            assigned_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (class_id, teacher_id)
        ) ENGINE=InnoDB");
        DB::execute("ALTER TABLE users ADD COLUMN IF NOT EXISTS department_id INT UNSIGNED NULL");
    } catch (Throwable $e) { /* columns/tables may already exist */ }
}

// ══════════════════════════════════════════════════════════════
// SUBJECTS
// GET    /subjects                — platform list + school-enabled flags
// POST   /subjects                — admin adds custom subject
// PATCH  /subjects/{id}           — admin edits custom subject
// DELETE /subjects/{id}           — admin deletes custom subject
// POST   /subjects/{id}/toggle    — enable/disable platform subject for school
// ══════════════════════════════════════════════════════════════
function handleSubjects(string $method, ?int $id, ?string $sub, array $body): void {
    $auth = require_auth();
    ensureSubjectSchema();
    $sid = $auth['school_id'];

    // GET /subjects — platform subjects with enabled flag + school's custom subjects
    if ($method === 'GET' && !$id) {
        $platform = DB::fetchAll(
            "SELECT s.*,
                    IF(ss.school_id IS NOT NULL, 1, 0) AS is_enabled
             FROM subjects s
             LEFT JOIN school_subjects ss ON ss.subject_id=s.id AND ss.school_id=?
             WHERE s.school_id IS NULL
             ORDER BY s.category, s.sort_order, s.name",
            [$sid]
        );
        $custom = DB::fetchAll(
            "SELECT s.*, 1 AS is_enabled FROM subjects s
             WHERE s.school_id=?
             ORDER BY s.category, s.name",
            [$sid]
        );
        // For teachers, also include which subjects they personally teach
        $mySubjectIds = [];
        if ($auth['role'] === 'teacher') {
            ensureTeacherSubjectSchema();
            $mySubjectIds = array_column(DB::fetchAll(
                'SELECT subject_id FROM teacher_subjects WHERE teacher_id=?',
                [$auth['user_id']]
            ), 'subject_id');
        }
        respond(['platform' => $platform, 'custom' => $custom, 'my_subject_ids' => $mySubjectIds]);
    }

    // GET /subjects/{id}/students — all students enrolled in this subject + their performance
    if ($method === 'GET' && $id && $sub === 'students') {
        ensureClassSubjectSchema();
        $rows = DB::fetchAll(
            "SELECT DISTINCT u.id, u.first_name, u.last_name, u.avatar_color, u.class_name,
                    COUNT(DISTINCT a.id)  AS tests_done,
                    ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                    MAX(ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),1))  AS best_pct
             FROM class_students cst
             JOIN class_subjects csubj ON csubj.class_id=cst.class_id AND csubj.subject_id=?
             LEFT JOIN student_class_groups scg
                    ON scg.student_id=cst.student_id AND scg.class_id=cst.class_id
             JOIN users u ON u.id=cst.student_id AND u.is_active=1 AND u.school_id=?
             LEFT JOIN attempts a ON a.student_id=u.id AND a.status IN ('submitted','marked')
                    AND a.test_id IN (SELECT id FROM tests WHERE subject_id=?)
             WHERE (csubj.group_tag IS NULL OR csubj.group_tag=scg.group_tag)
             GROUP BY u.id, u.first_name, u.last_name, u.avatar_color, u.class_name
             ORDER BY u.class_name, u.last_name",
            [$id, $sid, $id]
        );
        respond($rows);
    }

    // GET /subjects/{id}/tests — tests and quizzes for this subject
    if ($method === 'GET' && $id && $sub === 'tests') {
        $rows = DB::fetchAll(
            "SELECT t.id, t.title, t.type, t.status, t.created_at, t.time_limit_min,
                    (SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) AS question_count,
                    COUNT(DISTINCT a.id) AS attempt_count,
                    ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                    CONCAT(u.first_name,' ',u.last_name) AS creator_name
             FROM tests t
             LEFT JOIN attempts a ON a.test_id=t.id AND a.status IN ('submitted','marked')
             LEFT JOIN users u ON u.id=t.creator_id
             WHERE t.subject_id=? AND t.school_id=?
             GROUP BY t.id, t.title, t.type, t.status, t.created_at, t.time_limit_min, u.first_name, u.last_name
             ORDER BY t.created_at DESC",
            [$id, $sid]
        );
        respond($rows);
    }

    // GET /subjects/{id}/student-perf?student_id=X — one student's performance in this subject
    if ($method === 'GET' && $id && $sub === 'student-perf') {
        $studentId = (int)($_GET['student_id'] ?? 0);
        if (!$studentId) err('student_id required');
        $scores = DB::fetchAll(
            "SELECT t.id, t.title, t.type,
                    ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),1) AS pct_score,
                    a.score_auto + a.score_manual AS raw_score, a.max_score, a.submitted_at
             FROM attempts a
             JOIN tests t ON t.id=a.test_id
             WHERE a.student_id=? AND t.subject_id=? AND a.status IN ('submitted','marked')
             ORDER BY a.submitted_at DESC",
            [$studentId, $id]
        );
        $topics = DB::fetchAll(
            "SELECT q.sub_strand, q.topic,
                    COUNT(*) AS total,
                    SUM(an.is_correct) AS correct,
                    ROUND(AVG(an.is_correct)*100, 1) AS accuracy
             FROM answers an
             JOIN questions q ON q.id=an.question_id
             JOIN attempts a  ON a.id=an.attempt_id
             JOIN tests t     ON t.id=a.test_id
             WHERE a.student_id=? AND t.subject_id=? AND an.marks_awarded IS NOT NULL
             GROUP BY q.sub_strand, q.topic
             ORDER BY accuracy ASC
             LIMIT 10",
            [$studentId, $id]
        );
        respond(['scores' => $scores, 'topics' => $topics]);
    }

    // ── GET /subjects/{id}/strands — list strands with sub-strands tree ──
    if ($method === 'GET' && $id && $sub === 'strands') {
        ensureCurriculumSchema();
        $strands = DB::fetchAll(
            'SELECT id, strand_code, strand_label, description, sort_order, is_active
             FROM subject_strands
             WHERE school_id=? AND subject_id=? AND is_active=1
             ORDER BY sort_order, strand_code',
            [$sid, $id]
        );
        foreach ($strands as &$st) {
            $st['sub_strands'] = DB::fetchAll(
                'SELECT id, sub_strand_code, sub_strand_label, description, sort_order, is_active
                 FROM subject_sub_strands
                 WHERE strand_id=? AND is_active=1
                 ORDER BY sort_order, sub_strand_code',
                [$st['id']]
            );
            foreach ($st['sub_strands'] as &$ss) {
                $ss['topics'] = DB::fetchAll(
                    'SELECT id, topic_code, topic_label, description, sort_order, is_active
                     FROM subject_topics
                     WHERE sub_strand_id=? AND is_active=1
                     ORDER BY sort_order, topic_code',
                    [$ss['id']]
                );
            }
        }
        respond($strands);
    }

    // ── POST /subjects/{id}/strands — create a strand ────────────
    if ($method === 'POST' && $id && $sub === 'strands') {
        require_role($auth, 'admin', 'teacher');
        ensureCurriculumSchema();
        $d = need($body, 'strand_code', 'strand_label');
        $newId = DB::insert(
            'INSERT INTO subject_strands (school_id, subject_id, strand_code, strand_label, description, sort_order)
             VALUES (?,?,?,?,?,?)',
            [$sid, $id, trim($d['strand_code']), trim($d['strand_label']),
             trim($body['description'] ?? ''), (int)($body['sort_order'] ?? 0)]
        );
        logActivity($auth, 'strand.created', 'strand', (int)$newId, trim($d['strand_label']), "Added strand to subject #$id");
        respond(['id' => $newId, 'message' => 'Strand created'], 201);
    }

    // POST /subjects — admin adds a custom subject
    if ($method === 'POST' && !$id) {
        require_role($auth, 'admin');
        $d = need($body, 'name');
        $newId = DB::insert(
            'INSERT INTO subjects (school_id, name, short_name, category) VALUES (?,?,?,?)',
            [$sid, trim($d['name']),
             trim($body['short_name'] ?? ''),
             trim($body['category']  ?? 'General')]
        );
        respond(['subject_id' => $newId, 'message' => 'Subject added'], 201);
    }

    // PATCH /subjects/{id} — admin edits own custom subject
    if ($method === 'PATCH' && $id && $sub !== 'toggle') {
        require_role($auth, 'admin');
        $existing = DB::fetchOne('SELECT school_id FROM subjects WHERE id=?', [$id]);
        if (!$existing || (int)$existing['school_id'] !== (int)$sid)
            err('You can only edit subjects you added', 403);
        $allowed = ['name', 'short_name', 'category'];
        $sets=[]; $params=[];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $body)) { $sets[]="$f=?"; $params[]=$body[$f]; }
        }
        if ($sets) { $params[]=$id; DB::execute('UPDATE subjects SET '.implode(',',$sets).' WHERE id=?', $params); }
        respond(['message' => 'Subject updated']);
    }

    // DELETE /subjects/{id} — admin deletes own custom subject
    if ($method === 'DELETE' && $id && $sub !== 'toggle') {
        require_role($auth, 'admin');
        $existing = DB::fetchOne('SELECT school_id FROM subjects WHERE id=?', [$id]);
        if (!$existing || (int)$existing['school_id'] !== (int)$sid)
            err('You can only delete subjects you added', 403);
        DB::execute('DELETE FROM subjects WHERE id=? AND school_id=?', [$id, $sid]);
        respond(['message' => 'Subject deleted']);
    }

    // POST /subjects/{id}/toggle — enable or disable a platform subject for this school
    if ($method === 'POST' && $id && $sub === 'toggle') {
        require_role($auth, 'admin');
        $already = DB::fetchOne(
            'SELECT school_id FROM school_subjects WHERE school_id=? AND subject_id=?',
            [$sid, $id]
        );
        if ($already) {
            DB::execute('DELETE FROM school_subjects WHERE school_id=? AND subject_id=?', [$sid, $id]);
            respond(['enabled' => false, 'message' => 'Subject disabled']);
        } else {
            DB::execute('INSERT IGNORE INTO school_subjects (school_id, subject_id) VALUES (?,?)', [$sid, $id]);
            respond(['enabled' => true, 'message' => 'Subject enabled']);
        }
    }

    err('Subjects endpoint error', 404);
}

// ══════════════════════════════════════════════════════════════
// CURRICULUM — full tree + strand/sub-strand/topic CRUD
// GET /curriculum?subject_id=N  — full Subject→Strand→Sub-strand→Topic tree
// ══════════════════════════════════════════════════════════════
function handleCurriculum(string $method, array $body): void {
    $auth = require_auth();
    require_role($auth, 'admin', 'teacher');
    $sid = $auth['school_id'];
    ensureCurriculumSchema();

    if ($method === 'GET') {
        $subjectId = (int)($_GET['subject_id'] ?? 0);
        if (!$subjectId) err('subject_id required');
        $strands = DB::fetchAll(
            'SELECT id, strand_code, strand_label, description, sort_order, is_active
             FROM subject_strands
             WHERE school_id=? AND subject_id=?
             ORDER BY sort_order, strand_code',
            [$sid, $subjectId]
        );
        foreach ($strands as &$st) {
            $st['sub_strands'] = DB::fetchAll(
                'SELECT id, sub_strand_code, sub_strand_label, description, sort_order, is_active
                 FROM subject_sub_strands WHERE strand_id=? ORDER BY sort_order, sub_strand_code',
                [$st['id']]
            );
            foreach ($st['sub_strands'] as &$ss) {
                $ss['topics'] = DB::fetchAll(
                    'SELECT id, topic_code, topic_label, description, sort_order, is_active
                     FROM subject_topics WHERE sub_strand_id=? ORDER BY sort_order, topic_code',
                    [$ss['id']]
                );
            }
        }
        respond(['strands' => $strands]);
    }
    err('Method not allowed', 405);
}

// ── Strands resource: PUT/DELETE /strands/{id}
//                     GET/POST /strands/{id}/sub-strands
function handleStrandsResource(string $method, ?int $id, ?string $sub, array $body): void {
    $auth = require_auth();
    require_role($auth, 'admin', 'teacher');
    $sid = $auth['school_id'];
    ensureCurriculumSchema();

    // GET /strands/{id}/sub-strands — list sub-strands for a strand
    if ($method === 'GET' && $id && $sub === 'sub-strands') {
        $rows = DB::fetchAll(
            'SELECT id, sub_strand_code, sub_strand_label, description, sort_order, is_active
             FROM subject_sub_strands WHERE strand_id=? ORDER BY sort_order, sub_strand_code',
            [$id]
        );
        foreach ($rows as &$ss) {
            $ss['topics'] = DB::fetchAll(
                'SELECT id, topic_code, topic_label, description, sort_order, is_active
                 FROM subject_topics WHERE sub_strand_id=? ORDER BY sort_order',
                [$ss['id']]
            );
        }
        respond($rows);
    }

    // POST /strands/{id}/sub-strands — create sub-strand
    if ($method === 'POST' && $id && $sub === 'sub-strands') {
        $d = need($body, 'sub_strand_label');
        $newId = DB::insert(
            'INSERT INTO subject_sub_strands (strand_id, sub_strand_code, sub_strand_label, description, sort_order)
             VALUES (?,?,?,?,?)',
            [$id, trim($body['sub_strand_code'] ?? ''), trim($d['sub_strand_label']),
             trim($body['description'] ?? ''), (int)($body['sort_order'] ?? 0)]
        );
        logActivity($auth, 'sub_strand.created', 'sub_strand', (int)$newId, trim($d['sub_strand_label']), "Added sub-strand to strand #$id");
        respond(['id' => $newId, 'message' => 'Sub-strand created'], 201);
    }

    // PUT /strands/{id} — update strand
    if ($method === 'PUT' && $id && !$sub) {
        $sets = []; $params = [];
        foreach (['strand_code','strand_label','description','sort_order','is_active'] as $f) {
            if (array_key_exists($f, $body)) { $sets[] = "$f=?"; $params[] = $body[$f]; }
        }
        if ($sets) { $params[] = $id; $params[] = $sid;
            DB::execute('UPDATE subject_strands SET '.implode(',',$sets).' WHERE id=? AND school_id=?', $params); }
        respond(['updated' => true]);
    }

    // DELETE /strands/{id} — delete strand (cascades to sub-strands and topics)
    if ($method === 'DELETE' && $id && !$sub) {
        $st = DB::fetchOne('SELECT strand_label FROM subject_strands WHERE id=?', [$id]);
        DB::execute('DELETE FROM subject_strands WHERE id=? AND school_id=?', [$id, $sid]);
        logActivity($auth, 'strand.deleted', 'strand', (int)$id, $st['strand_label']??'');
        respond(['deleted' => true]);
    }

    err('Strands endpoint error', 404);
}

// ── Sub-strands resource: PUT/DELETE /sub-strands/{id}
//                          GET/POST /sub-strands/{id}/topics
function handleSubStrandsResource(string $method, ?int $id, ?string $sub, array $body): void {
    $auth = require_auth();
    require_role($auth, 'admin', 'teacher');
    ensureCurriculumSchema();

    // GET /sub-strands/{id}/topics — list topics
    if ($method === 'GET' && $id && $sub === 'topics') {
        $rows = DB::fetchAll(
            'SELECT id, topic_code, topic_label, description, sort_order, is_active
             FROM subject_topics WHERE sub_strand_id=? ORDER BY sort_order, topic_code',
            [$id]
        );
        respond($rows);
    }

    // POST /sub-strands/{id}/topics — create topic
    if ($method === 'POST' && $id && $sub === 'topics') {
        $d = need($body, 'topic_label');
        $newId = DB::insert(
            'INSERT INTO subject_topics (sub_strand_id, topic_code, topic_label, description, sort_order)
             VALUES (?,?,?,?,?)',
            [$id, trim($body['topic_code'] ?? ''), trim($d['topic_label']),
             trim($body['description'] ?? ''), (int)($body['sort_order'] ?? 0)]
        );
        logActivity($auth, 'topic.created', 'topic', (int)$newId, trim($d['topic_label']), "Added topic to sub-strand #$id");
        respond(['id' => $newId, 'message' => 'Topic created'], 201);
    }

    // PUT /sub-strands/{id} — update sub-strand
    if ($method === 'PUT' && $id && !$sub) {
        $sets = []; $params = [];
        foreach (['sub_strand_code','sub_strand_label','description','sort_order','is_active'] as $f) {
            if (array_key_exists($f, $body)) { $sets[] = "$f=?"; $params[] = $body[$f]; }
        }
        if ($sets) { $params[] = $id;
            DB::execute('UPDATE subject_sub_strands SET '.implode(',',$sets).' WHERE id=?', $params); }
        respond(['updated' => true]);
    }

    // DELETE /sub-strands/{id} — delete sub-strand (cascades to topics)
    if ($method === 'DELETE' && $id && !$sub) {
        $ss = DB::fetchOne('SELECT sub_strand_label FROM subject_sub_strands WHERE id=?', [$id]);
        DB::execute('DELETE FROM subject_sub_strands WHERE id=?', [$id]);
        logActivity($auth, 'sub_strand.deleted', 'sub_strand', (int)$id, $ss['sub_strand_label']??'');
        respond(['deleted' => true]);
    }

    err('Sub-strands endpoint error', 404);
}

// ── Topics resource: PUT/DELETE /topics/{id}
function handleTopicsResource(string $method, ?int $id, array $body): void {
    $auth = require_auth();
    require_role($auth, 'admin', 'teacher');
    ensureCurriculumSchema();

    // PUT /topics/{id} — update topic
    if ($method === 'PUT' && $id) {
        $sets = []; $params = [];
        foreach (['topic_code','topic_label','description','sort_order','is_active'] as $f) {
            if (array_key_exists($f, $body)) { $sets[] = "$f=?"; $params[] = $body[$f]; }
        }
        if ($sets) { $params[] = $id;
            DB::execute('UPDATE subject_topics SET '.implode(',',$sets).' WHERE id=?', $params); }
        respond(['updated' => true]);
    }

    // DELETE /topics/{id} — delete topic
    if ($method === 'DELETE' && $id) {
        $tp = DB::fetchOne('SELECT topic_label FROM subject_topics WHERE id=?', [$id]);
        DB::execute('DELETE FROM subject_topics WHERE id=?', [$id]);
        logActivity($auth, 'topic.deleted', 'topic', (int)$id, $tp['topic_label']??'');
        respond(['deleted' => true]);
    }

    err('Topics endpoint error', 404);
}

// Ensure the student has a row in class_students based on their users.class_name.
// Called on every student test/dashboard fetch — cheap because INSERT IGNORE is a no-op
// if the row already exists. Fixes students created before the class_students migration.
function syncStudentClass($studentId, $schoolId): void {
    $studentId = (int)$studentId;
    try {
        $schoolId = (int)$schoolId;
        $u = DB::fetchOne('SELECT class_name FROM users WHERE id=?', [$studentId]);
        if (empty($u['class_name'])) return;
        $cls = DB::fetchOne(
            'SELECT id FROM classes WHERE school_id=? AND name=?',
            [$schoolId, $u['class_name']]
        );
        if ($cls) {
            DB::execute(
                'INSERT IGNORE INTO class_students (class_id, student_id) VALUES (?,?)',
                [(int)$cls['id'], $studentId]
            );
        }
    } catch (Throwable $e) {}
}

// Returns [class_name_string, class_id_int|null] from request body.
// Accepts either class_id (int) or class_name (string).
function resolveStudentClass(array $body, $schoolId): array {
    $schoolId = (int)$schoolId;
    if (!empty($body['class_id'])) {
        $cid = (int)$body['class_id'];
        $cls = DB::fetchOne('SELECT name FROM classes WHERE id=? AND school_id=?', [$cid, $schoolId]);
        return [$cls ? $cls['name'] : null, $cls ? $cid : null];
    }
    if (isset($body['class_name']) && $body['class_name'] !== '') {
        $cn  = trim($body['class_name']);
        $cls = DB::fetchOne('SELECT id FROM classes WHERE school_id=? AND name=?', [$schoolId, $cn]);
        return [$cn, $cls ? (int)$cls['id'] : null];
    }
    return [null, null];
}

// ══════════════════════════════════════════════════════════════
// ACADEMIC PERIODS
// ══════════════════════════════════════════════════════════════
// ══════════════════════════════════════════════════════════════
// SCHOOL SETTINGS
// ══════════════════════════════════════════════════════════════
function handleSchoolSettings(string $method, array $body): void {
    $auth = require_auth();
    require_role($auth, 'admin');
    $sid = $auth['school_id'];
    // Ensure schools table has extended columns
    try {
        DB::execute("ALTER TABLE schools ADD COLUMN IF NOT EXISTS region    VARCHAR(100) NULL");
        DB::execute("ALTER TABLE schools ADD COLUMN IF NOT EXISTS ges_id    VARCHAR(50)  NULL");
        DB::execute("ALTER TABLE schools ADD COLUMN IF NOT EXISTS phone     VARCHAR(30)  NULL");
        DB::execute("ALTER TABLE schools ADD COLUMN IF NOT EXISTS address   VARCHAR(255) NULL");
        DB::execute("ALTER TABLE schools ADD COLUMN IF NOT EXISTS email     VARCHAR(150) NULL");
        DB::execute("ALTER TABLE schools ADD COLUMN IF NOT EXISTS logo_url  VARCHAR(500) NULL");
    } catch (Throwable $e) {}

    if ($method === 'GET') {
        $school = DB::fetchOne('SELECT id, name, region, ges_id, phone, address, email, logo_url FROM schools WHERE id=?', [$sid]);
        if (!$school) err('School not found', 404);
        respond($school);
    }

    if ($method === 'PATCH') {
        $sets=[]; $params=[];
        $allowed = ['name','region','ges_id','phone','address','email','logo_url'];
        foreach ($allowed as $f) {
            if (array_key_exists($f,$body)) { $sets[]="$f=?"; $params[]=trim($body[$f]); }
        }
        if ($sets) { $params[]=$sid; DB::execute('UPDATE schools SET '.implode(',',$sets).' WHERE id=?',$params); }
        // Also update school_name in users table if name changed
        if (isset($body['name'])) {
            // school_name is stored in JWT but users.school_name might not exist — safe to ignore
        }
        respond(['message'=>'School settings saved']);
    }
    err('School endpoint error',404);
}

// ══════════════════════════════════════════════════════════════
// GRADE CONFIGURATION  (Ghana GES A1-F9 system)
// ══════════════════════════════════════════════════════════════
function handleGradeConfig(string $method, array $body): void {
    $auth = require_auth();
    $sid  = $auth['school_id'];
    ensureGradeConfigSchema();

    if ($method === 'GET') {
        $grades = DB::fetchAll('SELECT * FROM grade_config WHERE school_id=? ORDER BY sort_order', [$sid]);
        if (empty($grades)) {
            // Return Ghana GES defaults
            $grades = [
                ['grade'=>'A1','label'=>'Excellent',         'min_pct'=>80,'max_pct'=>100,'sort_order'=>1],
                ['grade'=>'B2','label'=>'Very Good',          'min_pct'=>70,'max_pct'=>79, 'sort_order'=>2],
                ['grade'=>'B3','label'=>'Good',               'min_pct'=>60,'max_pct'=>69, 'sort_order'=>3],
                ['grade'=>'C4','label'=>'Credit',             'min_pct'=>55,'max_pct'=>59, 'sort_order'=>4],
                ['grade'=>'C5','label'=>'Credit',             'min_pct'=>50,'max_pct'=>54, 'sort_order'=>5],
                ['grade'=>'C6','label'=>'Credit',             'min_pct'=>45,'max_pct'=>49, 'sort_order'=>6],
                ['grade'=>'D7','label'=>'Pass',               'min_pct'=>40,'max_pct'=>44, 'sort_order'=>7],
                ['grade'=>'E8','label'=>'Pass',               'min_pct'=>35,'max_pct'=>39, 'sort_order'=>8],
                ['grade'=>'F9','label'=>'Fail',               'min_pct'=>0, 'max_pct'=>34, 'sort_order'=>9],
            ];
        }
        respond($grades);
    }

    if ($method === 'POST') {
        require_role($auth,'admin');
        $grades = $body['grades'] ?? [];
        if (empty($grades)) err('grades array required');
        DB::execute('DELETE FROM grade_config WHERE school_id=?', [$sid]);
        foreach ($grades as $i => $g) {
            DB::execute('INSERT INTO grade_config (school_id,grade,label,min_pct,max_pct,sort_order) VALUES (?,?,?,?,?,?)',
                [$sid, $g['grade'], $g['label'], (float)$g['min_pct'], (float)$g['max_pct'], $i+1]);
        }
        respond(['message'=>'Grade boundaries saved']);
    }
    err('Grade config error',404);
}

// ══════════════════════════════════════════════════════════════
// SEMESTER RESULT SHEET
// ══════════════════════════════════════════════════════════════
function handleSemesterResults(string $method, ?string $sub, array $body): void {
    $auth = require_auth();
    require_role($auth,'teacher','admin');
    $sid = $auth['school_id'];
    if ($method !== 'GET') err('GET only',405);

    // ?class_id=N&academic_year=2024/2025&semester=1
    $classId  = (int)($_GET['class_id']      ?? 0);
    $acYear   = trim($_GET['academic_year']  ?? '');
    $semNum   = (int)($_GET['semester']      ?? 0);
    if (!$classId || !$acYear || !$semNum) err('class_id, academic_year and semester required');

    $cls = DB::fetchOne('SELECT id,name,year_group FROM classes WHERE id=? AND school_id=?',[$classId,$sid]);
    if (!$cls) err('Class not found',404);

    // Students in class
    $students = DB::fetchAll(
        'SELECT u.id, u.first_name, u.last_name, u.avatar_color FROM users u
         JOIN class_students cs ON cs.student_id=u.id WHERE cs.class_id=? ORDER BY u.last_name,u.first_name',
        [$classId]
    );

    // Subjects for this class
    $subjects = DB::fetchAll(
        'SELECT DISTINCT s.id,s.name,s.short_name FROM subjects s
         JOIN class_subjects csj ON csj.subject_id=s.id WHERE csj.class_id=? ORDER BY s.name',
        [$classId]
    );

    // All submitted attempts for this class + semester + academic year
    $attempts = DB::fetchAll(
        "SELECT a.student_id, t.subject_id,
                ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                COUNT(DISTINCT a.id) AS test_count
         FROM attempts a
         JOIN tests t ON t.id=a.test_id
         WHERE a.student_id IN (SELECT student_id FROM class_students WHERE class_id=?)
           AND t.semester=? AND t.academic_year=? AND a.status IN ('submitted','marked')
         GROUP BY a.student_id, t.subject_id",
        [$classId, $semNum, $acYear]
    );

    // Build a student→subject→avg map
    $map = [];
    foreach ($attempts as $att) {
        $map[$att['student_id']][$att['subject_id']] = ['avg_pct' => $att['avg_pct'], 'test_count' => $att['test_count']];
    }

    // Grade config
    ensureGradeConfigSchema();
    $gradeConf = DB::fetchAll('SELECT * FROM grade_config WHERE school_id=? ORDER BY sort_order',[$sid]);
    if (empty($gradeConf)) {
        $gradeConf=[
            ['grade'=>'A1','label'=>'Excellent', 'min_pct'=>80,'max_pct'=>100,'sort_order'=>1],
            ['grade'=>'B2','label'=>'Very Good',  'min_pct'=>70,'max_pct'=>79, 'sort_order'=>2],
            ['grade'=>'B3','label'=>'Good',        'min_pct'=>60,'max_pct'=>69, 'sort_order'=>3],
            ['grade'=>'C4','label'=>'Credit',      'min_pct'=>55,'max_pct'=>59, 'sort_order'=>4],
            ['grade'=>'C5','label'=>'Credit',      'min_pct'=>50,'max_pct'=>54, 'sort_order'=>5],
            ['grade'=>'C6','label'=>'Credit',      'min_pct'=>45,'max_pct'=>49, 'sort_order'=>6],
            ['grade'=>'D7','label'=>'Pass',        'min_pct'=>40,'max_pct'=>44, 'sort_order'=>7],
            ['grade'=>'E8','label'=>'Pass',        'min_pct'=>35,'max_pct'=>39, 'sort_order'=>8],
            ['grade'=>'F9','label'=>'Fail',        'min_pct'=>0, 'max_pct'=>34, 'sort_order'=>9],
        ];
    }

    // Build result rows
    $results = [];
    foreach ($students as $stu) {
        $row = ['student_id'=>$stu['id'],'first_name'=>$stu['first_name'],'last_name'=>$stu['last_name'],'avatar_color'=>$stu['avatar_color'],'subjects'=>[],'overall_avg'=>null];
        $sum=0; $cnt=0;
        foreach ($subjects as $subj) {
            $data = $map[$stu['id']][$subj['id']] ?? null;
            $pct  = $data ? (float)$data['avg_pct'] : null;
            $grade = null;
            if ($pct !== null) {
                foreach ($gradeConf as $g) { if ($pct>=(float)$g['min_pct'] && $pct<=(float)$g['max_pct']) { $grade=$g['grade']; break; } }
                $sum += $pct; $cnt++;
            }
            $row['subjects'][] = ['subject_id'=>$subj['id'],'subject_name'=>$subj['name'],'subject_short'=>$subj['short_name'],'avg_pct'=>$pct,'grade'=>$grade,'test_count'=>$data['test_count']??0];
        }
        $row['overall_avg'] = $cnt>0 ? round($sum/$cnt,1) : null;
        $results[] = $row;
    }

    // Sort by overall avg desc for ranking
    usort($results, fn($a,$b) => ($b['overall_avg']??-1) <=> ($a['overall_avg']??-1));
    foreach ($results as $i => &$r) { $r['rank'] = $i+1; }

    $school = DB::fetchOne('SELECT name FROM schools WHERE id=?',[$sid]);
    respond([
        'class' => $cls, 'subjects' => $subjects, 'results' => $results,
        'academic_year' => $acYear, 'semester' => $semNum, 'school_name' => $school['name']??'School',
        'grade_config' => $gradeConf,
    ]);
}

function ensureGradeConfigSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    DB::execute("CREATE TABLE IF NOT EXISTS grade_config (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        school_id  INT UNSIGNED NOT NULL,
        grade      VARCHAR(5)   NOT NULL,
        label      VARCHAR(30)  NOT NULL,
        min_pct    DECIMAL(5,2) NOT NULL,
        max_pct    DECIMAL(5,2) NOT NULL,
        sort_order TINYINT      DEFAULT 0,
        UNIQUE KEY uniq_school_grade (school_id, grade)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}

function ensureAcademicPeriodsSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        DB::execute("CREATE TABLE IF NOT EXISTS academic_periods (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            school_id     INT UNSIGNED NOT NULL,
            year_group    TINYINT      NOT NULL,
            academic_year VARCHAR(20)  NOT NULL,
            semester      TINYINT      NOT NULL,
            is_active     TINYINT(1)   NOT NULL DEFAULT 1,
            started_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            ended_at      TIMESTAMP    NULL,
            set_by        INT UNSIGNED NOT NULL,
            INDEX idx_school_active     (school_id, is_active),
            INDEX idx_school_year_group (school_id, year_group, is_active)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        DB::execute("ALTER TABLE tests     ADD COLUMN IF NOT EXISTS academic_year    VARCHAR(20)      NULL");
        DB::execute("ALTER TABLE attempts  ADD COLUMN IF NOT EXISTS academic_year    VARCHAR(20)      NULL");
        DB::execute("ALTER TABLE attempts  ADD COLUMN IF NOT EXISTS attempt_semester TINYINT UNSIGNED NULL");
        // Backfill
        DB::execute("UPDATE tests SET academic_year=CONCAT(IF(MONTH(created_at)>=8,YEAR(created_at),YEAR(created_at)-1),'/',IF(MONTH(created_at)>=8,YEAR(created_at)+1,YEAR(created_at))),semester=IF(MONTH(created_at) IN (8,9,10,11,12,1),1,2) WHERE academic_year IS NULL AND created_at IS NOT NULL");
        DB::execute("UPDATE attempts SET academic_year=CONCAT(IF(MONTH(COALESCE(submitted_at,started_at))>=8,YEAR(COALESCE(submitted_at,started_at)),YEAR(COALESCE(submitted_at,started_at))-1),'/',IF(MONTH(COALESCE(submitted_at,started_at))>=8,YEAR(COALESCE(submitted_at,started_at))+1,YEAR(COALESCE(submitted_at,started_at)))),attempt_semester=IF(MONTH(COALESCE(submitted_at,started_at)) IN (8,9,10,11,12,1),1,2) WHERE academic_year IS NULL");
    } catch (Throwable $e) {}
}

function _currentPeriod(int $schoolId, int $yearGroup): array {
    ensureAcademicPeriodsSchema();
    $p = DB::fetchOne(
        'SELECT academic_year, semester FROM academic_periods WHERE school_id=? AND year_group=? AND is_active=1 ORDER BY started_at DESC LIMIT 1',
        [$schoolId, $yearGroup]
    );
    if ($p) return $p;
    // Derive from current date if none set
    $m = (int)date('n');
    return [
        'academic_year' => ($m >= 8 ? date('Y') : date('Y')-1) . '/' . ($m >= 8 ? date('Y')+1 : date('Y')),
        'semester'      => in_array($m, [8,9,10,11,12,1]) ? 1 : 2,
    ];
}

function handleAcademicPeriods(string $method, ?int $id, array $body): void {
    $auth = require_auth();
    $sid  = $auth['school_id'];
    ensureAcademicPeriodsSchema();

    // GET /academic-periods — returns current periods + full history
    if ($method === 'GET') {
        $current = DB::fetchAll(
            'SELECT * FROM academic_periods WHERE school_id=? AND is_active=1 ORDER BY year_group',
            [$sid]
        );
        $history = DB::fetchAll(
            'SELECT ap.*, CONCAT(u.first_name," ",u.last_name) AS set_by_name
             FROM academic_periods ap LEFT JOIN users u ON u.id=ap.set_by
             WHERE ap.school_id=? ORDER BY ap.year_group, ap.started_at DESC',
            [$sid]
        );
        // Also return distinct academic years used across all tests/periods for selector
        $allYears = DB::fetchAll(
            'SELECT DISTINCT academic_year, semester FROM tests WHERE school_id=? AND academic_year IS NOT NULL
             UNION SELECT DISTINCT academic_year, semester FROM academic_periods WHERE school_id=?
             ORDER BY academic_year DESC, semester DESC',
            [$sid, $sid]
        );
        respond(['current' => $current, 'history' => $history, 'available' => $allYears]);
    }

    // POST /academic-periods — admin sets period for a year group
    if ($method === 'POST') {
        require_role($auth, 'admin');
        $yg   = (int)($body['year_group']    ?? 0);
        $year = trim($body['academic_year']  ?? '');
        $sem  = (int)($body['semester']      ?? 1);
        if (!in_array($yg, [1,2,3]))      err('year_group must be 1, 2, or 3');
        if (!$year)                        err('academic_year is required (e.g. 2024/2025)');
        if (!in_array($sem, [1,2]))        err('semester must be 1 or 2');

        // Close current active period for this year_group
        DB::execute(
            'UPDATE academic_periods SET is_active=0, ended_at=NOW() WHERE school_id=? AND year_group=? AND is_active=1',
            [$sid, $yg]
        );
        $startDate = !empty($body['start_date']) ? $body['start_date'] : null;
        $endDate   = !empty($body['end_date'])   ? $body['end_date']   : null;
        $newId = DB::insert(
            'INSERT INTO academic_periods (school_id, year_group, academic_year, semester, is_active, set_by, start_date, end_date) VALUES (?,?,?,?,1,?,?,?)',
            [$sid, $yg, $year, $sem, $auth['user_id'], $startDate, $endDate]
        );
        respond(['id' => $newId, 'message' => "Year $yg period updated: $year Semester $sem"]);
    }

    // PATCH /academic-periods/{id} — edit or activate/deactivate
    if ($method === 'PATCH' && $id) {
        require_role($auth, 'admin');
        $p = DB::fetchOne('SELECT id, year_group, is_active FROM academic_periods WHERE id=? AND school_id=?', [$id, $sid]);
        if (!$p) err('Period not found', 404);

        // Handle activate request: deactivate ALL others in same year group first
        if (array_key_exists('is_active', $body) && (int)$body['is_active'] === 1) {
            DB::execute(
                'UPDATE academic_periods SET is_active=0, ended_at=NOW() WHERE school_id=? AND year_group=? AND id!=?',
                [$sid, (int)$p['year_group'], $id]
            );
            DB::execute('UPDATE academic_periods SET is_active=1, started_at=NOW(), ended_at=NULL WHERE id=?', [$id]);
            respond(['message' => 'Period activated — others in Year ' . $p['year_group'] . ' deactivated']);
        }

        $sets=[]; $params=[];
        if (array_key_exists('is_active', $body))     { $sets[]='is_active=?';     $params[]=(int)$body['is_active']; if(!(int)$body['is_active']){$sets[]='ended_at=NOW()';} }
        if (array_key_exists('academic_year', $body)) { $sets[]='academic_year=?'; $params[]=trim($body['academic_year']); }
        if (array_key_exists('semester', $body))      { $sets[]='semester=?';      $params[]=(int)$body['semester']; }
        if (array_key_exists('start_date', $body))    { $sets[]='start_date=?';    $params[]=($body['start_date']?:null); }
        if (array_key_exists('end_date', $body))      { $sets[]='end_date=?';      $params[]=($body['end_date']?:null); }
        if ($sets) { $params[]=$id; DB::execute('UPDATE academic_periods SET '.implode(',',$sets).' WHERE id=?',$params); }
        respond(['message'=>'Period updated']);
    }

    // DELETE /academic-periods/{id}
    if ($method === 'DELETE' && $id) {
        require_role($auth, 'admin');
        $p = DB::fetchOne('SELECT id, is_active FROM academic_periods WHERE id=? AND school_id=?', [$id, $sid]);
        if (!$p) err('Period not found', 404);
        if ((int)$p['is_active']) err('Cannot delete an active period. Deactivate it first.', 409);
        DB::execute('DELETE FROM academic_periods WHERE id=?', [$id]);
        respond(['message'=>'Period deleted']);
    }

    err('Academic periods endpoint error', 404);
}

function ensureQuestionTypeSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        // Widen type column so any string is valid (fixes ENUM rejection of 'fill-in')
        DB::execute("ALTER TABLE questions MODIFY COLUMN type VARCHAR(20) NOT NULL DEFAULT 'mcq'");
        // Migrate empty/blank/null → fill-in
        DB::execute("UPDATE questions SET type='fill-in' WHERE type='' OR type IS NULL OR type='blank'");
    } catch (Throwable $e) {}
}

function ensureTeacherSubjectSchema(): void {
    try {
        DB::execute("CREATE TABLE IF NOT EXISTS teacher_subjects (
            teacher_id  INT UNSIGNED NOT NULL,
            subject_id  INT UNSIGNED NOT NULL,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            assigned_by INT UNSIGNED NULL,
            PRIMARY KEY (teacher_id, subject_id),
            INDEX idx_ts_teacher (teacher_id),
            INDEX idx_ts_subject (subject_id)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        DB::execute("ALTER TABLE questions ADD COLUMN IF NOT EXISTS subject_id INT UNSIGNED NULL");
        DB::execute("ALTER TABLE questions ADD COLUMN IF NOT EXISTS updated_by  INT UNSIGNED NULL");
        DB::execute("ALTER TABLE tests     ADD COLUMN IF NOT EXISTS subject_id INT UNSIGNED NULL");
        DB::execute("ALTER TABLE tests     ADD COLUMN IF NOT EXISTS updated_by  INT UNSIGNED NULL");
        DB::execute("ALTER TABLE tests     ADD COLUMN IF NOT EXISTS semester    TINYINT UNSIGNED NULL COMMENT '1 or 2'");
    } catch (Throwable $e) {}
}

function ensureClassSubjectSchema(): void {
    try {
        DB::execute("CREATE TABLE IF NOT EXISTS class_subjects (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            class_id    INT UNSIGNED NOT NULL,
            subject_id  INT UNSIGNED NOT NULL,
            group_tag   VARCHAR(100) NULL COMMENT 'NULL = whole class; non-null = sub-group label',
            sort_order  SMALLINT DEFAULT 0,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_cls_subj (class_id, subject_id, group_tag)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        DB::execute("CREATE TABLE IF NOT EXISTS student_class_groups (
            student_id  INT UNSIGNED NOT NULL,
            class_id    INT UNSIGNED NOT NULL,
            group_tag   VARCHAR(100) NOT NULL,
            PRIMARY KEY (student_id, class_id)
        ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    } catch (Throwable $e) {}
}

// ══════════════════════════════════════════════════════════════
// MEETINGS — Google Meet Attendance
// ══════════════════════════════════════════════════════════════

function ensureMeetingsSchema(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    DB::execute("CREATE TABLE IF NOT EXISTS meetings (
        id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        school_id        INT UNSIGNED  NOT NULL,
        title            VARCHAR(255)  NOT NULL,
        subject_id       INT UNSIGNED  NULL,
        teacher_id       INT UNSIGNED  NOT NULL,
        class_id         INT UNSIGNED  NOT NULL,
        google_meet_link VARCHAR(1000) NOT NULL,
        meeting_date     DATE          NOT NULL,
        start_time       TIME          NOT NULL,
        end_time         TIME          NOT NULL,
        status           ENUM('scheduled','live','ended','cancelled') NOT NULL DEFAULT 'scheduled',
        description      TEXT          NULL,
        created_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
        updated_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_school_date (school_id, meeting_date),
        INDEX idx_class       (class_id),
        INDEX idx_teacher     (teacher_id)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    DB::execute("CREATE TABLE IF NOT EXISTS meeting_attendance (
        id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        meeting_id        INT UNSIGNED NOT NULL,
        student_id        INT UNSIGNED NOT NULL,
        joined_at         TIMESTAMP    NULL,
        last_seen         TIMESTAMP    NULL,
        duration_minutes  INT UNSIGNED NOT NULL DEFAULT 0,
        attendance_status ENUM('absent','partial','late','present') NOT NULL DEFAULT 'absent',
        ip_address        VARCHAR(45)  NULL,
        device_info       VARCHAR(500) NULL,
        created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_meeting_student (meeting_id, student_id),
        INDEX idx_meeting (meeting_id),
        INDEX idx_student (student_id)
    ) ENGINE=InnoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}

function calcAttendanceStatus(int $minutes): string {
    if ($minutes <= 0)  return 'absent';
    if ($minutes < 10)  return 'partial';
    if ($minutes < 20)  return 'late';
    return 'present';
}

function handleMeetings(string $method, array $parts, array $body): void {
    $auth = require_auth();
    ensureMeetingsSchema();
    $sid  = $auth['school_id'];
    $uid  = $auth['user_id'];
    $role = $auth['role'];

    $id1    = $parts[1] ?? null;
    $id     = ($id1 !== null && ctype_digit((string)$id1)) ? (int)$id1 : null;
    $action = ($id1 !== null && !ctype_digit((string)$id1)) ? $id1 : null;
    $sub    = $parts[2] ?? null;

    // ── GET /meetings/analytics
    if ($action === 'analytics') {
        require_role($auth, 'teacher', 'admin');
        $teacherWhere = ($role === 'teacher') ? 'AND m.teacher_id=?' : '';
        $params = $role === 'teacher' ? [$sid, $uid] : [$sid];
        $rows = DB::fetchAll(
            "SELECT m.id, m.title, m.meeting_date, m.start_time, m.end_time, m.status,
                    c.name AS class_name, s.name AS subject_name,
                    COUNT(DISTINCT ma.id)                              AS joined_count,
                    (SELECT COUNT(*) FROM class_students WHERE class_id=m.class_id) AS class_size,
                    SUM(ma.attendance_status='present')  AS present_count,
                    SUM(ma.attendance_status='late')     AS late_count,
                    SUM(ma.attendance_status='partial')  AS partial_count,
                    SUM(ma.attendance_status='absent' OR ma.attendance_status IS NULL) AS absent_count,
                    ROUND(AVG(ma.duration_minutes),1)   AS avg_duration
             FROM meetings m
             LEFT JOIN classes c    ON c.id = m.class_id
             LEFT JOIN subjects s   ON s.id = m.subject_id
             LEFT JOIN meeting_attendance ma ON ma.meeting_id = m.id
             WHERE m.school_id=? $teacherWhere AND m.status != 'cancelled'
             GROUP BY m.id
             ORDER BY m.meeting_date DESC LIMIT 60",
            $params
        );
        respond(['meetings' => $rows]);
    }

    // ── GET /meetings/history (student's own attendance)
    if ($action === 'history') {
        require_role($auth, 'student');
        $hSubjId    = !empty($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
        $hSubjWhere = $hSubjId ? 'AND m.subject_id = ?' : '';
        $hSubjParam = $hSubjId ? [$hSubjId] : [];
        $rows = DB::fetchAll(
            "SELECT m.id, m.title, m.meeting_date, m.start_time, m.end_time,
                    c.name AS class_name, s.name AS subject_name,
                    ma.joined_at, ma.duration_minutes, ma.attendance_status
             FROM meetings m
             LEFT JOIN classes c  ON c.id = m.class_id
             LEFT JOIN subjects s ON s.id = m.subject_id
             LEFT JOIN meeting_attendance ma ON ma.meeting_id=m.id AND ma.student_id=?
             WHERE m.class_id IN (SELECT class_id FROM class_students WHERE student_id=?)
               AND m.school_id=? AND m.status != 'cancelled'
               $hSubjWhere
             ORDER BY m.meeting_date DESC, m.start_time DESC LIMIT 60",
            array_merge([$uid, $uid, $sid], $hSubjParam)
        );
        respond($rows);
    }

    // ── Collection endpoints (no id)
    if ($id === null && $action === null) {
        if ($method === 'GET') {
            // Auto-transition scheduled/live meetings to 'ended' when end time has passed
            $aeWhere  = ($role === 'teacher') ? 'AND teacher_id=?' : '';
            $aeParams = $role === 'teacher' ? [$sid, $uid] : [$sid];
            DB::execute(
                "UPDATE meetings SET status='ended'
                 WHERE school_id=? $aeWhere
                   AND status IN ('scheduled','live')
                   AND CONCAT(meeting_date,' ',end_time) < NOW()",
                $aeParams
            );
            // Optional subject filter from context bar
            $subjId     = !empty($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
            $subjWhere  = $subjId ? 'AND m.subject_id = ?' : '';
            $subjParam  = $subjId ? [$subjId] : [];

            if ($role === 'student') {
                $rows = DB::fetchAll(
                    "SELECT m.id, m.title, m.meeting_date, m.start_time, m.end_time,
                            m.status, m.description,
                            c.name AS class_name, s.name AS subject_name,
                            u.first_name AS teacher_first, u.last_name AS teacher_last,
                            ma.attendance_status, ma.joined_at
                     FROM meetings m
                     JOIN  classes c ON c.id = m.class_id
                     LEFT JOIN subjects s ON s.id = m.subject_id
                     JOIN  users u ON u.id = m.teacher_id
                     LEFT JOIN meeting_attendance ma ON ma.meeting_id=m.id AND ma.student_id=?
                     WHERE m.class_id IN (SELECT class_id FROM class_students WHERE student_id=?)
                       AND m.school_id=? AND m.status != 'cancelled'
                       AND m.meeting_date >= CURDATE() - INTERVAL 7 DAY
                       $subjWhere
                     ORDER BY m.meeting_date ASC, m.start_time ASC LIMIT 30",
                    array_merge([$uid, $uid, $sid], $subjParam)
                );
            } else {
                $teacherWhere = ($role === 'teacher') ? 'AND m.teacher_id=?' : '';
                $params = $role === 'teacher' ? [$sid, $uid] : [$sid];
                $rows = DB::fetchAll(
                    "SELECT m.id, m.title, m.meeting_date, m.start_time, m.end_time,
                            m.status, m.description, m.class_id,
                            c.name AS class_name, s.name AS subject_name,
                            u.first_name AS teacher_first, u.last_name AS teacher_last,
                            (SELECT COUNT(*) FROM meeting_attendance WHERE meeting_id=m.id) AS attendance_count,
                            (SELECT COUNT(*) FROM class_students WHERE class_id=m.class_id)  AS class_size
                     FROM meetings m
                     JOIN  classes c ON c.id = m.class_id
                     LEFT JOIN subjects s ON s.id = m.subject_id
                     JOIN  users u ON u.id = m.teacher_id
                     WHERE m.school_id=? $teacherWhere $subjWhere
                     ORDER BY m.meeting_date DESC, m.start_time DESC LIMIT 60",
                    array_merge($params, $subjParam)
                );
            }
            respond($rows ?? []);
        }

        if ($method === 'POST') {
            require_role($auth, 'teacher', 'admin');
            $title  = trim($body['title'] ?? '');
            $link   = trim($body['google_meet_link'] ?? '');
            $date   = trim($body['meeting_date'] ?? '');
            $start  = trim($body['start_time'] ?? '');
            $end    = trim($body['end_time'] ?? '');
            // Accept class_ids (array) or legacy class_id (scalar)
            $classIds = [];
            if (!empty($body['class_ids']) && is_array($body['class_ids'])) {
                $classIds = array_map('intval', $body['class_ids']);
            } elseif (!empty($body['class_id'])) {
                $classIds = [(int)$body['class_id']];
            }
            $classIds = array_values(array_unique($classIds));
            if (!$title || empty($classIds) || !$link || !$date || !$start || !$end)
                err('title, class_ids, google_meet_link, meeting_date, start_time, end_time required');
            $teacherId  = ($role === 'admin' && !empty($body['teacher_id'])) ? (int)$body['teacher_id'] : $uid;
            $subjId     = $body['subject_id'] ?: null;
            $desc       = trim($body['description'] ?? '');
            $createdIds = [];
            foreach ($classIds as $cid) {
                $cls = DB::fetchOne('SELECT id FROM classes WHERE id=? AND school_id=?', [$cid, $sid]);
                if (!$cls) continue;
                $newId = DB::insert(
                    'INSERT INTO meetings (school_id,title,subject_id,teacher_id,class_id,google_meet_link,meeting_date,start_time,end_time,description)
                     VALUES (?,?,?,?,?,?,?,?,?,?)',
                    [$sid, $title, $subjId, $teacherId, $cid, $link, $date, $start, $end, $desc]
                );
                $createdIds[] = $newId;
            }
            if (empty($createdIds)) err('No valid classes found', 404);
            respond(['ids' => $createdIds, 'count' => count($createdIds), 'message' => count($createdIds) . ' meeting(s) created']);
        }
        err('Method not allowed', 405);
    }

    // ── Single-meeting endpoints
    $meeting = DB::fetchOne('SELECT * FROM meetings WHERE id=? AND school_id=?', [$id, $sid]);
    if (!$meeting) err('Meeting not found', 404);

    // POST /meetings/{id}/join
    if ($sub === 'join' && $method === 'POST') {
        require_role($auth, 'student');
        if ($meeting['status'] === 'cancelled') err('This meeting has been cancelled', 410);
        $enrolled = DB::fetchOne('SELECT 1 FROM class_students WHERE class_id=? AND student_id=?', [$meeting['class_id'], $uid]);
        if (!$enrolled) err('You are not enrolled in this class', 403);

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
        if ($ip) $ip = trim(explode(',', $ip)[0]);
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

        DB::execute(
            'INSERT INTO meeting_attendance (meeting_id, student_id, joined_at, last_seen, ip_address, device_info)
             VALUES (?,?,NOW(),NOW(),?,?)
             ON DUPLICATE KEY UPDATE
               joined_at   = IF(joined_at IS NULL, NOW(), joined_at),
               last_seen   = NOW(),
               ip_address  = VALUES(ip_address),
               device_info = VALUES(device_info)',
            [$id, $uid, $ip, $ua]
        );
        if ($meeting['status'] === 'scheduled') {
            DB::execute("UPDATE meetings SET status='live' WHERE id=?", [$id]);
        }
        respond(['meet_link' => $meeting['google_meet_link']]);
    }

    // POST /meetings/{id}/heartbeat
    if ($sub === 'heartbeat' && $method === 'POST') {
        require_role($auth, 'student');
        $att = DB::fetchOne('SELECT joined_at FROM meeting_attendance WHERE meeting_id=? AND student_id=?', [$id, $uid]);
        if (!$att || !$att['joined_at']) err('No attendance record found', 404);
        $dur    = (int)(DB::fetchOne('SELECT TIMESTAMPDIFF(MINUTE,?,NOW()) AS d', [$att['joined_at']])['d'] ?? 0);
        $status = calcAttendanceStatus($dur);
        DB::execute(
            'UPDATE meeting_attendance SET last_seen=NOW(), duration_minutes=?, attendance_status=? WHERE meeting_id=? AND student_id=?',
            [$dur, $status, $id, $uid]
        );
        respond(['duration_minutes' => $dur, 'status' => $status]);
    }

    // GET /meetings/{id}/attendance
    if ($sub === 'attendance' && $method === 'GET') {
        require_role($auth, 'teacher', 'admin');
        if ($role === 'teacher' && (int)$meeting['teacher_id'] !== (int)$uid) err('Not your meeting', 403);
        $rows = DB::fetchAll(
            "SELECT u.id, u.first_name, u.last_name, u.avatar_color,
                    ma.joined_at, ma.last_seen, ma.duration_minutes, ma.attendance_status, ma.ip_address
             FROM class_students cs
             JOIN users u ON u.id = cs.student_id
             LEFT JOIN meeting_attendance ma ON ma.meeting_id=? AND ma.student_id=u.id
             WHERE cs.class_id=?
             ORDER BY COALESCE(ma.attendance_status,'absent') ASC, u.last_name, u.first_name",
            [$id, $meeting['class_id']]
        );
        $cls = DB::fetchOne('SELECT name FROM classes WHERE id=?', [$meeting['class_id']]);
        $total   = count($rows);
        $present = count(array_filter($rows, fn($r) => ($r['attendance_status'] ?? '') === 'present'));
        $late    = count(array_filter($rows, fn($r) => ($r['attendance_status'] ?? '') === 'late'));
        $partial = count(array_filter($rows, fn($r) => ($r['attendance_status'] ?? '') === 'partial'));
        $absent  = $total - $present - $late - $partial;
        $out     = $meeting; unset($out['google_meet_link']);
        respond([
            'meeting'    => $out,
            'class_name' => $cls['name'] ?? '',
            'attendance' => $rows,
            'summary'    => ['total'=>$total,'present'=>$present,'late'=>$late,'partial'=>$partial,'absent'=>$absent],
        ]);
    }

    // PATCH /meetings/{id}
    if ($method === 'PATCH') {
        require_role($auth, 'teacher', 'admin');
        if ($role === 'teacher' && (int)$meeting['teacher_id'] !== $uid) err('Not your meeting', 403);
        $fields = []; $vals = [];
        foreach (['title','google_meet_link','meeting_date','start_time','end_time','description','status'] as $f) {
            if (array_key_exists($f, $body)) { $fields[] = "$f=?"; $vals[] = $body[$f]; }
        }
        if (array_key_exists('subject_id', $body)) { $fields[] = 'subject_id=?'; $vals[] = $body['subject_id'] ?: null; }
        if (!empty($body['class_id'])) {
            $newCls = DB::fetchOne('SELECT id FROM classes WHERE id=? AND school_id=?', [(int)$body['class_id'], $sid]);
            if ($newCls) { $fields[] = 'class_id=?'; $vals[] = (int)$body['class_id']; }
        }
        if (!$fields) err('Nothing to update');
        $vals[] = $id;
        DB::execute('UPDATE meetings SET ' . implode(',', $fields) . ' WHERE id=?', $vals);
        respond(['message' => 'Meeting updated']);
    }

    // DELETE /meetings/{id}  → soft-cancel
    if ($method === 'DELETE') {
        require_role($auth, 'teacher', 'admin');
        if ($role === 'teacher' && (int)$meeting['teacher_id'] !== $uid) err('Not your meeting', 403);
        DB::execute("UPDATE meetings SET status='cancelled' WHERE id=?", [$id]);
        respond(['message' => 'Meeting cancelled']);
    }

    // GET /meetings/{id}
    if ($method === 'GET') {
        if ($role === 'student') {
            $enrolled = DB::fetchOne('SELECT 1 FROM class_students WHERE class_id=? AND student_id=?', [$meeting['class_id'], $uid]);
            if (!$enrolled) err('Access denied', 403);
            unset($meeting['google_meet_link']); // students never get the raw link
        }
        // Enrich with class_name for the edit modal
        $cls = DB::fetchOne('SELECT name FROM classes WHERE id=?', [$meeting['class_id']]);
        $meeting['class_name'] = $cls['name'] ?? '';
        respond($meeting);
    }

    err('Not found', 404);
}

// ── Activity log ─────────────────────────────────────────────
function ensureActivityLogSchema(): void {
    static $done = false;
    if ($done) return;
    try {
        DB::execute("CREATE TABLE IF NOT EXISTS activity_log (
            id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            school_id    INT UNSIGNED    NOT NULL,
            user_id      INT UNSIGNED    NULL,
            user_role    VARCHAR(20)     NULL,
            user_name    VARCHAR(200)    NULL,
            action       VARCHAR(100)    NOT NULL,
            entity_type  VARCHAR(50)     NULL,
            entity_id    INT UNSIGNED    NULL,
            entity_label VARCHAR(300)    NULL,
            description  TEXT            NULL,
            ip_address   VARCHAR(45)     NULL,
            created_at   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
            KEY idx_school_date (school_id, created_at),
            KEY idx_user        (user_id),
            KEY idx_action      (action(50))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", []);
        $done = true;
    } catch (Throwable $e) {}
}

function logActivity(array $auth, string $action, ?string $entityType = null, ?int $entityId = null, ?string $entityLabel = null, ?string $description = null): void {
    static $nameCache = [];
    ensureActivityLogSchema();
    $uid  = isset($auth['user_id']) ? (int)$auth['user_id'] : null;
    $sid  = isset($auth['school_id']) ? (int)$auth['school_id'] : null;
    $role = $auth['role'] ?? null;
    if ($uid && !isset($nameCache[$uid])) {
        try {
            $u = DB::fetchOne('SELECT first_name, last_name FROM users WHERE id=?', [$uid]);
            $nameCache[$uid] = $u ? trim(($u['first_name']??'').' '.($u['last_name']??'')) : '';
        } catch (Throwable $e) { $nameCache[$uid] = ''; }
    }
    $name = $uid ? ($nameCache[$uid] ?? '') : 'System';
    $ip   = $_SERVER['REMOTE_ADDR'] ?? null;
    try {
        DB::insert(
            'INSERT INTO activity_log (school_id,user_id,user_role,user_name,action,entity_type,entity_id,entity_label,description,ip_address)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$sid, $uid, $role, $name, $action, $entityType, $entityId,
             $entityLabel ? mb_substr($entityLabel, 0, 290) : null,
             $description, $ip]
        );
    } catch (Throwable $e) { /* never break the app for logging */ }
}

// ── Activity log endpoint ─────────────────────────────────────
function handleActivityLog(string $method, array $body): void {
    $auth = require_auth();
    require_role($auth, 'admin');
    ensureActivityLogSchema();
    $sid = $auth['school_id'];

    if ($method === 'GET') {
        $limit   = min(500, max(20, (int)($_GET['limit']   ?? 100)));
        $offset  = max(0,           (int)($_GET['offset']  ?? 0));
        $action  = trim($_GET['action']  ?? '');
        $userId  = (int)($_GET['user_id'] ?? 0);
        $from    = trim($_GET['from']    ?? '');
        $to      = trim($_GET['to']      ?? '');
        $search  = trim($_GET['search']  ?? '');

        $where  = ['l.school_id=?'];
        $params = [$sid];
        if ($action)  { $where[] = 'l.action LIKE ?';           $params[] = $action.'%'; }
        if ($userId)  { $where[] = 'l.user_id=?';               $params[] = $userId; }
        if ($from)    { $where[] = 'l.created_at>=?';           $params[] = $from.' 00:00:00'; }
        if ($to)      { $where[] = 'l.created_at<=?';           $params[] = $to.' 23:59:59'; }
        if ($search)  { $where[] = '(l.entity_label LIKE ? OR l.description LIKE ? OR l.user_name LIKE ?)';
                        $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

        $w     = implode(' AND ', $where);
        $total = (int)(DB::fetchOne("SELECT COUNT(*) AS n FROM activity_log l WHERE $w", $params)['n'] ?? 0);
        $rows  = DB::fetchAll(
            "SELECT l.*, u.avatar_color
             FROM activity_log l
             LEFT JOIN users u ON u.id=l.user_id
             WHERE $w ORDER BY l.created_at DESC LIMIT ? OFFSET ?",
            array_merge($params, [$limit, $offset])
        );

        // Summary counts for dashboard
        $summary = DB::fetchAll(
            "SELECT SUBSTRING_INDEX(action,'.',1) AS category, COUNT(*) AS n
             FROM activity_log WHERE school_id=?
             GROUP BY category ORDER BY n DESC LIMIT 15",
            [$sid]
        );
        // Active users today
        $activeToday = (int)(DB::fetchOne(
            "SELECT COUNT(DISTINCT user_id) AS n FROM activity_log WHERE school_id=? AND DATE(created_at)=CURDATE()",
            [$sid]
        )['n'] ?? 0);

        respond(['logs'=>$rows,'total'=>$total,'limit'=>$limit,'offset'=>$offset,'summary'=>$summary,'active_today'=>$activeToday]);
    }

    if ($method === 'DELETE') {
        $days = max(7, (int)($body['days'] ?? 90));
        DB::execute('DELETE FROM activity_log WHERE school_id=? AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)', [$sid, $days]);
        respond(['message' => "Logs older than $days days deleted"]);
    }

    err('Method not allowed', 405);
}

function ensureCurriculumSchema(): void {
    static $done = false;
    if ($done) return;
    try {
        DB::execute("CREATE TABLE IF NOT EXISTS subject_strands (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            school_id    INT UNSIGNED NOT NULL,
            subject_id   INT UNSIGNED NOT NULL,
            strand_code  VARCHAR(50)  NOT NULL,
            strand_label VARCHAR(255) NOT NULL,
            description  TEXT         NULL,
            sort_order   SMALLINT UNSIGNED DEFAULT 0,
            is_active    TINYINT(1)   DEFAULT 1,
            created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            KEY idx_strands (school_id, subject_id, sort_order),
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", []);

        DB::execute("CREATE TABLE IF NOT EXISTS subject_sub_strands (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            strand_id        INT UNSIGNED NOT NULL,
            sub_strand_code  VARCHAR(50)  NULL,
            sub_strand_label VARCHAR(255) NOT NULL,
            description      TEXT         NULL,
            sort_order       SMALLINT UNSIGNED DEFAULT 0,
            is_active        TINYINT(1)   DEFAULT 1,
            created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            KEY idx_subs (strand_id, sort_order),
            FOREIGN KEY (strand_id) REFERENCES subject_strands(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", []);

        DB::execute("CREATE TABLE IF NOT EXISTS subject_topics (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sub_strand_id INT UNSIGNED NOT NULL,
            topic_code    VARCHAR(50)  NULL,
            topic_label   VARCHAR(255) NOT NULL,
            description   TEXT         NULL,
            sort_order    SMALLINT UNSIGNED DEFAULT 0,
            is_active     TINYINT(1)   DEFAULT 1,
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            KEY idx_topics (sub_strand_id, sort_order),
            FOREIGN KEY (sub_strand_id) REFERENCES subject_sub_strands(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", []);

        // Add FK columns to questions if not present
        try { DB::execute("ALTER TABLE questions ADD COLUMN IF NOT EXISTS strand_id INT UNSIGNED NULL", []); } catch(Throwable $e2) {}
        try { DB::execute("ALTER TABLE questions ADD COLUMN IF NOT EXISTS sub_strand_id INT UNSIGNED NULL", []); } catch(Throwable $e2) {}
        try { DB::execute("ALTER TABLE questions ADD COLUMN IF NOT EXISTS topic_id INT UNSIGNED NULL", []); } catch(Throwable $e2) {}

        $done = true;
    } catch (Throwable $e) {}
}
// backwards-compat alias
function ensureSubjectStrandsSchema(): void { ensureCurriculumSchema(); }

function ensureSubjectSchema(): void {
    try {
        DB::execute("CREATE TABLE IF NOT EXISTS subjects (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            school_id   INT UNSIGNED NULL,
            name        VARCHAR(200) NOT NULL,
            short_name  VARCHAR(80)  NULL,
            category    VARCHAR(100) NOT NULL DEFAULT 'General',
            sort_order  SMALLINT UNSIGNED DEFAULT 0,
            created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_subj (school_id, name)
        ) ENGINE=InnoDB");
        DB::execute("CREATE TABLE IF NOT EXISTS school_subjects (
            school_id   INT UNSIGNED NOT NULL,
            subject_id  INT UNSIGNED NOT NULL,
            enabled_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (school_id, subject_id)
        ) ENGINE=InnoDB");
    } catch (Throwable $e) {}
}

// ══════════════════════════════════════════════════════════════
// INCREMENTAL SYNC  — GET /sync?since=UNIX_TIMESTAMP
//
// Returns everything changed since `since` for the current user.
// The client stores the returned `synced_at` and sends it next time.
// This keeps subsequent syncs tiny (only deltas).
// ══════════════════════════════════════════════════════════════
function handleSync(string $method): void {
    if ($method !== 'GET') err('GET only', 405);
    $auth = require_auth();
    $sid  = $auth['school_id'];
    $uid  = $auth['user_id'];
    $role = $auth['role'];

    // `since` is a Unix timestamp from the client; default to 30 days ago
    $since     = isset($_GET['since']) ? (int)$_GET['since'] : 0;
    $sinceDate = $since > 0
        ? date('Y-m-d H:i:s', $since)
        : date('Y-m-d H:i:s', strtotime('-30 days'));

    $payload = ['synced_at' => time()];

    if ($role === 'student') {
        // Tests assigned to the student's class(es)
        $payload['tests'] = DB::fetchAll(
            "SELECT t.id, t.title, t.status, t.time_limit_min, t.due_at, t.subject_id,
                    t.semester, t.academic_year, t.created_at
             FROM tests t
             JOIN test_assignments ta ON ta.test_id = t.id
             JOIN class_students   cs ON cs.class_id = ta.class_id AND cs.student_id = ?
             WHERE t.school_id = ? AND t.status = 'published'
               AND (t.created_at >= ? OR t.updated_at >= ?)
             LIMIT 100",
            [$uid, $sid, $sinceDate, $sinceDate]
        );

        // Student's own attempts
        $payload['attempts'] = DB::fetchAll(
            "SELECT a.id, a.test_id, a.status, a.score_auto, a.score_manual,
                    a.max_score, a.submitted_at, a.started_at
             FROM attempts a
             WHERE a.student_id = ? AND (a.started_at >= ? OR a.submitted_at >= ?)
             LIMIT 100",
            [$uid, $sinceDate, $sinceDate]
        );

        // Upcoming meetings for the student's class
        $payload['meetings'] = DB::fetchAll(
            "SELECT m.id, m.title, m.meeting_date, m.start_time, m.end_time,
                    m.status, m.subject_id
             FROM meetings m
             WHERE m.class_id IN (SELECT class_id FROM class_students WHERE student_id = ?)
               AND m.school_id = ? AND m.status != 'cancelled'
               AND (m.created_at >= ? OR m.updated_at >= ?)
             LIMIT 50",
            [$uid, $sid, $sinceDate, $sinceDate]
        );

    } elseif ($role === 'teacher') {
        // Teacher's own tests
        $payload['tests'] = DB::fetchAll(
            "SELECT id, title, status, time_limit_min, due_at, subject_id,
                    semester, academic_year, created_at
             FROM tests
             WHERE creator_id = ? AND school_id = ? AND created_at >= ?
             LIMIT 100",
            [$uid, $sid, $sinceDate]
        );

        // Questions the teacher created or in their subjects
        $payload['questions'] = DB::fetchAll(
            "SELECT id, type, question_text, sub_strand, difficulty, marks,
                    subject_id, created_at
             FROM questions
             WHERE school_id = ? AND author_id = ? AND created_at >= ?
             LIMIT 200",
            [$sid, $uid, $sinceDate]
        );

        // Teacher's meetings
        $payload['meetings'] = DB::fetchAll(
            "SELECT id, title, meeting_date, start_time, end_time, status,
                    class_id, subject_id, created_at
             FROM meetings
             WHERE teacher_id = ? AND school_id = ?
               AND (created_at >= ? OR updated_at >= ?)
             LIMIT 50",
            [$uid, $sid, $sinceDate, $sinceDate]
        );

    } elseif ($role === 'admin') {
        // School-wide tests
        $payload['tests'] = DB::fetchAll(
            "SELECT id, title, status, creator_id, subject_id, created_at
             FROM tests
             WHERE school_id = ? AND created_at >= ?
             LIMIT 100",
            [$sid, $sinceDate]
        );

        // School-wide meetings
        $payload['meetings'] = DB::fetchAll(
            "SELECT id, title, meeting_date, start_time, end_time, status,
                    teacher_id, class_id, subject_id, created_at
             FROM meetings
             WHERE school_id = ? AND (created_at >= ? OR updated_at >= ?)
             LIMIT 100",
            [$sid, $sinceDate, $sinceDate]
        );

        // Grade config changes
        $payload['grade_config'] = DB::fetchAll(
            'SELECT * FROM grade_config WHERE school_id = ? LIMIT 20',
            [$sid]
        );
    }

    // Strip nulls to keep payload lean
    foreach ($payload as $k => $v) {
        if (is_array($v) && empty($v)) unset($payload[$k]);
    }

    respond($payload);
}

// ══════════════════════════════════════════════════════════════
// TERM REPORT CARDS
// GET /term-report?class_id=N&academic_year=YYYY/YYYY&semester=N
// Returns full per-student data needed to render A4 report cards
// ══════════════════════════════════════════════════════════════
function handleTermReport(string $method): void {
    $auth = require_auth();
    require_role($auth, 'teacher', 'admin');
    ensureGradeConfigSchema();
    $sid     = $auth['school_id'];
    $classId = (int)($_GET['class_id']      ?? 0);
    $acYear  = trim($_GET['academic_year']  ?? '');
    $semNum  = (int)($_GET['semester']      ?? 0);
    if (!$classId || !$acYear || !$semNum) err('class_id, academic_year and semester required');

    $cls = DB::fetchOne('SELECT id, name, year_group FROM classes WHERE id=? AND school_id=?', [$classId, $sid]);
    if (!$cls) err('Class not found', 404);

    $school = DB::fetchOne('SELECT name, address, ges_id FROM schools WHERE id=?', [$sid]);

    $students = DB::fetchAll(
        'SELECT u.id, u.first_name, u.last_name, u.avatar_color
         FROM users u JOIN class_students cs ON cs.student_id=u.id
         WHERE cs.class_id=? ORDER BY u.last_name, u.first_name',
        [$classId]
    );

    $subjects = DB::fetchAll(
        'SELECT DISTINCT s.id, s.name, s.short_name FROM subjects s
         JOIN class_subjects csj ON csj.subject_id=s.id WHERE csj.class_id=? ORDER BY s.name',
        [$classId]
    );

    $attempts = DB::fetchAll(
        "SELECT a.student_id, t.subject_id,
                ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                COUNT(DISTINCT a.id) AS test_count
         FROM attempts a JOIN tests t ON t.id=a.test_id
         WHERE a.student_id IN (SELECT student_id FROM class_students WHERE class_id=?)
           AND t.semester=? AND t.academic_year=? AND a.status IN ('submitted','marked')
         GROUP BY a.student_id, t.subject_id",
        [$classId, $semNum, $acYear]
    );

    $gradeConf = DB::fetchAll('SELECT * FROM grade_config WHERE school_id=? ORDER BY sort_order', [$sid]);
    if (empty($gradeConf)) {
        $gradeConf = [
            ['grade'=>'A1','label'=>'Excellent','min_pct'=>80,'max_pct'=>100],['grade'=>'B2','label'=>'Very Good','min_pct'=>70,'max_pct'=>79],
            ['grade'=>'B3','label'=>'Good','min_pct'=>60,'max_pct'=>69],['grade'=>'C4','label'=>'Credit','min_pct'=>55,'max_pct'=>59],
            ['grade'=>'C5','label'=>'Credit','min_pct'=>50,'max_pct'=>54],['grade'=>'C6','label'=>'Credit','min_pct'=>45,'max_pct'=>49],
            ['grade'=>'D7','label'=>'Pass','min_pct'=>40,'max_pct'=>44],['grade'=>'E8','label'=>'Pass','min_pct'=>35,'max_pct'=>39],
            ['grade'=>'F9','label'=>'Fail','min_pct'=>0,'max_pct'=>34],
        ];
    }

    $gradeOf = function(float $pct) use ($gradeConf): string {
        foreach ($gradeConf as $g) {
            if ($pct >= (float)$g['min_pct'] && $pct <= (float)$g['max_pct']) return $g['grade'];
        }
        return 'F9';
    };

    // Build map: student_id → subject_id → { avg_pct, grade }
    $map = [];
    foreach ($attempts as $att) {
        $pct = (float)$att['avg_pct'];
        $map[$att['student_id']][$att['subject_id']] = [
            'avg_pct'    => $pct,
            'grade'      => $gradeOf($pct),
            'test_count' => (int)$att['test_count'],
        ];
    }

    // Build student cards with rankings
    $cards = [];
    foreach ($students as $stu) {
        $subjectRows = [];
        $sum = 0; $cnt = 0;
        foreach ($subjects as $subj) {
            $d   = $map[$stu['id']][$subj['id']] ?? null;
            $pct = $d ? (float)$d['avg_pct'] : null;
            if ($pct !== null) { $sum += $pct; $cnt++; }
            $subjectRows[] = [
                'subject_id'    => $subj['id'],
                'subject_name'  => $subj['name'],
                'subject_short' => $subj['short_name'],
                'avg_pct'       => $pct,
                'grade'         => $pct !== null ? $gradeOf($pct) : null,
                'test_count'    => $d['test_count'] ?? 0,
            ];
        }
        $overallAvg = $cnt > 0 ? round($sum / $cnt, 1) : null;
        $cards[] = [
            'student_id'   => $stu['id'],
            'first_name'   => $stu['first_name'],
            'last_name'    => $stu['last_name'],
            'avatar_color' => $stu['avatar_color'],
            'subjects'     => $subjectRows,
            'overall_avg'  => $overallAvg,
            'overall_grade'=> $overallAvg !== null ? $gradeOf($overallAvg) : null,
        ];
    }

    // Rank by overall average
    usort($cards, fn($a,$b) => ($b['overall_avg'] ?? -1) <=> ($a['overall_avg'] ?? -1));
    foreach ($cards as $i => &$c) { $c['rank'] = $i + 1; }

    respond([
        'class'         => $cls,
        'school'        => $school,
        'subjects'      => $subjects,
        'cards'         => $cards,
        'academic_year' => $acYear,
        'semester'      => $semNum,
        'grade_config'  => $gradeConf,
        'total_students'=> count($cards),
    ]);
}

// ══════════════════════════════════════════════════════════════
// AT-RISK STUDENT ALERTS
// GET /at-risk?class_id=N&threshold=40&academic_year=...&semester=N
// Returns students below threshold or showing declining trend
// ══════════════════════════════════════════════════════════════
function handleAtRisk(string $method): void {
    $auth      = require_auth();
    require_role($auth, 'teacher', 'admin');
    $sid       = $auth['school_id'];
    $uid       = (int)$auth['user_id'];
    $role      = $auth['role'];
    $threshold = max(0, min(100, (int)($_GET['threshold'] ?? 45)));
    $classId   = (int)($_GET['class_id'] ?? 0);
    $acYear    = trim($_GET['academic_year'] ?? '');
    $semNum    = (int)($_GET['semester']    ?? 0);

    // Class filter
    $classWhere  = $classId ? 'AND cs.class_id=?' : '';
    $classParams = $classId ? [$classId] : [];

    // Teacher restriction: params ONLY for the subquery placeholders (not for u.school_id=?)
    // Bug fix: $uid was previously merged as the school_id param — now kept separate.
    if ($role === 'teacher') {
        $teacherWhere      = 'AND cs.class_id IN (SELECT id FROM classes WHERE school_id=? AND teacher_id=?)';
        $teacherWhereParms = [$sid, $uid];
    } else {
        $teacherWhere      = '';
        $teacherWhereParms = [];
    }

    // Optional period filters
    $acWhere  = $acYear ? 'AND t.academic_year=?' : '';
    $semWhere = $semNum ? 'AND t.semester=?'      : '';
    $acParam  = $acYear ? [$acYear] : [];
    $semParam = $semNum ? [$semNum] : [];

    // ── 1. Below-threshold students ──────────────────────────────
    // Param order: u.school_id, teacherWhere?, classId?, acYear?, semNum?, c.school_id, threshold
    $atRisk = DB::fetchAll(
        "SELECT u.id AS student_id, u.first_name, u.last_name, u.avatar_color,
                c.name AS class_name, c.id AS class_id,
                ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1) AS avg_pct,
                COUNT(DISTINCT a.id) AS test_count,
                SUM(IF(a.max_score>0 AND (a.score_auto+a.score_manual)/a.max_score<0.5,1,0)) AS fail_count
         FROM users u
         JOIN class_students cs ON cs.student_id=u.id
         JOIN classes c         ON c.id = cs.class_id
         JOIN attempts a        ON a.student_id=u.id AND a.status IN ('submitted','marked')
         JOIN tests t           ON t.id=a.test_id
         WHERE u.school_id=? $teacherWhere $classWhere $acWhere $semWhere
           AND c.school_id=?
         GROUP BY u.id, u.first_name, u.last_name, u.avatar_color, c.name, c.id
         HAVING avg_pct < ? AND test_count >= 2
         ORDER BY avg_pct ASC LIMIT 50",
        array_merge([$sid], $teacherWhereParms, $classParams, $acParam, $semParam, [$sid, $threshold])
    );

    // ── 2. Declining students ─────────────────────────────────────
    // Wrapped in a derived table so outer WHERE can reference the aggregate
    // aliases (overall_avg, recent_avg) — MariaDB forbids this in HAVING.
    $declining = DB::fetchAll(
        "SELECT *
         FROM (
             SELECT u.id AS student_id, u.first_name, u.last_name, u.avatar_color,
                    c.name AS class_name,
                    ROUND(AVG(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0)),1)
                        AS overall_avg,
                    ROUND(AVG(IF(a.submitted_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND a.max_score>0,
                        (a.score_auto+a.score_manual)/a.max_score*100, NULL)),1)
                        AS recent_avg,
                    SUM(a.submitted_at >= DATE_SUB(NOW(), INTERVAL 60 DAY))
                        AS recent_count
             FROM users u
             JOIN class_students cs ON cs.student_id=u.id
             JOIN classes c ON c.id=cs.class_id AND c.school_id=?
             JOIN attempts a ON a.student_id=u.id AND a.status IN ('submitted','marked')
             WHERE u.school_id=? $teacherWhere $classWhere
             GROUP BY u.id, u.first_name, u.last_name, u.avatar_color, c.name
         ) AS sub
         WHERE sub.recent_count >= 1
           AND sub.recent_avg   IS NOT NULL
           AND sub.recent_avg   < sub.overall_avg - 5
           AND sub.recent_avg   < ?
         ORDER BY (sub.overall_avg - sub.recent_avg) DESC LIMIT 30",
        array_merge([$sid, $sid], $teacherWhereParms, $classParams, [$threshold + 10])
    );

    // ── 3. Students with zero attempts ───────────────────────────
    $missing = DB::fetchAll(
        "SELECT u.id AS student_id, u.first_name, u.last_name, c.name AS class_name
         FROM users u
         JOIN class_students cs ON cs.student_id=u.id
         JOIN classes c ON c.id=cs.class_id AND c.school_id=?
         WHERE u.school_id=? $teacherWhere $classWhere
           AND NOT EXISTS (
               SELECT 1 FROM attempts a2
               WHERE a2.student_id=u.id AND a2.status IN ('submitted','marked')
           )
         LIMIT 30",
        array_merge([$sid, $sid], $teacherWhereParms, $classParams)
    );

    respond([
        'threshold'   => $threshold,
        'at_risk'     => $atRisk,
        'declining'   => $declining,
        'no_attempts' => $missing,
        'summary'     => [
            'at_risk_count'   => count($atRisk),
            'declining_count' => count($declining),
            'missing_count'   => count($missing),
        ],
    ]);
}

// ══════════════════════════════════════════════════════════════
// REMEDIATION PLANNER
// GET /remediation?student_id=N  (student sees own; teacher sees any)
// Returns weak sub-strands + recommended practice areas
// ══════════════════════════════════════════════════════════════
function handleRemediation(string $method): void {
    $auth = require_auth();
    $sid  = $auth['school_id'];
    $uid  = $auth['user_id'];
    $role = $auth['role'];

    $studentId = ($role === 'student') ? $uid : (int)($_GET['student_id'] ?? $uid);
    $subjId    = !empty($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

    $subjFilter = $subjId ? 'AND t.subject_id=?' : '';
    $subjParam  = $subjId ? [$subjId] : [];

    // Sub-strand accuracy
    $subStrands = DB::fetchAll(
        "SELECT q.sub_strand,
                COUNT(*) AS total_q,
                SUM(IF(an.is_correct=1,1,0)) AS correct_q,
                ROUND(AVG(IF(an.is_correct=1,100,0)),1) AS accuracy_pct,
                MAX(a.submitted_at) AS last_attempted
         FROM answers an
         JOIN questions q ON q.id=an.question_id
         JOIN attempts a  ON a.id=an.attempt_id
         JOIN tests t     ON t.id=a.test_id
         WHERE a.student_id=? AND an.is_correct IS NOT NULL
           AND q.sub_strand IS NOT NULL $subjFilter
         GROUP BY q.sub_strand
         ORDER BY accuracy_pct ASC",
        array_merge([$studentId], $subjParam)
    );

    // Tag each as weak / needs work / strong
    $tagged = array_map(function($row) {
        $pct = (float)$row['accuracy_pct'];
        return array_merge($row, [
            'status'     => $pct < 40 ? 'critical' : ($pct < 60 ? 'needs_work' : 'good'),
            'label'      => $pct < 40 ? 'Critical — urgent revision' : ($pct < 60 ? 'Needs work' : 'Good'),
            'icon'       => $pct < 40 ? '🔴' : ($pct < 60 ? '🟡' : '🟢'),
        ]);
    }, $subStrands);

    // Hardest individual questions (answered wrong most often)
    $hardQuestions = DB::fetchAll(
        "SELECT q.id, q.question_text, q.sub_strand, q.type,
                COUNT(*) AS attempts,
                SUM(IF(an.is_correct=0,1,0)) AS wrong_count,
                ROUND(SUM(IF(an.is_correct=0,1,0))/COUNT(*)*100,0) AS error_rate_pct
         FROM answers an
         JOIN questions q ON q.id=an.question_id
         JOIN attempts a  ON a.id=an.attempt_id
         JOIN tests t     ON t.id=a.test_id
         WHERE a.student_id=? $subjFilter AND an.is_correct IS NOT NULL
         GROUP BY q.id, q.question_text, q.sub_strand, q.type
         HAVING wrong_count >= 1 AND error_rate_pct >= 50
         ORDER BY error_rate_pct DESC, wrong_count DESC LIMIT 10",
        array_merge([$studentId], $subjParam)
    );

    // Overall score trend (last 10 tests)
    $trend = DB::fetchAll(
        "SELECT t.title, t.subject_id, a.submitted_at,
                ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),1) AS score_pct
         FROM attempts a JOIN tests t ON t.id=a.test_id
         WHERE a.student_id=? AND a.status IN ('submitted','marked') $subjFilter
         ORDER BY a.submitted_at DESC LIMIT 10",
        array_merge([$studentId], $subjParam)
    );

    respond([
        'student_id'   => $studentId,
        'sub_strands'  => $tagged,
        'hard_questions'=> $hardQuestions,
        'trend'        => array_reverse($trend),
        'summary'      => [
            'critical_count'   => count(array_filter($tagged, fn($r) => $r['status'] === 'critical')),
            'needs_work_count' => count(array_filter($tagged, fn($r) => $r['status'] === 'needs_work')),
            'strong_count'     => count(array_filter($tagged, fn($r) => $r['status'] === 'good')),
        ],
    ]);
}
