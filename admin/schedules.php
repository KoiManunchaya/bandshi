<?php
require_once '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    exit("Unauthorized");
}
?>

<!DOCTYPE html>
<html>
<head>

<meta charset="UTF-8">
<title>Practice Scheduling</title>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>


<style>
*{
    box-sizing:border-box;
}

html, body{
    height:100%;
}

body{
    background:#08111f;
    color:#fff;
    font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
    margin:0;
}

/* ===== top ===== */
.top-bar{
    padding:18px 24px 0;
    display:flex;
    gap:12px;
    align-items:center;
    flex-wrap:wrap;
}

.back-button{
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

.back-button:hover{
    background:#132037;
    border-color:#3a4d6b;
}

/* ===== layout ===== */
.wrapper{
    display:flex;
    gap:20px;
    padding:18px 20px 20px;
    min-height:calc(100vh - 72px);
    align-items:stretch;
}

/* ===== sidebar ===== */
#external-events{
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

#external-events h3,
#panelTitle{
    margin:0 0 10px;
    font-size:22px;
    line-height:1.2;
    font-weight:800;
    color:#fff;
}

.side-note{
    font-size:14px;
    color:#aab3c2;
    margin-bottom:14px;
    flex-shrink:0;
}

#panelNote{
    font-size:18px;
    color:#ff4fa3;
    font-weight:800;
    margin-bottom:16px;
}

.back-small{
    display:inline-block;
    margin:0 0 14px;
    font-size:14px;
    color:#93c5fd;
    cursor:pointer;
    text-decoration:none;
}

.back-small:hover{
    color:#bfdbfe;
}

.schedule-tip{
    font-size:12px;
    line-height:1.5;
    color:#aeb7c6;
    margin-bottom:14px;
    display:flex;
    align-items:flex-start;
    gap:8px;
    position:relative;
}

.tip-text{
    flex:1;
}

.info-icon{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:20px;
    height:20px;
    border-radius:999px;
    background:#334155;
    color:#fff;
    font-size:12px;
    font-weight:700;
    cursor:pointer;
    position:relative;
    flex-shrink:0;
    margin-top:2px;
}

.tooltip-box{
    display:none;
    position:absolute;
    top:28px;
    right:0;
    width:255px;
    background:#111827;
    color:#fff;
    padding:10px 12px;
    border-radius:10px;
    border:1px solid #374151;
    box-shadow:0 8px 24px rgba(0,0,0,.35);
    z-index:9999;
    font-weight:400;
    line-height:1.5;
}

.tooltip-box::before{
    content:"";
    position:absolute;
    top:-6px;
    right:6px;
    width:10px;
    height:10px;
    background:#111827;
    border-left:1px solid #374151;
    border-top:1px solid #374151;
    transform:rotate(45deg);
}

.info-icon:hover .tooltip-box{
    display:block;
}

#sessionTypes{
    margin-bottom:8px;
    flex-shrink:0;
}

.session-type-card{
    background:#1b2a3f;
    border:1px solid #364961;
    color:#fff;
    border-radius:16px;
    padding:14px 16px;
    margin-bottom:12px;
    cursor:pointer;
    transition:.18s ease;
    user-select:none;
}

.session-type-card:hover{
    background:#243650;
    border-color:#4a617f;
}

.session-type-card strong{
    display:block;
    font-size:17px;
    line-height:1.2;
    font-weight:800;
}

.session-type-card.active{
    box-shadow:0 0 0 2px rgba(255,255,255,.12);
    transform:translateY(-1px);
}

.session-type-card[data-type="run_through"].active{
    background:#294061;
    border-color:#7aa2d6;
}

.session-type-card[data-type="soundcheck"].active{
    background:#8b1e5d;
    border-color:#ff7cbc;
}

#songList{
    flex:1;
    overflow-y:auto;
    padding-right:4px;
    margin-top:6px;
    min-height:0;
}

#songList::-webkit-scrollbar{
    width:8px;
}

#songList::-webkit-scrollbar-track{
    background:transparent;
}

#songList::-webkit-scrollbar-thumb{
    background:#263346;
    border-radius:999px;
}

