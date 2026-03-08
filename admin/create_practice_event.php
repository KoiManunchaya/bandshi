<?php
session_start();
require_once __DIR__.'/../db.php';
require_once __DIR__.'/admin_guard.php';
require_once __DIR__.'/auto_schedule.php';

$songId = (int)$_POST['song_id'];
$weekStart = $_POST['week_start'];

$slots = ['afternoon','evening','night'];

for ($i=0; $i<7; $i++) {

    $date = date('Y-m-d', strtotime($weekStart." +$i days"));

    // ===== get members =====
    $stmt = $conn->prepare("
        SELECT u.id AS user_id, u.part, u.instrument
        FROM song_members sm
        JOIN users u ON sm.user_id = u.id
        WHERE sm.song_id = ?
    ");
    $stmt->bind_param("i",$songId);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($slots as $slot) {

        // availability per slot
        $stmt = $conn->prepare("
            SELECT user_id FROM availability
            WHERE date = ? AND slot = ?
        ");
        $stmt->bind_param("ss",$date,$slot);
        $stmt->execute();
        $available = array_column(
            $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
            'user_id'
        );

        $details = [];
        $can = canPractice($members,$available,$details);

        if ($can) {

            // insert session
            $stmt = $conn->prepare("
                INSERT IGNORE INTO practice_sessions
                (song_id,date,time_slot,score)
                VALUES(?,?,?,?)
            ");
            $score = count($available);
            $stmt->bind_param("issi",$songId,$date,$slot,$score);
            $stmt->execute();

            $sessionId = $stmt->insert_id;

            if ($sessionId > 0) {

                // link members
                foreach ($members as $m) {
                    $stmt2 = $conn->prepare("
                        INSERT INTO practice_session_members
                        (practice_session_id,user_id)
                        VALUES(?,?)
                    ");
                    $stmt2->bind_param("ii",$sessionId,$m['user_id']);
                    $stmt2->execute();
                }

                // notify
                foreach ($members as $m) {
                    $msg = "Practice scheduled: $date ($slot)";
                    $stmt3 = $conn->prepare("
                        INSERT INTO notifications(user_id,message)
                        VALUES(?,?)
                    ");
                    $stmt3->bind_param("is",$m['user_id'],$msg);
                    $stmt3->execute();
                }
            }
        }
    }
}

header("Location: schedules.php");
exit();