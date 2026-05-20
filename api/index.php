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
        case 'ai-generate':  handleAIGenerate($method, $body); break;
        case 'questions-bulk': handleBulkQuestions($method, $body); break;
        case 'live':       handleLive($method, $parts, $body); break;
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
    $action = $parts[1] ?? '';

    // POST /auth/login
    if ($method === 'POST' && $action === 'login') {
        ['email' => $email, 'password' => $password] = need($body, 'email', 'password');
        $user = DB::fetchOne(
            'SELECT id, school_id, role, first_name, last_name, email, password_hash, avatar_color, is_active
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
        unset($user['password_hash']);
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
            'SELECT id, school_id, role, first_name, last_name, email, class_name, avatar_color, last_login
             FROM users WHERE id = ?', [$auth['user_id']]
        );
        if (!$user) err('User not found', 404);
        respond($user);
    }

    // POST /auth/change-password
    if ($method === 'POST' && $action === 'change-password') {
        $auth = require_auth();
        ['current_password'=>$cur,'new_password'=>$new] = need($body,'current_password','new_password');
        $user = DB::fetchOne('SELECT password_hash FROM users WHERE id = ?', [$auth['user_id']]);
        if (!password_verify($cur, $user['password_hash'])) err('Current password incorrect', 401);
        if (strlen($new) < 8) err('New password must be at least 8 characters');
        DB::execute('UPDATE users SET password_hash = ? WHERE id = ?',
            [password_hash($new, PASSWORD_BCRYPT, ['cost'=>12]), $auth['user_id']]);
        respond(['message' => 'Password changed']);
    }

    err("Auth action '$action' not found", 404);
}