/* ===== cards in left panel ===== */
.external-event{
    background:#1f2937;
    border:1px solid #334155;
    padding:16px 18px;
    margin-bottom:14px;
    border-radius:18px;
    cursor:pointer;
    transition:.18s ease;
    color:#fff;
    user-select:none;
}

.external-event:hover{
    transform:translateY(-1px);
}

.external-event strong{
    display:block;
    color:#fff;
    font-size:17px;
    line-height:1.25;
    font-weight:800;
    margin-bottom:6px;
}

.external-event .event-date{
    display:block;
    color:#d4dbe6;
    font-size:13px;
    font-weight:500;
}

/* event cards mode */
#songList .external-event{
    opacity:.82;
    filter:saturate(.92) brightness(.92);
}

#songList .external-event:hover{
    opacity:.94;
    filter:saturate(1) brightness(1);
}

#songList .external-event.active{
    opacity:1;
    filter:saturate(1.08) brightness(1.05);
    box-shadow:0 0 0 2px rgba(255,255,255,.16);
    transform:translateY(-1px);
}

/* progress colors in sidebar */
#songList .external-event.song-progress-0{
    background:#374151;
    border-color:#4b5563;
    color:#fff;
}

#songList .external-event.song-progress-25{
    background:#8f1616;
    border-color:#ef4444;
    color:#fff;
}

#songList .external-event.song-progress-50{
    background:#7a4a0a;
    border-color:#fbbf24;
    color:#fff;
}

#songList .external-event.song-progress-75{
    background:#0f7f9c;
    border-color:#22d3ee;
    color:#fff;
}

#songList .external-event.song-progress-100{
    background:#166534;
    border-color:#22c55e;
    color:#fff;
}

#songList .external-event.song-progress-0.active{
    background:#4b5563 !important;
    border-color:#9ca3af !important;
}

#songList .external-event.song-progress-25.active{
    background:#991b1b !important;
    border-color:#f87171 !important;
}

#songList .external-event.song-progress-50.active{
    background:#92540a !important;
    border-color:#fcd34d !important;
}

#songList .external-event.song-progress-75.active{
    background:#0891b2 !important;
    border-color:#67e8f9 !important;
}

#songList .external-event.song-progress-100.active{
    background:#15803d !important;
    border-color:#4ade80 !important;
}

/* ===== save button ===== */
.save-btn{
    border:none;
    border-radius:14px;
    padding:14px 18px;
    font-size:15px;
    font-weight:800;
    cursor:pointer;
    color:#03150b;
    background:#22c55e;
    transition:.18s ease;
}

.save-btn:hover:not(:disabled){
    background:#16a34a;
    transform:translateY(-1px);
}

.save-btn:disabled{
    opacity:.45;
    cursor:not-allowed;
}

/* ===== calendar area ===== */
.calendar-area{
    flex:1;
    min-width:0;
    position:relative;
    display:flex;
}

.calendar-save-bar{
    position:absolute;
    top:14px;
    right:14px;
    z-index:80;
    pointer-events:none;
}

.floating-save-btn{
    pointer-events:auto;
    min-width:170px;
    margin:0;
    box-shadow:0 10px 25px rgba(0,0,0,.28);
}

#calendar{
    width:100%;
    min-width:0;
    background:#05070b;
    border:1px solid #1e293b;
    border-radius:18px;
    padding:10px;
    overflow:hidden;
    box-shadow:0 12px 30px rgba(0,0,0,.22);
}

/* ===== FullCalendar toolbar ===== */
.fc .fc-toolbar{
    margin-bottom:12px !important;
    padding-right:220px;
}

.fc .fc-toolbar-title{
    font-size:28px !important;
    font-weight:900 !important;
    color:#fff;
}

.fc .fc-button{
    background:#334155 !important;
    border:none !important;
    box-shadow:none !important;
    color:#fff !important;
    border-radius:10px !important;
    text-transform:none !important;
    padding:.55em .9em !important;
    font-weight:700 !important;
}

.fc .fc-button:hover{
    background:#41546f !important;
}

.fc .fc-button:disabled{
    opacity:.55 !important;
}

/* ===== day header / grid ===== */
.fc-theme-standard td,
.fc-theme-standard th,
.fc-theme-standard .fc-scrollgrid{
    border-color:rgba(255,255,255,.18) !important;
}

.fc .fc-col-header-cell{
    background:#07090d;
}

