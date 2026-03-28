<?php
require_once '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    exit("Unauthorized");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Weekly Schedule Overview</title>

<style>
*{
    box-sizing:border-box;
}

html, body{
    height:100%;
}

body{
    margin:0;
    background:#08111f;
    color:#fff;
    font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
}

.top-bar{
    padding:18px 24px 0;
}

.top-actions{
    display:flex;
    gap:12px;
    align-items:center;
    flex-wrap:wrap;
}

.back-button,
.top-link{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 16px;
    border:1px solid #2a3a52;
    border-radius:12px;
    background:#0d1728;
    color:#fff;
    text-decoration:none;
    font-size:15px;
    font-weight:600;
    transition:.18s ease;
}

.back-button:hover,
.top-link:hover{
    background:#132037;
    border-color:#3a4d6b;
}

.wrapper{
    display:flex;
    gap:20px;
    padding:18px 20px 20px;
    min-height:calc(100vh - 72px);
    align-items:stretch;
}

.sidebar{
    width:360px;
    min-width:360px;
    background:#05070b;
    border:1px solid #1e293b;
    border-radius:18px;
    padding:20px;
    display:flex;
    flex-direction:column;
    overflow:hidden;
    box-shadow:0 12px 30px rgba(0,0,0,.22);
}

.sidebar h2{
    margin:0 0 8px;
    font-size:22px;
    line-height:1.2;
    font-weight:800;
    color:#fff;
}

.sidebar-subtitle{
    font-size:15px;
    color:#aab3c2;
    margin-bottom:16px;
}

.selected-event-name{
    font-size:18px;
    color:#ff4fa3;
    font-weight:800;
    margin-bottom:14px;
    min-height:26px;
}

.side-note{
    font-size:13px;
    color:#92a1b6;
    margin-bottom:12px;
}

.legend{
    display:grid;
    gap:10px;
    margin-bottom:18px;
    padding:14px;
    border:1px solid #1f2d42;
    background:#0b1220;
    border-radius:14px;
}

.legend-title{
    font-size:14px;
    font-weight:700;
    color:#d6dbea;
    margin-bottom:2px;
}

.legend-item{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:13px;
    color:#c5cfdd;
}

.legend-color{
    width:14px;
    height:14px;
    border-radius:999px;
    flex-shrink:0;
}

.color-progress-25{ background:#ef4444; }
.color-progress-50{ background:#f59e0b; }
.color-progress-75{ background:#22d3ee; }
.color-progress-100{ background:#22c55e; }

#eventList{
    flex:1;
    overflow-y:auto;
    padding-right:4px;
    min-height:0;
}

#eventList::-webkit-scrollbar{
    width:8px;
}

#eventList::-webkit-scrollbar-track{
    background:transparent;
}

#eventList::-webkit-scrollbar-thumb{
    background:#263346;
    border-radius:999px;
}

.event-card{
    background:#1f2937;
    border:1px solid #334155;
    border-radius:16px;
    padding:14px 16px;
    margin-bottom:12px;
    cursor:pointer;
    transition:.18s ease;
}

.event-card:hover{
    background:#2a374d;
    border-color:#4b5d78;
    transform:translateY(-1px);
}

.event-card.active{
    background:#132037;
    border-color:#60a5fa;
    box-shadow:0 0 0 2px rgba(96,165,250,.18);
}

.event-card strong{
    display:block;
    color:#fff;
    font-size:16px;
    line-height:1.25;
    font-weight:800;
    margin-bottom:6px;
}

.event-date{
    display:block;
    color:#cbd5e1;
    font-size:13px;
    font-weight:500;
}

.event-meta{
    display:block;
    color:#8fa1ba;
    font-size:12px;
    margin-top:4px;
}

.summary-box{
    margin-top:16px;
    padding:14px;
    border-radius:14px;
    background:#0b1220;
    border:1px solid #1f2d42;
    color:#c5cfdd;
    font-size:13px;
    line-height:1.6;
}

