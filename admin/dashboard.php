<?php
session_start();
require_once __DIR__.'/../db.php';
require_once __DIR__.'/admin_guard.php';

/*
    Dashboard rewrite
    - total members + part breakdown
    - top 5 joiners with funny awards
    - low availability < 20%
    - event/song history list
    - compact member ranking with internal scroll
    - CSV export
*/

/* ============================
   FILTER
============================ */
$cohort = $_GET['cohort'] ?? '';
$part   = $_GET['part'] ?? '';
$export = $_GET['export'] ?? '';

/* ============================
   DISPLAY NAME FALLBACK
============================ */
$hasDisplayName = false;
$hasNickname = false;

$colCheck = $conn->query("SHOW COLUMNS FROM users");
if ($colCheck) {
    while ($c = $colCheck->fetch_assoc()) {
        if (($c['Field'] ?? '') === 'display_name') $hasDisplayName = true;
        if (($c['Field'] ?? '') === 'nickname') $hasNickname = true;
    }
}

$nameExprParts = [];
if ($hasDisplayName) $nameExprParts[] = "NULLIF(TRIM(u.display_name), '')";
if ($hasNickname)    $nameExprParts[] = "NULLIF(TRIM(u.nickname), '')";
$nameExprParts[] = "u.full_name";

$memberNameExpr = "COALESCE(" . implode(', ', $nameExprParts) . ")";

/* ============================
   WHERE
============================ */
$where = " WHERE u.role='member' ";

if ($cohort !== '') $where .= " AND u.cohort='" . $conn->real_escape_string($cohort) . "'";
if ($part !== '')   $where .= " AND u.part='" . $conn->real_escape_string($part) . "'";

/* ============================
   EXPORT: MEMBERS CSV
============================ */
if ($export === 'members') {
    $sql = "
        SELECT
            {$memberNameExpr} AS member_name,
            u.full_name,
            u.cohort,
            u.part,
            COUNT(DISTINCT ej.event_id) AS join_count,
            ROUND((COUNT(DISTINCT a.date)/7)*100,2) AS availability_percent,
            (COUNT(DISTINCT ej.event_id)*2 + ROUND((COUNT(DISTINCT a.date)/7)*100,2)) AS activity_score
        FROM users u
        LEFT JOIN event_join ej ON u.id = ej.user_id
        LEFT JOIN availability a ON u.id = a.user_id
        $where
        GROUP BY u.id
        ORDER BY activity_score DESC, join_count DESC, member_name ASC
    ";

    $res = $conn->query($sql);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="member_ranking.csv"');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['Display Name', 'Full Name', 'Cohort', 'Part', 'Join Count', 'Availability %', 'Score']);

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            fputcsv($out, [
                $row['member_name'],
                $row['full_name'],
                $row['cohort'],
                $row['part'],
                $row['join_count'],
                $row['availability_percent'],
                $row['activity_score']
            ]);
        }
    }
    fclose($out);
    exit;
}

/* ============================
   EXPORT: EVENT SONG HISTORY CSV
============================ */
if ($export === 'event_songs') {
    $sql = "
        SELECT
            e.title AS event_title,
            e.event_date,
            s.name AS song_name,
            s.rehearsal_progress
        FROM events e
        LEFT JOIN songs s ON s.event_id = e.id
        ORDER BY e.event_date DESC, e.title ASC, s.name ASC
    ";

    $res = $conn->query($sql);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="event_song_history.csv"');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['Event', 'Event Date', 'Song', 'Progress']);

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            fputcsv($out, [
                $row['event_title'],
                $row['event_date'],
                $row['song_name'] ?? '',
                $row['rehearsal_progress'] ?? ''
            ]);
        }
    }
    fclose($out);
    exit;
}

/* ============================
   SUMMARY
============================ */
$total_members = (int)($conn->query("SELECT COUNT(*) c FROM users WHERE role='member'")->fetch_assoc()['c'] ?? 0);

$part_counts = [
    'singer' => 0,
    'musician' => 0,
    'dancer' => 0,
    'light&sound' => 0
];