.fc .fc-col-header-cell-cushion{
    color:#fff !important;
    font-size:18px;
    font-weight:800;
    padding:10px 4px !important;
}

.fc .fc-timegrid-axis{
    background:#0b1322;
}

.fc .fc-timegrid-axis-cushion,
.fc .fc-timegrid-slot-label-cushion{
    color:#fff !important;
    font-size:16px;
    font-weight:700;
}

.fc .fc-timegrid-slot{
    height:2.2em;
    background:linear-gradient(to bottom, #0c1524, #0a1220);
}

.fc .fc-timegrid-slot-minor{
    border-top-style:solid !important;
    border-top-color:rgba(255,255,255,.08) !important;
}

.fc .fc-timegrid-now-indicator-line{
    border-color:#f87171 !important;
}

.fc .fc-timegrid-divider{
    padding:0 !important;
}

.fc .fc-timegrid-divider-frame{
    background:#07090d !important;
}

.fc .fc-day-today{
    background:rgba(251,191,36,.18) !important;
}

.fc .fc-timegrid-allday{
    background:#07090d;
}

.fc .fc-daygrid-day-number,
.fc .fc-timegrid-slot-label{
    color:#fff !important;
}

/* ===== events ===== */
.fc-event{
    border-radius:12px !important;
    font-weight:700;
    box-shadow:none !important;
}

.fc-timegrid-event{
    padding:0 !important;
}

.fc-timegrid-event .fc-event-main{
    position:relative;
    padding:4px 22px 4px 8px !important;
    height:100%;
}

.fc-daygrid-event .fc-event-main{
    position:relative;
    padding:4px 22px 4px 8px !important;
}

.fc-event-title{
    font-size:12px !important;
    line-height:1.25 !important;
    white-space:normal !important;
    word-break:break-word;
}

.fc-event-time{
    font-size:12px !important;
    font-weight:700 !important;
}

.fc-event.session-runthrough{
    background:#31496b !important;
    border-color:#7aa2d6 !important;
    color:#fff !important;
}

.fc-event.session-soundcheck{
    background:#ff4fa3 !important;
    border-color:#ff4fa3 !important;
    color:#fff !important;
}

.fc-event.progress-0{
    background:#6b7280 !important;
    border-color:#6b7280 !important;
    color:#fff !important;
}

.fc-event.progress-25{
    background:#ef4444 !important;
    border-color:#ef4444 !important;
    color:#fff !important;
}

.fc-event.progress-50{
    background:#f59e0b !important;
    border-color:#f59e0b !important;
    color:#111 !important;
}

.fc-event.progress-75{
    background:#22d3ee !important;
    border-color:#22d3ee !important;
    color:#111 !important;
}

.fc-event.progress-100{
    background:#22c55e !important;
    border-color:#22c55e !important;
    color:#fff !important;
}

.fc-event.suggestion{
    opacity:.55;
    border-style:dashed !important;
}

.fc .event-delete-btn{
    position:absolute;
    top:4px;
    right:4px;
    width:18px;
    height:18px;
    border:none;
    border-radius:999px;
    background:rgba(0,0,0,.35);
    color:#fff;
    font-size:12px;
    font-weight:700;
    line-height:18px;
    text-align:center;
    cursor:pointer;
    display:none;
    padding:0;
    z-index:20;
}

.fc-timegrid-event:hover .event-delete-btn,
.fc-daygrid-event:hover .event-delete-btn{
    display:block;
}

.fc .event-delete-btn:hover{
    background:rgba(0,0,0,.58);
}

/* ===== modal ===== */
.modal{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.5);
    z-index:9999;
    align-items:center;
    justify-content:center;
    padding:20px;
}

.modal-content{
    width:min(460px, 100%);
    background:#0f172a;
    border:1px solid #334155;
    border-radius:16px;
    padding:20px;
    color:#fff;
    box-shadow:0 20px 40px rgba(0,0,0,.35);
}

.modal-content h3{
    margin:0 0 14px;
    font-size:22px;
}

.close-btn{
    margin-top:18px;
    border:none;
    border-radius:12px;
    padding:10px 16px;
    background:#334155;
    color:#fff;
    font-weight:700;
    cursor:pointer;
}