.main-panel{
    flex:1;
    min-width:0;
    background:#05070b;
    border:1px solid #1e293b;
    border-radius:18px;
    padding:20px;
    box-shadow:0 12px 30px rgba(0,0,0,.22);
    overflow:hidden;
}

.panel-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
    margin-bottom:18px;
    flex-wrap:wrap;
}

.panel-title{
    margin:0;
    font-size:24px;
    font-weight:900;
    color:#fff;
}

.panel-subtitle{
    margin-top:6px;
    font-size:14px;
    color:#9eb0c8;
}

.week-label{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:10px 14px;
    border-radius:12px;
    background:#132037;
    border:1px solid #2a3a52;
    color:#fff;
    font-weight:800;
}

.table-wrap{
    overflow:auto;
    border:1px solid #22304a;
    border-radius:16px;
}

.schedule-table{
    width:100%;
    border-collapse:collapse;
    min-width:980px;
    table-layout:fixed;
    background:#0a1220;
}

.schedule-table th,
.schedule-table td{
    border:1px solid #24324c;
    padding:14px 12px;
    vertical-align:middle;
}

.schedule-table thead th{
    background:#070d18;
    color:#fff;
    font-size:15px;
    font-weight:800;
    text-align:center;
}

.schedule-table tbody td{
    background:#0d1627;
    color:#e5ecf6;
    font-size:14px;
}

.schedule-table .date-cell{
    width:120px;
    text-align:center;
    font-weight:900;
    font-size:16px;
    color:#08111f;
}

.schedule-table .date-cell .date-main{
    display:block;
    font-size:18px;
    line-height:1.1;
}

.schedule-table .date-cell .date-sub{
    display:block;
    margin-top:4px;
    font-size:13px;
    font-weight:700;
}

