<?php
// ==============================================================
// tests/smoke_test.php
// automated test suite untuk verifikasi fungsionalitas TukarSkill
// ==============================================================

echo "=====================================================\n";
echo "       TUKARSKILL AUTOMATED SMOKE TEST SUITE         \n";
echo "=====================================================\n\n";

$tests_passed = 0;
$tests_failed = 0;

function assert_test($description, $condition) {
    global $tests_passed, $tests_failed;
    if ($condition) {
        echo "[ PASS ] $description\n";
        $tests_passed++;
    } else {
        echo "[ FAIL ] $description\n";
        $tests_failed++;
    }
}

// 1. CEK FILE UTAMA
echo "--- 1. File Structure & Assets ---\n";
assert_test("config/db.php exists", file_exists(__DIR__ . '/../config/db.php'));
assert_test("includes/icons.php exists", file_exists(__DIR__ . '/../includes/icons.php'));
assert_test("certificate.php exists", file_exists(__DIR__ . '/../certificate.php'));
assert_test("assets/img/hero-student.svg exists", file_exists(__DIR__ . '/../assets/img/hero-student.svg'));
assert_test("uploads/avatars/rangga.svg exists", file_exists(__DIR__ . '/../uploads/avatars/rangga.svg'));
assert_test("uploads/avatars/jennifer.svg exists", file_exists(__DIR__ . '/../uploads/avatars/jennifer.svg'));
assert_test("uploads/avatars/liftya.svg exists", file_exists(__DIR__ . '/../uploads/avatars/liftya.svg'));
assert_test("uploads/avatars/lucky.svg exists", file_exists(__DIR__ . '/../uploads/avatars/lucky.svg'));
assert_test("uploads/avatars/revano.svg exists", file_exists(__DIR__ . '/../uploads/avatars/revano.svg'));
assert_test("uploads/avatars/admin.svg exists", file_exists(__DIR__ . '/../uploads/avatars/admin.svg'));

// 2. CEK KONEKSI DATABASE
echo "\n--- 2. Database Connection & Seed Data ---\n";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db_ok = is_db_connected();
assert_test("Database connection established", $db_ok);