.close-btn:hover{
    background:#475569;
}

/* ===== responsive ===== */
@media (max-width: 1100px){
    .wrapper{
        flex-direction:column;
    }

    #external-events{
        width:100%;
        min-width:0;
        height:auto;
        max-height:none;
    }

    .calendar-area{
        width:100%;
        display:block;
    }

    .calendar-save-bar{
        position:static;
        margin-bottom:12px;
        pointer-events:auto;
    }

    .floating-save-btn{
        width:100%;
        min-width:0;
    }

    #calendar{
        min-height:720px;
    }

    .fc .fc-toolbar{
        padding-right:0;
    }
}
</style>
</head>
<body>

<div class="top-bar">
    <a href="index.php" class="back-button">← Back to admin page</a>
    <a href="admin_weekly_schedule.php" class="back-button" style="margin-left:10px;">View weekly schedule</a>
</div>

<div class="wrapper">

    <div id="external-events">
        <h3 id="panelTitle">Events</h3>
        <div id="panelNote" class="side-note">Select an event first</div>

        <div class="schedule-tip" id="scheduleHelp">
            <span class="tip-text">
                Tip: Select a song to view suggestions, then drag into the calendar.
            </span>

            <span class="info-icon">i
                <span class="tooltip-box">
                    <strong>How to schedule</strong><br>
                    Select a song to view suggested times.<br>
                    Drag songs or session types into the calendar.<br>
                    Or drag on the calendar to create a custom time block.
                </span>
            </span>
        </div>

        <div id="backToEvents" class="back-small" style="display:none;">← Back to events</div>

        <div id="sessionTypes" style="display:none;">
            <div class="side-note">Session Types</div>

            <div class="session-type-card" data-type="run_through" onclick="selectSessionType('run_through')">
                <strong>Run-through</strong>
            </div>

            <div class="session-type-card" data-type="soundcheck" onclick="selectSessionType('soundcheck')">
                <strong>Soundcheck</strong>
            </div>
        </div>

        <div id="songList"></div>
    </div>

    <div class="calendar-area">
        <div class="calendar-save-bar">
            <button class="save-btn floating-save-btn" id="saveBtn" onclick="saveSchedule()" disabled>
                Save Schedule
            </button>
        </div>

        <div id="calendar"></div>
    </div>

</div>

<div class="modal" id="suggestionModal">
    <div class="modal-content">
        <h3 id="modalTitle"></h3>
        <div id="modalBody"></div>
        <button class="close-btn" onclick="closeModal()">Close</button>
    </div>
</div>
<script>
let calendar;
let selectedEventId = null;
let selectedSongId = null;
let selectedSongName = "";
let selectedEventTitle = "";
let selectedSessionType = null;

function hasScheduledOverlap(start, end, ignoreEvent = null){
    if(!calendar || !start || !end) return false;

    return calendar.getEvents().some(function(e){
        if(e.extendedProps.type !== "scheduled") return false;
        if(ignoreEvent && e === ignoreEvent) return false;

        const eventStart = e.start;
        const eventEnd = e.end || e.start;

        return start < eventEnd && end > eventStart;
    });
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof FullCalendar === 'undefined') {
        alert('FullCalendar failed to load');
        return;
    }

    calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
         initialView: 'timeGridWeek',
    editable: true,
    droppable: true,
    selectable: true,
    selectMirror: true,
    selectMinDistance: 5,
    slotDuration: "00:30:00",
    snapDuration: "00:30:00",
    slotMinTime: "09:00:00",
    slotMaxTime: "21:00:00",
    height: 750,
    slotEventOverlap: false,

       selectAllow: function(selectInfo){
    return !hasScheduledOverlap(
        new Date(selectInfo.start),
        new Date(selectInfo.end)
    );
},

        eventAllow: function(dropInfo, draggedEvent){
            if(!draggedEvent) return true;
            return !hasScheduledOverlap(dropInfo.start, dropInfo.end, draggedEvent);
        },

        eventDrop: function(info){
            if(hasScheduledOverlap(info.event.start, info.event.end, info.event)){
                info.revert();
                alert("This time slot is already occupied");
            }
        },

        eventResize: function(info){
            if(hasScheduledOverlap(info.event.start, info.event.end, info.event)){
                info.revert();
                alert("This time slot is already occupied");
            }
        },

        eventReceive: function(info){
            if(!selectedEventId){
                info.event.remove();
                alert("Please select an event first");
                return;
            }

            if(hasScheduledOverlap(info.event.start, info.event.end, info.event)){
                info.event.remove();
                alert("This time slot is already occupied");
                return;
            }

            info.event.setExtendedProp("type", "scheduled");
            info.event.setExtendedProp("event_id", selectedEventId);

            if(!info.event.extendedProps.session_type){
                info.event.setExtendedProp("session_type", "song_practice");
            }

            if(!info.event.extendedProps.custom_title){
                info.event.setExtendedProp("custom_title", info.event.title);
            }

            if(!info.event.extendedProps.song_name){
                info.event.setExtendedProp("song_name", info.event.title);
            }
        },

        eventClick: function(info){
            const type = info.event.extendedProps.type;

            if(type === "suggestion"){
                showSuggestionDetail(info.event);
                return;
            }
        },

        eventDidMount: function(info){
            const type = info.event.extendedProps.type;

            if(type !== "scheduled") return;

            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "event-delete-btn";
            btn.innerHTML = "&times;";
            btn.title = "Delete";

            btn.addEventListener("click", function(e){
                e.preventDefault();
                e.stopPropagation();

                if(confirm("Remove this session?")){
                    info.event.remove();
                }
            });

            info.el.appendChild(btn);
        },