.schedule-table .date-sun{ background:#f4b4b4; color:#111827; } /* อาทิตย์ = แดง */
.schedule-table .date-mon{ background:#f3e39a; color:#111827; } /* จันทร์ = เหลือง */
.schedule-table .date-tue{ background:#e8c2d8; color:#111827; } /* อังคาร = ชมพู */
.schedule-table .date-wed{ background:#cfe3be; color:#111827; } /* พุธ = เขียว */
.schedule-table .date-thu{ background:#f2d2a2; color:#111827; } /* พฤหัส = ส้ม */
.schedule-table .date-fri{ background:#b9d8f4; color:#111827; } /* ศุกร์ = ฟ้า */
.schedule-table .date-sat{ background:#d9c7ef; color:#111827; } /* เสาร์ = ม่วง */
.time-cell{
    width:110px;
    text-align:center;
    font-weight:800;
    font-size:15px;
    color:#fff;
    background:#111c31 !important;
}

.song-cell{
    font-weight:800;
    font-size:16px;
}

.song-pill{
    display:inline-flex;
    flex-direction:column;
    align-items:flex-start;
    gap:4px;
    padding:10px 14px;
    border-radius:14px;
    font-weight:800;
    line-height:1.25;
    min-width:140px;
    max-width:220px;
}

.song-name{
    font-size:15px;
    font-weight:900;
    line-height:1.2;
}

.song-progress-inside{
    font-size:12px;
    font-weight:800;
    opacity:.9;
    line-height:1.2;
}

.song-pill.progress-25{
    background:#ef4444;
    color:#fff;
}

.song-pill.progress-50{
    background:#f59e0b;
    color:#111;
}

.song-pill.progress-75{
    background:#22d3ee;
    color:#111;
}

.song-pill.progress-100{
    background:#22c55e;
    color:#fff;
}

.song-pill.progress-0{
    background:#6b7280;
    color:#fff;
}

.missing-cell,
.sub-cell{
    line-height:1.6;
    word-break:break-word;
}

.empty-state{
    display:flex;
    align-items:center;
    justify-content:center;
    min-height:280px;
    color:#8fa1ba;
    font-size:16px;
    font-weight:600;
    text-align:center;
    padding:40px 20px;
    border:1px dashed #2a3a52;
    border-radius:16px;
    background:#091221;
}

@media (max-width: 1100px){
    .wrapper{
        flex-direction:column;
    }

    .sidebar{
        width:100%;
        min-width:0;
        max-height:none;
    }
}

/* เส้นคั่นระหว่างวัน */
.schedule-table tr.day-start td{
    border-top:4px solid #6b7280 !important;
}

/* อยากให้ date cell เด่นขึ้นอีก */
.schedule-table tr.day-start .date-cell{
    box-shadow:inset 0 4px 0 rgba(255,255,255,.18);
}

/* บรรทัดคั่นดูนุ่มขึ้น */
.schedule-table tbody tr.day-start td{
    border-top-color:#7c8798 !important;
}

/* ถ้าเป็นวันแรก ไม่ต้องหนามาก */
.schedule-table tbody tr.day-start.first-day td{
    border-top:1px solid #24324c !important;
}

.week-nav{
    display:flex;
    align-items:center;
    gap:10px;
}

.week-nav-btn{
    width:42px;
    height:42px;
    border:none;
    border-radius:12px;
    background:#132037;
    border:1px solid #2a3a52;
    color:#fff;
    font-size:24px;
    font-weight:800;
    cursor:pointer;
    transition:.18s ease;
}

.week-nav-btn:hover{
    background:#1a2b47;
}

</style>
</head>
<body>

<div class="top-bar">
    <div class="top-actions">
        <a href="index.php" class="back-button">← Back to admin page</a>
        <a href="schedules.php" class="top-link">Open set schedule page</a>
    </div>
</div>

<div class="wrapper">
    <aside class="sidebar">
        <h2>Saved Schedules</h2>
        <div class="sidebar-subtitle">Weekly table view by event</div>
        <div id="selectedEventName" class="selected-event-name">Select an event</div>

        <div class="legend">
            <div class="legend-title">Legend</div>

            <div class="legend-item">
                <span class="legend-color color-progress-25"></span>
                <span>Song Progress 25%</span>
            </div>

            <div class="legend-item">
                <span class="legend-color color-progress-50"></span>
                <span>Song Progress 50%</span>
            </div>

            <div class="legend-item">
                <span class="legend-color color-progress-75"></span>
                <span>Song Progress 75%</span>
            </div>

            <div class="legend-item">
                <span class="legend-color color-progress-100"></span>
                <span>Song Progress 100%</span>
            </div>
        </div>

        <div class="side-note">Events</div>
        <div id="eventList"></div>

        <div class="summary-box" id="summaryBox">
            Select an event to load its saved schedule.
        </div>
    </aside>

    <section class="main-panel">
        <div class="panel-head">
            <div>
                <h1 class="panel-title">Weekly Schedule Overview</h1>
    
            </div>

            <div class="week-nav">
    <button type="button" class="week-nav-btn" onclick="changeWeek(-1)">‹</button>
    <div class="week-label" id="weekLabel">No week selected</div>
    <button type="button" class="week-nav-btn" onclick="changeWeek(1)">›</button>
</div>
        </div>

        <div id="scheduleContent" class="empty-state">
            Select an event on the left to view its saved weekly schedule.
        </div>
    </section>
</div>

<script>
let selectedEventId = null;
let selectedEventTitle = '';
let allWeeklyRows = [];
let currentWeekStart = null;

document.addEventListener('DOMContentLoaded', function(){
    loadEvents();
});

function loadEvents(){
    fetch('fetch_schedule_events.php')
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('eventList');
            container.innerHTML = '';

            if(!Array.isArray(data) || !data.length){
                container.innerHTML = '<div class="side-note">No events found</div>';
                return;
            }

            data.forEach(ev => {
                const card = document.createElement('div');
                card.className = 'event-card';
                card.dataset.eventId = ev.id;

                card.innerHTML = `
                    <strong>${escapeHtml(ev.title)}</strong>
                    <span class="event-date">${escapeHtml(ev.event_date || '-')}</span>
                    <span class="event-meta">Saved blocks: ${Number(ev.schedule_count || 0)}</span>
                `;

                card.onclick = function(){
                    document.querySelectorAll('.event-card').forEach(el => el.classList.remove('active'));
                    card.classList.add('active');

                    selectedEventId = ev.id;
                    selectedEventTitle = ev.title;

                    document.getElementById('selectedEventName').innerText = ev.title;
                    loadWeeklyOverview(ev.id, ev.title);
                };

                container.appendChild(card);
            });
        })
        .catch(err => {
            console.error(err);
            document.getElementById('eventList').innerHTML = '<div class="side-note">Failed to load events</div>';
        });
}
function loadWeeklyOverview(eventId, eventTitle){
    fetch('fetch_weekly_overview.php?event_id=' + encodeURIComponent(eventId))
        .then(res => res.json())
        .then(data => {
            allWeeklyRows = Array.isArray(data) ? data : [];

            if(allWeeklyRows.length){
                currentWeekStart = getStartOfWeek(allWeeklyRows[0].date);
            }else{
                currentWeekStart = null;
            }

            renderWeeklyTable(eventTitle);
        })
        .catch(err => {
            console.error(err);
            document.getElementById('scheduleContent').innerHTML = `
                <div class="empty-state">Failed to load saved schedule.</div>
            `;
            document.getElementById('summaryBox').innerHTML = `
                <strong>${escapeHtml(eventTitle)}</strong><br>
                Failed to load saved schedule.
            `;
            document.getElementById('weekLabel').innerText = 'Load failed';
        });
}

function renderWeeklyTable(eventTitle){
    const container = document.getElementById('scheduleContent');
    const summaryBox = document.getElementById('summaryBox');
    const weekLabel = document.getElementById('weekLabel');

    if(!Array.isArray(allWeeklyRows) || !allWeeklyRows.length){
        container.innerHTML = `<div class="empty-state">No saved song practice schedule found for this event.</div>`;
        summaryBox.innerHTML = `
            <strong>${escapeHtml(eventTitle)}</strong><br>
            No saved song practice schedule found.
        `;
        weekLabel.innerText = 'No data';
        return;
    }

    if(!currentWeekStart){
        currentWeekStart = getStartOfWeek(allWeeklyRows[0].date);
    }

    const weekRows = allWeeklyRows.filter(row => isInCurrentWeek(row.date, currentWeekStart));
    weekLabel.innerText = formatWeekLabel(currentWeekStart);

    if(!weekRows.length){
        container.innerHTML = `<div class="empty-state">No saved schedule in this week.</div>`;
        summaryBox.innerHTML = `
            <strong>${escapeHtml(eventTitle)}</strong><br>
            No saved schedule in this week.
        `;
        return;
    }

    const grouped = groupByDate(weekRows);
    const dates = Object.keys(grouped);

    let totalBlocks = weekRows.length;
    let missingTotal = 0;

    weekRows.forEach(row => {
        missingTotal += Array.isArray(row.missing_members) ? row.missing_members.length : 0;
    });

    summaryBox.innerHTML = `
        <strong>${escapeHtml(eventTitle)}</strong><br>
        Total blocks: ${totalBlocks}<br>
        Dates used: ${dates.length}<br>
        Missing-member mentions: ${missingTotal}
    `;

    let html = `
        <div class="table-wrap">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th style="width:120px;">Date</th>
                        <th style="width:110px;">Start</th>
                        <th style="width:110px;">End</th>
                        <th style="width:260px;">Song</th>
                        <th>Missing members</th>
                        <th>Substitute</th>
                    </tr>
                </thead>
                <tbody>
    `;

    dates.forEach(date => {
        const items = grouped[date];
        const dateInfo = formatDateCell(date);

        items.forEach((item, idx) => {
            const missingMembers = Array.isArray(item.missing_members) && item.missing_members.length
                ? item.missing_members.map(escapeHtml).join(', ')
                : '-';

            const subMembers = Array.isArray(item.sub_members) && item.sub_members.length
                ? item.sub_members.map(escapeHtml).join(', ')
                : '-';

            const progressClass = getProgressClass(item.song_progress);

            const rowClass = idx === 0
                ? (date === dates[0] ? 'day-start first-day' : 'day-start')
                : '';

            html += `<tr class="${rowClass}">`;

            if(idx === 0){
                html += `
                    <td class="date-cell ${getDateColorClass(date)}" rowspan="${items.length}">
                        <span class="date-main">${escapeHtml(dateInfo.dayLabel)}</span>
                        <span class="date-sub">${escapeHtml(dateInfo.dateLabel)}</span>
                    </td>
                `;
            }

            html += `
                <td class="time-cell">${escapeHtml(item.start || '-')}</td>
                <td class="time-cell">${escapeHtml(item.end || '-')}</td>
                <td class="song-cell">
                    <span class="song-pill ${progressClass}">
                        <span class="song-name">${escapeHtml(item.song || '-')}</span>
                        <span class="song-progress-inside">Progress: ${Number(item.song_progress ?? 0)}%</span>
                    </span>
                </td>
                <td class="missing-cell">${missingMembers}</td>
                <td class="sub-cell">${subMembers}</td>
            `;

            html += '</tr>';
        });
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    container.innerHTML = html;
}

function groupByDate(rows){
    return rows.reduce((acc, row) => {
        const key = row.date || 'unknown';
        if(!acc[key]) acc[key] = [];
        acc[key].push(row);
        return acc;
    }, {});
}

function getProgressClass(score){
    const p = parseInt(score, 10) || 0;
    if(p >= 100) return 'progress-100';
    if(p >= 75) return 'progress-75';
    if(p >= 50) return 'progress-50';
    if(p >= 25) return 'progress-25';
    return 'progress-0';
}

function formatDateCell(dateStr){
    const d = new Date(dateStr + 'T00:00:00');

    if(Number.isNaN(d.getTime())){
        return {
            dayLabel: dateStr,
            dateLabel: ''
        };
    }

    const dayLabel = d.toLocaleDateString('en-GB', { weekday: 'short' });
    const dayNum = d.getDate();
    const monthLabel = d.toLocaleDateString('en-GB', { month: 'short' });

    return {
        dayLabel: `${dayLabel}`,
        dateLabel: `${dayNum} ${monthLabel}`
    };
}

function getStartOfWeek(dateStr){
    const d = new Date(dateStr + 'T00:00:00');

    if(Number.isNaN(d.getTime())) return null;

    const day = d.getDay(); // 0=Sun
    const diffToSunday = -day;

    const start = new Date(d);
    start.setDate(d.getDate() + diffToSunday);
    start.setHours(0,0,0,0);

    return start;
}

function isInCurrentWeek(dateStr, weekStart){
    if(!weekStart) return false;

    const d = new Date(dateStr + 'T00:00:00');
    if(Number.isNaN(d.getTime())) return false;

    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekStart.getDate() + 6);
    weekEnd.setHours(23,59,59,999);

    return d >= weekStart && d <= weekEnd;
}

function formatWeekLabel(weekStart){
    if(!weekStart) return 'No week selected';

    return 'Week of ' + weekStart.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

function changeWeek(offset){
    if(!currentWeekStart) return;

    const nextWeek = new Date(currentWeekStart);
    nextWeek.setDate(nextWeek.getDate() + (offset * 7));
    currentWeekStart = nextWeek;

    renderWeeklyTable(selectedEventTitle);
}

function getDateColorClass(dateStr){
    const d = new Date(dateStr + 'T00:00:00');

    if(Number.isNaN(d.getTime())){
        return 'date-mon';
    }

    const day = d.getDay(); // 0=Sun, 1=Mon, ... 6=Sat

    if(day === 0) return 'date-sun';
    if(day === 1) return 'date-mon';
    if(day === 2) return 'date-tue';
    if(day === 3) return 'date-wed';
    if(day === 4) return 'date-thu';
    if(day === 5) return 'date-fri';
    return 'date-sat';
}

function escapeHtml(value){
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

</body>
</html>