// ══════════════════════════════════════════════════════════════
// QUESTIONS
// ══════════════════════════════════════════════════════════════
function handleQuestions(string $method, ?int $id, ?string $sub, array $body): void {
    $auth = require_auth();

    if ($method === 'GET' && !$id) {
        $where  = ['(q.school_id IS NULL OR q.school_id = ?)'];
        $params = [$auth['school_id']];
        if (!empty($_GET['year_group']))  { $where[] = 'q.year_group = ?';  $params[] = (int)$_GET['year_group']; }
        if (!empty($_GET['sub_strand']))  { $where[] = 'q.sub_strand = ?';  $params[] = $_GET['sub_strand']; }
        if (!empty($_GET['difficulty']))  { $where[] = 'q.difficulty = ?';  $params[] = $_GET['difficulty']; }
        if (!empty($_GET['type']))        { $where[] = 'q.type = ?';        $params[] = $_GET['type']; }
        if (!empty($_GET['search']))      { $where[] = 'q.question_text LIKE ?'; $params[] = '%'.$_GET['search'].'%'; }
        $limit  = min(100, max(1, (int)($_GET['limit'] ?? 50)));
        $offset = ((int)($_GET['page'] ?? 1) - 1) * $limit;
        $where  = implode(' AND ', $where);
        $total  = DB::fetchOne("SELECT COUNT(*) AS n FROM questions q WHERE $where AND q.is_active=1", $params)['n'] ?? 0;
        // $items  = DB::fetchAll("SELECT q.*,
        //     (SELECT JSON_ARRAYAGG(JSON_OBJECT('label',option_label,'text',option_text,'correct',is_correct))
        //      FROM question_options WHERE question_id=q.id ORDER BY sort_order) AS options
        //     FROM questions q WHERE $where AND q.is_active=1
        //     ORDER BY q.id LIMIT $limit OFFSET $offset", $params);
        $items = DB::fetchAll("SELECT q.id, q.school_id, q.type, q.sub_strand, q.topic,
            q.bloom_level, q.difficulty, q.year_group, q.marks, q.question_text,
            q.explanation, q.rubric, q.author_id, q.created_at,
            (SELECT GROUP_CONCAT(option_label,'|',option_text,'|',is_correct ORDER BY sort_order SEPARATOR ';;')
            FROM question_options WHERE question_id=q.id) AS options_raw
            FROM questions q WHERE $where AND q.is_active=1
            ORDER BY q.id LIMIT $limit OFFSET $offset", $params);
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
        $d = need($body, 'type', 'sub_strand', 'topic', 'question_text');
        DB::beginTransaction();
        try {
            $qid = DB::insert(
                'INSERT INTO questions (school_id,author_id,type,sub_strand,topic,bloom_level,difficulty,year_group,marks,question_text,explanation,rubric)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                [$auth['school_id'],$auth['user_id'],$d['type'],$d['sub_strand'],
                 $d['topic'],$body['bloom_level']??'Remember',$body['difficulty']??'Medium',
                 (int)($body['year_group']??1),(int)($body['marks']??1),
                 $d['question_text'],$body['explanation']??null,$body['rubric']??null]
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
            respond(['question_id' => $qid], 201);
        } catch (Throwable $e) { DB::rollback(); throw $e; }
    }

    if ($method === 'PUT' && $id) {
        require_role($auth, 'teacher', 'admin');
        $d = need($body, 'question_text', 'sub_strand', 'topic');

        // Verify ownership (school or platform question the teacher authored)
        $existing = DB::fetchOne('SELECT id, author_id FROM questions WHERE id=?', [$id]);
        if (!$existing) err('Question not found', 404);

        DB::execute(
            'UPDATE questions SET question_text=?, sub_strand=?, topic=?, bloom_level=?,
             difficulty=?, year_group=?, marks=?, explanation=?, rubric=?, type=?, updated_at=NOW()
             WHERE id=?',
            [
                $d['question_text'], $d['sub_strand'], $d['topic'],
                $body['bloom_level'] ?? 'Remember', $body['difficulty'] ?? 'Medium',
                (int)($body['year_group'] ?? 1), (int)($body['marks'] ?? 1),
                $body['explanation'] ?? null, $body['rubric'] ?? null,
                $body['type'] ?? 'mcq', $id,
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
        $existing = DB::fetchOne('SELECT id FROM questions WHERE id=?', [$id]);
        if (!$existing) err('Question not found', 404);
        DB::execute('UPDATE questions SET is_active=0 WHERE id=?', [$id]);
        respond(['message' => 'Question deleted']);
    }

    err('Questions endpoint error', 404);
}

// ══════════════════════════════════════════════════════════════
// TESTS
// ══════════════════════════════════════════════════════════════
function handleTests(string $method, ?int $id, ?string $sub, array $body): void {
    $auth = require_auth();

    if ($method === 'GET' && !$id) {
        if ($auth['role'] === 'student') {
            $uid = $auth['user_id'];
            // Get all published tests — both directly assigned and via class
            // Also include ALL published tests if no assignments exist (fallback for demo)
            $tests = DB::fetchAll(
                "SELECT DISTINCT t.id, t.title, t.type, t.status, t.time_limit_min, t.max_attempts,
                        t.show_feedback, t.available_from, t.due_at,
                        (SELECT COUNT(*) FROM attempts WHERE test_id=t.id AND student_id=?) AS my_attempts,
                        (SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) AS question_count
                 FROM tests t
                 LEFT JOIN test_assignments ta ON ta.test_id=t.id
                 LEFT JOIN class_students cs ON cs.class_id=ta.class_id AND cs.student_id=?
                 WHERE t.status='published'
                   AND (ta.id IS NULL OR ta.student_id=? OR cs.student_id=?)
                   AND (t.available_from IS NULL OR t.available_from<=NOW())
                 ORDER BY t.due_at IS NULL, t.due_at ASC",
                [$uid,$uid,$uid,$uid]
            );
            respond($tests);
        } else {
            respond(DB::fetchAll(
                'SELECT t.*, (SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) AS question_count
                 FROM tests t WHERE t.school_id=? ORDER BY t.created_at DESC',
                [$auth['school_id']]
            ));
        }
    }

    if ($method === 'GET' && $id && !$sub) {
        // Students can access published tests; teachers/admins restricted to own school
        if ($auth['role'] === 'student') {
            $test = DB::fetchOne('SELECT * FROM tests WHERE id=? AND status="published"', [$id]);
        } else {
            $test = DB::fetchOne('SELECT * FROM tests WHERE id=? AND school_id=?', [$id, $auth['school_id']]);
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
        respond($test);
    }

    if ($method === 'GET' && $id && $sub === 'results') {
        require_role($auth, 'teacher', 'admin');
        $attempts = DB::fetchAll(
            'SELECT a.*, CONCAT(u.first_name," ",u.last_name) AS student_name, u.class_name
             FROM attempts a JOIN users u ON u.id=a.student_id
             WHERE a.test_id=? ORDER BY a.submitted_at DESC', [$id]
        );
        $stats = DB::fetchOne(
            'SELECT COUNT(*) AS total,
                    AVG(ROUND(IF(max_score>0,(score_auto+score_manual)/max_score*100,0),2)) AS avg_pct,
                    MAX(ROUND(IF(max_score>0,(score_auto+score_manual)/max_score*100,0),2)) AS max_pct,
                    SUM(ROUND(IF(max_score>0,(score_auto+score_manual)/max_score*100,0),2)>=50) AS pass_count
             FROM attempts WHERE test_id=? AND status IN ("submitted","marked")',
            [$id]
        );
        respond(['attempts'=>$attempts,'stats'=>$stats]);
    }

    if ($method === 'POST') {
        require_role($auth, 'teacher', 'admin');
        $d = need($body, 'title');
        $tid = DB::insert(
            'INSERT INTO tests (school_id,creator_id,title,type,time_limit_min,max_attempts,randomise_qs,randomise_opts,show_feedback,available_from,due_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)',
            [$auth['school_id'],$auth['user_id'],trim($d['title']),$body['type']??'quiz',
             (int)($body['time_limit_min']??0),(int)($body['max_attempts']??1),
             (int)($body['randomise_qs']??1),(int)($body['randomise_opts']??1),
             (int)($body['show_feedback']??1),$body['available_from']??null,$body['due_at']??null]
        );
        if (!empty($body['question_ids'])) {
            foreach ($body['question_ids'] as $order => $qid)
                DB::execute('INSERT INTO test_questions (test_id,question_id,sort_order) VALUES (?,?,?)',
                    [$tid,(int)$qid,$order]);
        }
        if (!empty($body['class_ids'])) {
            foreach ($body['class_ids'] as $cid)
                DB::execute('INSERT INTO test_assignments (test_id,class_id) VALUES (?,?)', [$tid,(int)$cid]);
        }
        respond(['test_id'=>$tid,'message'=>'Test created'], 201);
    }

    if ($method === 'PATCH' && $id) {
        require_role($auth, 'teacher', 'admin');
        $allowed = ['title','status','time_limit_min','max_attempts','randomise_qs','randomise_opts','show_feedback','due_at','available_from','description','type'];
        $sets=[]; $params=[];
        foreach ($allowed as $f) {
            if (array_key_exists($f,$body)) { $sets[]="$f=?"; $params[]=$body[$f]; }
        }
        if ($sets) {
            $params[]=$id; $params[]=$auth['school_id'];
            DB::execute('UPDATE tests SET '.implode(',',$sets).',updated_at=NOW() WHERE id=? AND school_id=?', $params);
        }
        // Replace questions if provided
        if (isset($body['question_ids']) && is_array($body['question_ids'])) {
            DB::execute('DELETE FROM test_questions WHERE test_id=?', [$id]);
            foreach ($body['question_ids'] as $order => $qid) {
                if ($qid) DB::execute(
                    'INSERT INTO test_questions (test_id,question_id,sort_order) VALUES (?,?,?)',
                    [$id, (int)$qid, $order]
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
        $a['answers'] = DB::fetchAll('SELECT question_id,selected_opts,text_response,is_flagged,marks_awarded,teacher_feedback,is_correct FROM answers WHERE attempt_id=?', [$id]);
        respond($a);
    }

    if ($method === 'PATCH' && $id && $sub === 'submit') {
        $a = DB::fetchOne('SELECT * FROM attempts WHERE id=?', [$id]);
        if (!$a) err('Attempt not found', 404);
        if ($auth['role']==='student' && $a['student_id']!=$auth['user_id']) err('Forbidden',403);
        if ($a['status']!=='in_progress') err('Already submitted');
        $timeTaken = (int)($body['time_taken_s'] ?? (time()-strtotime($a['started_at'])));
        DB::execute('UPDATE attempts SET status="submitted",submitted_at=NOW(),time_taken_s=? WHERE id=?', [$timeTaken,$id]);
        // Auto-mark MCQ answers
        $mcqAnswers = DB::fetchAll(
            'SELECT an.id,an.question_id,an.selected_opts FROM answers an
             JOIN questions q ON q.id=an.question_id
             WHERE an.attempt_id=? AND q.type IN ("mcq","tf") AND an.is_correct IS NULL', [$id]
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
        respond(array_merge($result??[], ['message'=>'Submitted','unmarked_essays'=>(int)$unmarked]));
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

    if ($method === 'GET' && !$id && $sub === 'dashboard') {
        $uid = $auth['user_id'];
        $stats   = DB::fetchOne(
            'SELECT COUNT(*) AS tests_done,
                    AVG(ROUND(IF(max_score>0,(score_auto+score_manual)/max_score*100,0),2)) AS avg_pct,
                    MAX(ROUND(IF(max_score>0,(score_auto+score_manual)/max_score*100,0),2)) AS best_pct
             FROM attempts WHERE student_id=? AND status IN ("submitted","marked")',
            [$uid]
        );
        $streak  = DB::fetchOne('SELECT current_streak,longest_streak,total_xp FROM streaks WHERE student_id=?', [$uid]);
        $subAvg  = DB::fetchAll('SELECT q.sub_strand,AVG(an.is_correct*q.marks/q.marks*100) AS avg_pct FROM answers an JOIN questions q ON q.id=an.question_id JOIN attempts a ON a.id=an.attempt_id WHERE a.student_id=? AND an.marks_awarded IS NOT NULL GROUP BY q.sub_strand', [$uid]);
        $due     = DB::fetchAll("SELECT DISTINCT t.id,t.title,t.due_at,t.time_limit_min,(SELECT COUNT(*) FROM test_questions WHERE test_id=t.id) AS question_count,(SELECT COUNT(*) FROM attempts WHERE test_id=t.id AND student_id=?) AS my_attempts FROM tests t JOIN test_assignments ta ON ta.test_id=t.id LEFT JOIN class_students cs ON cs.class_id=ta.class_id AND cs.student_id=? WHERE t.status='published' AND (ta.student_id=? OR cs.student_id IS NOT NULL) AND (t.due_at IS NULL OR t.due_at>NOW()) GROUP BY t.id HAVING my_attempts=0 ORDER BY t.due_at IS NULL,t.due_at ASC LIMIT 5", [$uid,$uid,$uid]);
        $recent = DB::fetchAll(
            'SELECT a.id, t.title,
                    ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),2) AS pct_score,
                    a.submitted_at
             FROM attempts a JOIN tests t ON t.id=a.test_id
             WHERE a.student_id=? AND a.status IN ("submitted","marked")
             ORDER BY a.submitted_at DESC LIMIT 5',
            [$uid]
        );
        $srDue   = DB::fetchOne('SELECT COUNT(*) AS n FROM spaced_repetition WHERE student_id=? AND next_review<=CURDATE()', [$uid])['n'] ?? 0;
        // Rename keys to match frontend expectations
        respond([
            'stats'          => $stats,
            'streak'         => $streak,
            'substrand_avg'  => $subAvg,
            'due'            => $due,
            'recent'         => $recent,
            'sr_due'         => (int)$srDue,
        ]);
    }

    if ($method === 'GET' && $id) {
        require_role($auth,'teacher','admin');
        $s = DB::fetchOne('SELECT id,first_name,last_name,email,class_name,avatar_color,last_login FROM users WHERE id=? AND school_id=? AND role="student"', [$id,$auth['school_id']]);
        if (!$s) err('Student not found',404);
        $s['substrand_avg']   = DB::fetchAll('SELECT q.sub_strand,AVG(a.pct_score) AS avg_pct FROM attempts a JOIN test_questions tq ON tq.test_id=a.test_id JOIN questions q ON q.id=tq.question_id WHERE a.student_id=? AND a.status IN ("submitted","marked") GROUP BY q.sub_strand', [$id]);
        $s['streak']          = DB::fetchOne('SELECT * FROM streaks WHERE student_id=?', [$id]);
        $s['recent_attempts'] = DB::fetchAll(
            'SELECT a.id, t.title,
                    ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),2) AS pct_score,
                    a.submitted_at
             FROM attempts a JOIN tests t ON t.id=a.test_id
             WHERE a.student_id=? ORDER BY a.submitted_at DESC LIMIT 8',
            [$id]
        );
        respond($s);
    }

    if ($method === 'GET' && !$id) {
        require_role($auth,'teacher','admin');
        $students = DB::fetchAll(
            "SELECT u.id,u.first_name,u.last_name,u.class_name,u.avatar_color,
                    s.current_streak,s.total_xp,
                    (SELECT COUNT(*) FROM attempts WHERE student_id=u.id AND status IN ('submitted','marked')) AS tests_done,
                    (SELECT AVG(ROUND(IF(max_score>0,(score_auto+score_manual)/max_score*100,0),2)) FROM attempts WHERE student_id=u.id AND status IN ('submitted','marked')) AS avg_pct
             FROM users u LEFT JOIN streaks s ON s.student_id=u.id
             WHERE u.school_id=? AND u.role='student'
             ORDER BY u.class_name,u.last_name", [$auth['school_id']]
        );
        respond($students);
    }

    err('Students endpoint error',404);
}

// ══════════════════════════════════════════════════════════════
// CLASSES
// ══════════════════════════════════════════════════════════════
function handleClasses(string $method, ?int $id, ?string $sub, array $body): void {
    $auth = require_auth();
    require_role($auth,'teacher','admin');

    if ($method === 'GET' && !$id) {
        respond(DB::fetchAll(
            'SELECT c.*,(SELECT COUNT(*) FROM class_students WHERE class_id=c.id) AS student_count,
                    (SELECT AVG(a.pct_score) FROM attempts a JOIN class_students cs ON cs.student_id=a.student_id WHERE cs.class_id=c.id AND a.status IN ("submitted","marked")) AS class_avg
             FROM classes c WHERE c.school_id=? ORDER BY c.year_group,c.name', [$auth['school_id']]
        ));
    }

    if ($method === 'POST') {
        $d = need($body,'name','year_group');
        $cid = DB::insert('INSERT INTO classes (school_id,teacher_id,name,year_group) VALUES (?,?,?,?)',
            [$auth['school_id'],$auth['user_id'],trim($d['name']),(int)$d['year_group']]);
        respond(['class_id'=>$cid],201);
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

    if ($sub === 'school') {
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
        $by_substrand = DB::fetchAll('SELECT q.sub_strand,AVG(an.marks_awarded/q.marks*100) AS avg_pct FROM answers an JOIN questions q ON q.id=an.question_id JOIN attempts a ON a.id=an.attempt_id JOIN users u ON u.id=a.student_id WHERE u.school_id=? AND an.marks_awarded IS NOT NULL GROUP BY q.sub_strand ORDER BY avg_pct ASC', [$sid]);
        $at_risk = DB::fetchAll(
            'SELECT u.id, u.first_name, u.last_name, u.class_name,
                    AVG(ROUND(IF(a.max_score>0,(a.score_auto+a.score_manual)/a.max_score*100,0),2)) AS avg_pct
             FROM users u JOIN attempts a ON a.student_id=u.id
             WHERE u.school_id=? AND a.status IN ("submitted","marked")
             GROUP BY u.id HAVING avg_pct<50 ORDER BY avg_pct ASC LIMIT 20',
            [$sid]
        );
        respond(compact('overview','by_substrand','at_risk'));
    }

    if ($sub === 'marking-queue') {
        $queue = DB::fetchAll(
            'SELECT an.id AS answer_id,an.attempt_id,an.question_id,an.text_response,
                    u.first_name,u.last_name,u.class_name,t.title AS test_title,
                    q.sub_strand,q.marks AS max_marks,q.rubric
             FROM answers an
             JOIN attempts a ON a.id=an.attempt_id
             JOIN users u ON u.id=a.student_id
             JOIN tests t ON t.id=a.test_id
             JOIN questions q ON q.id=an.question_id
             WHERE q.type="essay" AND an.is_correct IS NULL AND a.status="submitted"
               AND t.school_id=?
             ORDER BY an.id', [$sid]
        );
        respond($queue);
    }

    err('Analytics endpoint error',404);
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
            $exp    = trim($q['explanation'] ?? '');

            // Validate difficulty
            if (!in_array($diff, ['Easy','Medium','Hard'])) $diff = 'Medium';
            if (!in_array($type, ['mcq','essay','short','tf'])) $type = 'mcq';

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

            // Insert options A/B/C/D
            $opts = [];
            // Support both {A:'text', B:'text'} and [{label,text,correct}] formats
            if (isset($q['A']) || isset($q['option_a'])) {
                // CSV flat format: A, B, C, D columns + correct column
                $correct = strtoupper(trim($q['correct'] ?? $q['answer'] ?? 'A'));
                foreach (['A','B','C','D'] as $lbl) {
                    $val = trim($q[$lbl] ?? $q['option_'.strtolower($lbl)] ?? '');
                    if ($val) $opts[] = ['label'=>$lbl,'text'=>$val,'correct'=>$lbl===$correct?1:0];
                }
            } elseif (!empty($q['options']) && is_array($q['options'])) {
                foreach ($q['options'] as $j => $o) {
                    $opts[] = [
                        'label'   => $o['label'] ?? $letters[$j] ?? $letters[0],
                        'text'    => trim($o['text'] ?? ''),
                        'correct' => (int)($o['correct'] ?? ($o['is_correct'] ?? 0)),
                    ];
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
            'SELECT ls.*, t.title AS test_title FROM live_sessions ls JOIN tests t ON t.id=ls.test_id WHERE ls.room_code=?',
            [$code]
        );
        if (!$sess) err('Room not found', 404);

        $participants = DB::fetchAll(
            'SELECT lp.student_id, lp.score, lp.last_q_answered, u.first_name, u.last_name, u.avatar_color
             FROM live_participants lp JOIN users u ON u.id=lp.student_id
             WHERE lp.session_id=? ORDER BY lp.score DESC',
            [$sess['id']]
        );

        $question    = null;
        $answeredCnt = 0;
        $totalQ      = (int)(DB::fetchOne('SELECT COUNT(*) AS n FROM test_questions WHERE test_id=?', [$sess['test_id']])['n'] ?? 0);

        if ($sess['status'] === 'active' && $sess['current_q'] >= 0) {
            $hideAnswers = ($auth['role'] === 'student');
            $question = DB::fetchOne(
                "SELECT q.id, q.question_text, q.marks, q.sub_strand, q.difficulty,
                        (SELECT JSON_ARRAYAGG(JSON_OBJECT('id',qo.id,'label',qo.option_label,'text',qo.option_text" .
                        ($hideAnswers ? '' : ",'correct',qo.is_correct") . "))
                         FROM question_options qo WHERE qo.question_id=q.id ORDER BY qo.sort_order) AS options
                 FROM test_questions tq JOIN questions q ON q.id=tq.question_id
                 WHERE tq.test_id=? ORDER BY tq.sort_order LIMIT 1 OFFSET " . (int)$sess['current_q'],
                [$sess['test_id']]
            );
            $answeredCnt = (int)(DB::fetchOne(
                'SELECT COUNT(*) AS n FROM live_answers WHERE session_id=? AND q_index=?',
                [$sess['id'], $sess['current_q']]
            )['n'] ?? 0);
        }

        // For student: was their answer correct for current question?
        $myAnswer = null;
        if ($auth['role'] === 'student' && $sess['status'] === 'active') {
            $myAnswer = DB::fetchOne(
                'SELECT selected_opt, is_correct FROM live_answers WHERE session_id=? AND student_id=? AND q_index=?',
                [$sess['id'], $auth['user_id'], $sess['current_q']]
            );
        }

        respond([
            'session'           => $sess,
            'participants'      => $participants,
            'participant_count' => count($participants),
            'question'          => $question,
            'answered_count'    => $answeredCnt,
            'total_questions'   => $totalQ,
            'my_answer'         => $myAnswer,
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