dateClick: function(info){
    if(!selectedEventId){
        alert("Please select an event first");
        return;
    }

    if(!selectedSessionType && !selectedSongId){
        alert("Please select a song or a session type first");
        return;
    }

    const start = new Date(info.date);
    const end = new Date(start.getTime() + 60 * 60 * 1000); // default 1 hr

    if(hasScheduledOverlap(start, end)){
        alert("This time slot is already occupied");
        return;
    }

    const type = selectedSessionType || 'song_practice';
    const title = getSessionTitle();

    let extraClass = '';
    if(type === 'run_through') extraClass = 'session-runthrough';
    else if(type === 'soundcheck') extraClass = 'session-soundcheck';
    else extraClass = getProgressClass(
        document.querySelector('#songList .external-event.active')?.dataset.songProgress || 0
    );

    calendar.addEvent({
        title: title,
        start: start,
        end: end,
        classNames: ["scheduled", extraClass],
        extendedProps: {
            type: "scheduled",
            event_id: selectedEventId,
            song_id: type === 'song_practice' ? selectedSongId : null,
            song_name: type === 'song_practice' ? selectedSongName : null,
            song_progress: type === 'song_practice'
                ? parseInt(document.querySelector('#songList .external-event.active')?.dataset.songProgress || 0, 10)
                : null,
            session_type: type,
            custom_title: title
        }
    });
},

select: function(info){
    if(!selectedEventId){
        calendar.unselect();
        alert("Please select an event first");
        return;
    }

    if(!selectedSessionType && !selectedSongId){
        calendar.unselect();
        alert("Please select a song or a session type first");
        return;
    }

    const start = new Date(info.start);
    const end = new Date(info.end);

    if(hasScheduledOverlap(start, end)){
        calendar.unselect();
        alert("This time slot is already occupied");
        return;
    }

    const type = selectedSessionType || 'song_practice';
    const title = getSessionTitle();

    let extraClass = '';
    if(type === 'run_through') extraClass = 'session-runthrough';
    else if(type === 'soundcheck') extraClass = 'session-soundcheck';
    else extraClass = getProgressClass(
        document.querySelector('#songList .external-event.active')?.dataset.songProgress || 0
    );

    calendar.addEvent({
        title: title,
        start: start,
        end: end,
        classNames: ["scheduled", extraClass],
        extendedProps: {
            type: "scheduled",
            event_id: selectedEventId,
            song_id: type === 'song_practice' ? selectedSongId : null,
            song_name: type === 'song_practice' ? selectedSongName : null,
            song_progress: type === 'song_practice'
                ? parseInt(document.querySelector('#songList .external-event.active')?.dataset.songProgress || 0, 10)
                : null,
            session_type: type,
            custom_title: title
        }
    });

    calendar.unselect();
},
    });

    calendar.render();
    setEventMode();
    loadEvents();

    document.getElementById("backToEvents").onclick = function(){
        selectedEventId = null;
        selectedSongId = null;
        selectedSongName = "";
        selectedEventTitle = "";
        selectedSessionType = null;

        clearSuggestions();
        clearScheduledEvents();
        document.getElementById("songList").innerHTML = "";
        document.querySelectorAll('.session-type-card').forEach(el => el.classList.remove('active'));
        setEventMode();
        loadEvents();
    };
});