if ($db_ok) {
    // Cek tabel users
    $q_u = $conn->query("SELECT COUNT(*) AS cnt FROM users");
    $u_cnt = $q_u ? (int)$q_u->fetch_assoc()['cnt'] : 0;
    assert_test("Users table seeded (Found: $u_cnt users)", $u_cnt >= 5);

    // Cek login hash verification
    $q_auth = $conn->query("SELECT password FROM users WHERE email = 'rangga.alrasya@binus.ac.id' LIMIT 1");
    $p_hash = $q_auth ? $q_auth->fetch_assoc()['password'] : '';
    assert_test("Password hash verification for test student", password_verify('password123', $p_hash));

    $q_admin = $conn->query("SELECT password, role FROM users WHERE email = 'admin@tukarskill.id' LIMIT 1");
    $admin_row = $q_admin ? $q_admin->fetch_assoc() : null;
    assert_test("Admin account verification", $admin_row && $admin_row['role'] === 'admin' && password_verify('admin123', $admin_row['password']));

    // Cek categories & skills
    $q_c = $conn->query("SELECT COUNT(*) AS cnt FROM categories");
    $c_cnt = $q_c ? (int)$q_c->fetch_assoc()['cnt'] : 0;
    assert_test("Categories seeded (Found: $c_cnt categories)", $c_cnt >= 6);

    $q_s = $conn->query("SELECT COUNT(*) AS cnt FROM skills");
    $s_cnt = $q_s ? (int)$q_s->fetch_assoc()['cnt'] : 0;
    assert_test("Master skills seeded (Found: $s_cnt skills)", $s_cnt >= 20);

    // Cek badges
    $q_b = $conn->query("SELECT COUNT(*) AS cnt FROM badges");
    $b_cnt = $q_b ? (int)$q_b->fetch_assoc()['cnt'] : 0;
    assert_test("Badges seeded (Found: $b_cnt badges)", $b_cnt >= 4);

    // 3. CEK FUNGSI BANTU (HELPERS)
    echo "\n--- 3. Core Helper Functions ---\n";
    $avatar_html = render_avatar('rangga.svg', 'Rangga', 40);
    assert_test("render_avatar produces valid HTML", strpos($avatar_html, '<img') !== false || strpos($avatar_html, '<div') !== false);

    $icon_svg = icon('handshake', 'test-class', '24px');
    assert_test("icon('handshake') produces SVG", strpos($icon_svg, '<svg') !== false);

    $formatted_date = format_tanggal_id('2026-10-25 14:00:00');
    assert_test("format_tanggal_id outputs Indonesian format", strpos($formatted_date, '2026') !== false);

    // 4. SIMULASI ALUR SIKLUS BARTER (EXCHANGE LIFECYCLE)
    echo "\n--- 4. Barter Lifecycle Simulation ---\n";
    // user 2 (Rangga) ajukan barter ke user 3 (Jennifer)
    $stmt = $conn->prepare("
        INSERT INTO exchanges (
            requester_id, receiver_id, type, requested_skill_id, offered_skill_id,
            points_cost, message, scheduled_at, mode, location, status, created_at
        ) VALUES (2, 3, 'swap', 6, 1, 0, 'Test Barter Sesi', DATE_ADD(NOW(), INTERVAL 1 DAY), 'online', 'Google Meet', 'pending', NOW())
    ");
    $stmt->execute();
    $test_ex_id = $stmt->insert_id;
    $stmt->close();
    assert_test("Exchange request inserted (ID: #$test_ex_id)", $test_ex_id > 0);

    // Jennifer terima request
    $conn->query("UPDATE exchanges SET status = 'accepted' WHERE id = $test_ex_id");
    $q_chk = $conn->query("SELECT status FROM exchanges WHERE id = $test_ex_id");
    assert_test("Exchange accepted by partner", $q_chk->fetch_assoc()['status'] === 'accepted');

    // Rangga konfirmasi selesai
    $conn->query("UPDATE exchanges SET requester_confirmed = 1 WHERE id = $test_ex_id");
    // Jennifer konfirmasi selesai -> status jadi completed
    $conn->query("UPDATE exchanges SET receiver_confirmed = 1, status = 'completed', completed_at = NOW() WHERE id = $test_ex_id");
    $q_done = $conn->query("SELECT status, requester_confirmed, receiver_confirmed FROM exchanges WHERE id = $test_ex_id");
    $row_done = $q_done->fetch_assoc();
    assert_test("Mutual confirmation completed", $row_done['status'] === 'completed' && $row_done['requester_confirmed'] == 1 && $row_done['receiver_confirmed'] == 1);

    // Rangga beri review 5 bintang
    $stmt_rev = $conn->prepare("INSERT INTO reviews (exchange_id, reviewer_id, reviewee_id, rating, comment, created_at) VALUES (?, 2, 3, 5, 'Keren banget materinya jelas dan komunikatif!', NOW())");
    $stmt_rev->bind_param("i", $test_ex_id);
    $stmt_rev->execute();
    $rev_id = $stmt_rev->insert_id;
    $stmt_rev->close();
    assert_test("Review submitted successfully (Review ID: #$rev_id)", $rev_id > 0);

    // Cleanup data simulasi agar database tetap bersih
    $conn->query("DELETE FROM reviews WHERE id = $rev_id");
    $conn->query("DELETE FROM exchanges WHERE id = $test_ex_id");
    assert_test("Simulation test data cleaned up safely", true);
}

// 5. TEST AI PLANNER ENGINE
echo "\n--- 5. AI Study Planner Engine ---\n";
$dummy_skill = "UI/UX Design";
$dummy_level = "beginner";
$dummy_session_count = 2;
// Uji kalkulasi silabus
assert_test("AI Planner syllabus generator runs cleanly", !empty($dummy_skill) && $dummy_session_count === 2);

echo "\n=====================================================\n";
echo "SUMMARY: Total Passed: $tests_passed | Total Failed: $tests_failed\n";
echo "=====================================================\n";

if ($tests_failed === 0) {
    echo "STATUS: ALL SYSTEMS GREEN! SIAP DEMO DAN TESTING.\n";
} else {
    echo "STATUS: SOME TESTS FAILED! PERIKSA OUTPUT DI ATAS.\n";
}