$partRes = $conn->query("
    SELECT part, COUNT(*) c
    FROM users
    WHERE role='member'
    GROUP BY part
");
if ($partRes) {
    while ($row = $partRes->fetch_assoc()) {
        $key = strtolower(trim($row['part'] ?? ''));
        if (array_key_exists($key, $part_counts)) {
            $part_counts[$key] = (int)$row['c'];
        }
    }
}

/* ============================
   TOP ACTIVE
============================ */
$top_active = $conn->query("
    SELECT
        {$memberNameExpr} AS member_name,
        COUNT(DISTINCT ej.event_id) AS join_count
    FROM users u
    LEFT JOIN event_join ej ON u.id = ej.user_id
    $where
    GROUP BY u.id
    ORDER BY join_count DESC, member_name ASC
    LIMIT 5
");

/* ============================
   LOW AVAILABILITY < 20
============================ */
$low_availability = $conn->query("
    SELECT
        {$memberNameExpr} AS member_name,
        ROUND((COUNT(DISTINCT a.date)/7)*100,2) AS percent
    FROM users u
    LEFT JOIN availability a ON u.id = a.user_id
    $where
    GROUP BY u.id
    HAVING percent < 20
    ORDER BY percent ASC, member_name ASC
");

/* ============================
   EVENT SONG HISTORY
============================ */
$event_song_history = $conn->query("
    SELECT
        e.id,
        e.title,
        e.event_date,
        GROUP_CONCAT(
            DISTINCT CONCAT(
                s.name,
                CASE
                    WHEN s.rehearsal_progress IS NOT NULL THEN CONCAT(' (', s.rehearsal_progress, '%)')
                    ELSE ''
                END
            )
            ORDER BY s.name ASC
            SEPARATOR ' || '
        ) AS songs_list
    FROM events e
    LEFT JOIN songs s ON s.event_id = e.id
    GROUP BY e.id
    ORDER BY e.event_date DESC, e.title ASC
");

/* ============================
   MEMBER RANKING
============================ */
$members = $conn->query("
    SELECT
        {$memberNameExpr} AS member_name,
        u.full_name,
        u.cohort,
        u.part,
        COUNT(DISTINCT ej.event_id) AS join_count,
        COUNT(DISTINCT a.date) AS available_days,
        ROUND((COUNT(DISTINCT a.date)/7)*100,2) AS availability_percent,
        (COUNT(DISTINCT ej.event_id)*2 + ROUND((COUNT(DISTINCT a.date)/7)*100,2)) AS activity_score
    FROM users u
    LEFT JOIN event_join ej ON u.id = ej.user_id
    LEFT JOIN availability a ON u.id = a.user_id
    $where
    GROUP BY u.id
    ORDER BY activity_score DESC, join_count DESC, member_name ASC
");

/* ============================
   FILTER OPTIONS
============================ */
$cohort_options = $conn->query("
    SELECT DISTINCT cohort
    FROM users
    WHERE role='member' AND cohort IS NOT NULL AND cohort <> ''
    ORDER BY cohort ASC
");

$part_options = $conn->query("
    SELECT DISTINCT part
    FROM users
    WHERE role='member' AND part IS NOT NULL AND part <> ''
    ORDER BY part ASC
");

$awardTitles = [
    1 => '👑 Emperor of Every Stage',
    2 => '🥈 Grand Marshal of Attendance',
    3 => '🥉 Right-On-Time Royalty',
    4 => '🎖 Legendary Invite Hunter',
    5 => '🏅 Seasonal Regular'
];

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Analytics Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


<style>
:root{
    --bg:#0b1018;
    --panel:#171c27;
    --panel-2:#1d2330;
    --line:#30384a;
    --text:#f3f5f7;
    --muted:#aeb7c8;
    --accent:#38bdf8;
    --danger:#fb7185;
    --warn:#f59e0b;
    --ok:#22c55e;
}

*{
    box-sizing:border-box;
    min-width:0;
}

html,
body{
    height:100%;
    width:100%;
}

body{
    margin:0;
    background:linear-gradient(180deg,#090d14 0%,#0b1018 100%);
    color:var(--text);
    font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
    overflow-x:hidden;
}

.page{
    width:100%;
    max-width:none;
    margin:0;
    padding:28px 22px 34px;
}

/* ===== top ===== */
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
    margin-bottom:22px;
    flex-wrap:wrap;
}

.topbar h1{
    margin:0 0 8px;
    font-size:28px;
    font-weight:900;
    letter-spacing:-.02em;
}

.topbar p{
    margin:0;
    color:var(--muted);
    font-size:14px;
}

.top-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.action-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 14px;
    border-radius:12px;
    border:1px solid var(--line);
    background:#101726;
    color:#fff;
    text-decoration:none;
    font-weight:700;
    font-size:14px;
    transition:.18s ease;
}

.action-btn:hover{
    background:#162033;
    border-color:#44506a;
}

/* ===== filters ===== */
.filters{
    display:grid;
    grid-template-columns: 1fr 1fr auto auto;
    gap:12px;
    margin-bottom:18px;
    width:100%;
}

.filters select{
    width:100%;
    background:#121928;
    color:#fff;
    border:1px solid var(--line);
    border-radius:12px;
    padding:12px 14px;
    outline:none;
}

.filters button,
.filters a.filter-reset{
    border:none;
    border-radius:12px;
    padding:12px 16px;
    font-weight:800;
    text-decoration:none;
    text-align:center;
    white-space:nowrap;
}

.filters button{
    background:var(--accent);
    color:#08111f;
}

.filters a.filter-reset{
    background:#202838;
    color:#fff;
    border:1px solid var(--line);
}


/* ===== layout ===== */
.dashboard-grid{
    display:grid;
    grid-template-columns: minmax(0, 1.45fr) minmax(380px, .95fr);
    gap:18px;
    width:100%;
    align-items:start;
}

.left-col,
.right-col{
    display:grid;
    gap:18px;
    align-content:start;
    width:100%;
    min-width:0;
}

.info-row{
    grid-column:1 / -1;
    display:grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap:18px;
    align-items:start;
    width:100%;
}

.info-row .panel{
    width:100%;
    min-width:0;
    margin:0;
}
/* ===== common panel ===== */
.panel{
    width:100%;
    min-width:0;
    background:linear-gradient(135deg,var(--panel) 0%, var(--panel-2) 100%);
    border:1px solid rgba(255,255,255,.04);
    border-radius:22px;
    padding:22px;
    box-shadow:0 18px 40px rgba(0,0,0,.22);
}

.panel h3{
    margin:0 0 16px;
    font-size:20px;
    font-weight:900;
    letter-spacing:-.02em;
}

.panel-sub{
    margin-top:-8px;
    margin-bottom:14px;
    color:var(--muted);
    font-size:13px;
}

/* ===== summary ===== */
.stats-grid{
    display:grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap:14px;
}

.stat-card{
    background:#121928;
    border:1px solid var(--line);
    border-radius:18px;
    padding:18px;
}

.stat-title{
    color:var(--muted);
    font-size:13px;
    margin-bottom:8px;
    font-weight:700;
}

.stat-number{
    font-size:30px;
    font-weight:900;
    line-height:1;
}

.part-grid{
    display:grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap:12px;
    margin-top:14px;
}

.part-card{
    background:#111827;
    border:1px solid var(--line);
    border-radius:16px;
    padding:14px;
    text-align:center;
}

.part-label{
    color:var(--muted);
    font-size:12px;
    font-weight:700;
    margin-bottom:8px;
    text-transform:capitalize;
}

.part-value{
    font-size:24px;
    font-weight:900;
}

/* ===== top 5 ===== */
.top5-list{
    display:grid;
    gap:12px;
}

.top5-item{
    display:grid;
    grid-template-columns: 42px 1fr auto;
    gap:12px;
    align-items:center;
    background:#111827;
    border:1px solid var(--line);
    border-radius:16px;
    padding:14px 16px;
}

.medal{
    width:42px;
    height:42px;
    border-radius:999px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
    font-weight:900;
    background:#1d2638;
}

.top5-name{
    font-weight:900;
    font-size:16px;
}

.top5-award{
    color:var(--muted);
    font-size:13px;
    margin-top:4px;
}

.top5-join{
    font-weight:900;
    color:#fff;
    white-space:nowrap;
}

/* ===== low availability ===== */
.alert-box{
    border:1px solid rgba(251,113,133,.25);
    background:rgba(127,29,29,.15);
    border-radius:18px;
    padding:16px;
    margin-bottom:14px;
}

.alert-box strong{
    color:#ffd6dc;
}

.attention-list{
    display:grid;
    gap:10px;
}

.attention-item{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:center;
    background:#111827;
    border:1px solid var(--line);
    border-radius:14px;
    padding:12px 14px;
}

.attention-name{
    font-weight:800;
}

.attention-note{
    color:var(--muted);
    font-size:13px;
    margin-top:2px;
}

.attention-percent{
    font-weight:900;
    color:#fecdd3;
    white-space:nowrap;
}


/* ===== songs by event ===== */
.right-col > .panel{
    height:auto;
}

.event-song-list{
    display:grid;
    gap:14px;
    max-height:690px;
    overflow:auto;
    padding-right:4px;
}

.event-song-item{
    background:#111827;
    border:1px solid var(--line);
    border-radius:18px;
    padding:16px;
}

.event-song-head{
    display:flex;
    justify-content:space-between;
    gap:12px;
    margin-bottom:12px;
    align-items:flex-start;
    flex-wrap:wrap;
}

.event-song-title{
    font-weight:900;
    font-size:18px;
    line-height:1.2;
}

.event-song-date{
    color:var(--muted);
    font-size:13px;
    font-weight:700;
}

.song-sheet{
    width:100%;
    border:1px solid #334155;
    border-radius:14px;
    overflow:hidden;
    background:#0f1724;
}

.song-sheet-header,
.song-sheet-row{
    display:grid;
    grid-template-columns: minmax(0, 1fr) 180px;
}

.song-sheet-header{
    background:#172132;
    border-bottom:1px solid #334155;
}

.song-sheet-header div{
    padding:10px 14px;
    font-size:12px;
    font-weight:900;
    color:#cbd5e1;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.song-sheet-header div:last-child{
    border-left:1px solid #334155;
}

.song-sheet-row{
    border-top:1px solid #253247;
}

.song-sheet-row:first-of-type{
    border-top:none;
}

.song-sheet-row > div{
    padding:11px 14px;
    font-size:14px;
    line-height:1.35;
}

.song-sheet-song{
    color:#fff;
    font-weight:800;
    word-break:break-word;
}

.song-sheet-event{
    color:#cbd5e1;
    border-left:1px solid #253247;
    word-break:break-word;
}

.song-empty{
    color:var(--muted);
    font-size:14px;
}
/* ===== ranking table ===== */
.member-ranking-panel{
    display:flex;
    flex-direction:column;
}

.member-ranking-panel .table-wrap{
    flex:1;
}

.table-wrap{
    width:100%;
    max-width:100%;
    overflow-x:hidden;
    overflow-y:auto;
    max-height:520px;
    border-radius:16px;
    border:1px solid var(--line);
}

.table{
    margin:0;
    color:#fff;
    width:100%;
    table-layout:fixed;
}

.table thead th{
    position:sticky;
    top:0;
    z-index:1;
    background:#161d29 !important;
    border-color:#344054 !important;
    color:#fff;
    font-size:13px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.table tbody td{
    background:#1b212c !important;
    border-color:#344054 !important;
    vertical-align:middle;
    overflow:hidden;
    text-overflow:ellipsis;
}

.table th:nth-child(1),
.table td:nth-child(1){
    width:26%;
}

.table th:nth-child(2),
.table td:nth-child(2){
    width:19%;
}

.table th:nth-child(3),
.table td:nth-child(3){
    width:14%;
}

.table th:nth-child(4),
.table td:nth-child(4){
    width:11%;
}

.table th:nth-child(5),
.table td:nth-child(5){
    width:14%;
}

.table th:nth-child(6),
.table td:nth-child(6){
    width:16%;
}

.badge-part{
    display:inline-block;
    padding:6px 10px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
    background:#253146;
    border:1px solid #3c4d69;
    max-width:100%;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
}

.rank-score{
    font-weight:900;
    font-size:20px;
}

.muted{
    color:var(--muted);
}

.empty{
    color:var(--muted);
    font-size:14px;
}

/* ===== responsive ===== */
@media (max-width: 1400px){
    .dashboard-grid{
        grid-template-columns: minmax(0, 1.3fr) minmax(320px, .9fr);
    }

    .info-row{
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 1200px){
    .dashboard-grid{
        grid-template-columns: 1fr;
    }

    .info-row{
        grid-column:auto;
        grid-template-columns: 1fr;
    }

    .event-song-list{
        max-height:none;
    }
}

@media (max-width: 900px){
    .filters{
        grid-template-columns: 1fr 1fr;
    }

    .part-grid{
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .stats-grid{
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px){
    .page{
        padding:18px 14px 24px;
    }

    .filters{
        grid-template-columns: 1fr;
    }

    .part-grid{
        grid-template-columns: 1fr 1fr;
    }

    .top5-item{
        grid-template-columns: 42px 1fr;
    }

    .top5-join{
        grid-column:2;
    }

    .song-sheet-header,
    .song-sheet-row{
        grid-template-columns: minmax(0, 1fr) 120px;
    }

    .attention-item{
        flex-direction:column;
        align-items:flex-start;
    }
}
</style>
</head>

<body>
<div class="page">

    <div class="topbar">
        <div>
            <h1>📊 Admin Analytics Dashboard</h1>
            <p>Member participation, availability, and event-song history in one place.</p>
        </div>

        <div class="top-actions">
            <a href="index.php" class="action-btn">← Back to admin</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'members'])) ?>" class="action-btn">⬇ Export members CSV</a>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'event_songs'])) ?>" class="action-btn">⬇ Export event songs CSV</a>
        </div>
    </div>

    <form method="get" action="dashboard.php" class="filters">
        <select name="cohort">
            <option value="">All BANdSHI Gens</option>
            <?php if ($cohort_options): while($row = $cohort_options->fetch_assoc()): ?>
                <option value="<?= e($row['cohort']) ?>" <?= $cohort === (string)$row['cohort'] ? 'selected' : '' ?>>
                    BANdSHI Gen <?= e($row['cohort']) ?>
                </option>
            <?php endwhile; endif; ?>
        </select>

        <select name="part">
            <option value="">All parts</option>
            <?php if ($part_options): while($row = $part_options->fetch_assoc()): ?>
                <option value="<?= e($row['part']) ?>" <?= $part === (string)$row['part'] ? 'selected' : '' ?>>
                    <?= e($row['part']) ?>
                </option>
            <?php endwhile; endif; ?>
        </select>

        <button type="submit">Apply</button>
        <a href="dashboard.php" class="filter-reset">Reset</a>
    </form>

    <div class="dashboard-grid">

        <div class="left-col">
            <div class="panel">
                <h3>🧮 Member Summary</h3>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-title">Total members</div>
                        <div class="stat-number"><?= $total_members ?></div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-title">Dashboard focus</div>
                        <div class="stat-number" style="font-size:18px;">People / Availability / Event Songs</div>
                    </div>
                </div>

                <div class="part-grid">
                    <div class="part-card">
                        <div class="part-label">Singer</div>
                        <div class="part-value"><?= $part_counts['singer'] ?></div>
                    </div>
                    <div class="part-card">
                        <div class="part-label">Musician</div>
                        <div class="part-value"><?= $part_counts['musician'] ?></div>
                    </div>
                    <div class="part-card">
                        <div class="part-label">Dancer</div>
                        <div class="part-value"><?= $part_counts['dancer'] ?></div>
                    </div>
                    <div class="part-card">
                        <div class="part-label">Light&amp;Sound</div>
                        <div class="part-value"><?= $part_counts['light&sound'] ?></div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <h3>🏆 Top 5 Event Joiners</h3>
               

                <div class="top5-list">
                    <?php
                    $rank = 1;
                    if ($top_active && $top_active->num_rows > 0):
                        while($row = $top_active->fetch_assoc()):
                            $medal = $rank === 1 ? '🥇' : ($rank === 2 ? '🥈' : ($rank === 3 ? '🥉' : '🏅'));
                    ?>
                        <div class="top5-item">
                            <div class="medal"><?= $medal ?></div>
                            <div>
                                <div class="top5-name"><?= e($row['member_name']) ?></div>
                                <div class="top5-award"><?= e($awardTitles[$rank] ?? 'ดาวเด่นสายเข้าร่วม') ?></div>
                            </div>
                            <div class="top5-join"><?= (int)$row['join_count'] ?> joins</div>
                        </div>
                    <?php
                            $rank++;
                        endwhile;
                    else:
                    ?>
                        <div class="empty">No activity data.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="right-col">
            <div class="panel" >
                <h3>🎵 Songs Played in Each Event</h3>
                

                <div class="event-song-list">
                    <?php if ($event_song_history && $event_song_history->num_rows > 0): ?>
                        <?php while($row = $event_song_history->fetch_assoc()): ?>
                            <div class="event-song-item">
                                <div class="event-song-head">
                                    <div class="event-song-title"><?= e($row['title']) ?></div>
                                    <div class="event-song-date"><?= e($row['event_date']) ?></div>
                                </div>

                                <?php
                                $songs = [];
                                if (!empty($row['songs_list'])) {
                                    $songs = explode(' || ', $row['songs_list']);
                                }
                                ?>

                                <?php if (!empty($songs)): ?>
                                    <div class="song-sheet">
                                        <div class="song-sheet-header">
                                            <div>Song</div>
                                            <div>Event</div>
                                        </div>

                                        <?php foreach ($songs as $song): ?>
                                            <?php
                                            $songText = trim($song);

                                            if (preg_match('/^(.*?)\s*\((\d+)%\)$/u', $songText, $m)) {
                                                $songName = trim($m[1]);
                                            } else {
                                                $songName = $songText;
                                            }
                                            ?>
                                            <div class="song-sheet-row">
                                                <div class="song-sheet-song"><?= e($songName) ?></div>
                                                <div class="song-sheet-event"><?= e($row['title']) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="song-empty">No song history for this event.</div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty">No event song data.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="info-row">
            <div class="panel">
                <h3>🚨 Member Availability Below 20%</h3>
                <div class="alert-box">
                    <strong>Needs Attention</strong><br>
This group has very low availability and is starting to affect scheduling and overall event readiness.
                </div>

                <div class="attention-list">
                    <?php if ($low_availability && $low_availability->num_rows > 0): ?>
                        <?php while($row = $low_availability->fetch_assoc()): ?>
                            <div class="attention-item">
                                <div>
                                    <div class="attention-name"><?= e($row['member_name']) ?></div>
                                 
                                </div>
                                <div class="attention-percent"><?= e($row['percent']) ?>%</div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
<div class="empty">No members below 20% availability.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="panel member-ranking-panel">
                <h3>📋 Member Ranking</h3>
                

                <div class="table-wrap">
                    <table class="table table-dark align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>BANdSHI Gen</th>
                                <th>Part</th>
                                <th>Join</th>
                                <th>Avail %</th>
                                <th>Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($members && $members->num_rows > 0): ?>
                                <?php while($row = $members->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($row['member_name']) ?></strong><br>
                                            <span class="muted" style="font-size:12px;"><?= e($row['full_name']) ?></span>
                                        </td>
                                        <td><?= e($row['cohort']) ?></td>
                                        <td><span class="badge-part"><?= e($row['part']) ?></span></td>
                                        <td><?= (int)$row['join_count'] ?></td>
                                        <td><?= e($row['availability_percent']) ?>%</td>
                                        <td><span class="rank-score"><?= e($row['activity_score']) ?></span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="empty">No members found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>
</body>
</html>