function setEventMode(){
    document.getElementById("panelTitle").innerText = "Events";
    document.getElementById("panelNote").innerText = "Select an event first";
    document.getElementById("backToEvents").style.display = "none";
    document.getElementById("scheduleHelp").style.display = "none";
    document.getElementById("saveBtn").disabled = true;
    document.getElementById("external-events").classList.remove("song-mode");
    document.getElementById("sessionTypes").style.display = "none";
}

function setSongMode(eventTitle){
    document.getElementById("panelTitle").innerText = "Sessions";
    document.getElementById("panelNote").innerText = eventTitle;
    document.getElementById("backToEvents").style.display = "inline-block";
    document.getElementById("saveBtn").disabled = false;
    document.getElementById("external-events").classList.add("song-mode");
    document.getElementById("sessionTypes").style.display = "block";
    document.getElementById("scheduleHelp").style.display = "flex";
}

function toLocalDateTimeString(date){
    const pad = n => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth()+1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
}

function getSessionTitle(){
    if(selectedSessionType === 'run_through') return 'Run-through';
    if(selectedSessionType === 'soundcheck') return 'Soundcheck';
    return selectedSongName || 'Song Practice';
}

function getProgressClass(progress){
    const p = parseInt(progress, 10) || 0;
    if(p >= 100) return 'progress-100';
    if(p >= 75) return 'progress-75';
    if(p >= 50) return 'progress-50';
    if(p >= 25) return 'progress-25';
    return 'progress-0';
}

function selectSessionType(type){
    selectedSessionType = type;
    selectedSongId = null;
    selectedSongName = '';

    document.querySelectorAll('.session-type-card').forEach(el => el.classList.remove('active'));
    const target = document.querySelector(`.session-type-card[data-type="${type}"]`);
    if(target) target.classList.add('active');

    document.querySelectorAll('#songList .external-event').forEach(el => el.classList.remove('active'));
    clearSuggestions();
}

function loadEvents(){
    fetch("fetch_events.php")
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById("songList");
        container.innerHTML = "";

        if(!Array.isArray(data) || !data.length){
            container.innerHTML = `<div class="side-note">No upcoming events</div>`;
            return;
        }

        data.forEach(ev => {
            const div = document.createElement("div");
            div.className = "external-event";
            div.innerHTML = `
                <strong>${ev.title}</strong>
                <span class="event-date">${ev.event_date}</span>
            `;

            div.onclick = function(){
                selectedEventId = ev.id;
                selectedEventTitle = ev.title;
                selectedSongId = null;
                selectedSongName = "";
                selectedSessionType = null;

                document.querySelectorAll('.session-type-card').forEach(el => el.classList.remove('active'));

                clearSuggestions();
                clearScheduledEvents();
                setSongMode(ev.title);
                loadSongsByEvent(ev.id);
                loadSavedSchedule(ev.id);
                initSessionTypeDragging();
            };

            container.appendChild(div);
        });
    })
    .catch(err => {
        console.error(err);
        document.getElementById("songList").innerHTML = `<div class="side-note">Failed to load events</div>`;
    });
}

function loadSongsByEvent(eventId){
    fetch("fetch_suggestions.php?mode=songs&event_id=" + eventId)
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById("songList");
        container.innerHTML = "";

        if(!Array.isArray(data) || !data.length){
            container.innerHTML = `<div class="side-note">No songs for this event</div>`;
            return;
        }

        data.forEach(song => {
            const div = document.createElement("div");
            const progressClass = getProgressClass(song.progress);

            div.className = `external-event ${progressClass.replace('progress-', 'song-progress-')}`;
            div.dataset.songId = song.id;
            div.dataset.songName = song.name;
            div.dataset.songProgress = song.progress;

            div.innerHTML = `
                <strong>${song.name}</strong><br>
                Progress: ${song.progress}%
            `;

            div.onclick = function(){
                document.querySelectorAll('#songList .external-event').forEach(el => el.classList.remove('active'));
                div.classList.add('active');

                document.querySelectorAll('.session-type-card').forEach(el => el.classList.remove('active'));

                selectedSessionType = 'song_practice';
                selectedSongId = song.id;
                selectedSongName = song.name;

                showSuggestions(eventId, song.id);
            };

            container.appendChild(div);
        });

        /* เพลงไม่ต้อง drag จาก sidebar แล้ว */
    })
    .catch(err => {
        console.error(err);
        document.getElementById("songList").innerHTML = `<div class="side-note">Failed to load songs</div>`;
    });
}



function clearSuggestions(){
    calendar.getEvents().forEach(ev => {
        if(ev.extendedProps.type === "suggestion"){
            ev.remove();
        }
    });
}

function clearScheduledEvents(){
    calendar.getEvents().forEach(ev => {
        if(ev.extendedProps.type === "scheduled"){
            ev.remove();
        }
    });
}

function showSuggestions(eventId, songId){
    clearSuggestions();

    fetch(`fetch_suggestions.php?mode=suggestions&event_id=${eventId}&song_id=${songId}`)
    .then(res => res.json())
    .then(data => {
        if(!Array.isArray(data)) return;

        data.forEach(item => {
            calendar.addEvent({
                title: item.title || selectedSongName,
                start: item.start,
                end: item.end,
                classNames: ['suggestion'],
                extendedProps: {
                    type: 'suggestion',
                    detail: item
                }
            });
        });
    })
    .catch(err => console.error(err));
}

function showSuggestionDetail(event){
    const detail = event.extendedProps.detail || {};
    document.getElementById('modalTitle').innerText = event.title || 'Suggestion';
    document.getElementById('modalBody').innerHTML = `
        <div><strong>Start:</strong> ${detail.start || '-'}</div>
        <div><strong>End:</strong> ${detail.end || '-'}</div>
        <div><strong>Reason:</strong> ${detail.reason || '-'}</div>
    `;
    document.getElementById('suggestionModal').style.display = 'flex';
}

function closeModal(){
    document.getElementById('suggestionModal').style.display = 'none';
}

function loadSavedSchedule(eventId){
    fetch("fetch_saved_schedule.php?event_id=" + eventId)
    .then(res => res.json())
    .then(data => {
        if(!Array.isArray(data)) return;

        data.forEach(item => {
            let extraClass = 'progress-0';

            if(item.session_type === 'run_through') extraClass = 'session-runthrough';
            else if(item.session_type === 'soundcheck') extraClass = 'session-soundcheck';
            else extraClass = getProgressClass(item.song_progress);

            calendar.addEvent({
                title: item.custom_title,
                start: item.start_time,
                end: item.end_time,
                classNames: ['scheduled', extraClass],
                extendedProps: {
                    type: 'scheduled',
                    event_id: item.event_id,
                    song_id: item.song_id,
                    song_name: item.song_name,
                    song_progress: item.song_progress,
                    session_type: item.session_type,
                    custom_title: item.custom_title
                }
            });
        });
    })
    .catch(err => console.error(err));
}

function saveSchedule(){
    if(!selectedEventId){
        alert("Please select an event first");
        return;
    }

    const events = calendar.getEvents()
        .filter(ev => ev.extendedProps.type === "scheduled")
        .map(ev => ({
            event_id: selectedEventId,
            song_id: ev.extendedProps.song_id || null,
            song_name: ev.extendedProps.song_name || null,
            song_progress: ev.extendedProps.song_progress || null,
            session_type: ev.extendedProps.session_type || 'song_practice',
            custom_title: ev.extendedProps.custom_title || ev.title,
            start_time: toLocalDateTimeString(ev.start),
            end_time: toLocalDateTimeString(ev.end)
        }));

    fetch("save_schedule.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            event_id: selectedEventId,
            schedules: events
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            alert("Schedule saved successfully");
        }else{
            alert(data.message || "Failed to save schedule");
        }
    })
    .catch(err => {
        console.error(err);
        alert("Failed to save schedule");
    });
}
</script>
</body>
</html>

