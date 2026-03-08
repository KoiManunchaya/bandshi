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
body{
    background:#0f141a;
    color:#fff;
    font-family:system-ui;
    margin:0;
}

.wrapper{
    display:flex;
    gap:20px;
    padding:20px;
}

/* ===== Sidebar ===== */
#external-events{
    width:300px;
    background:#111;
    padding:18px;
    border-radius:12px;
    border:1px solid #222;
}

.external-event{
    background:#1f2937;
    border:1px solid #334155;
    padding:12px;
    margin-bottom:12px;
    border-radius:10px;
    cursor:pointer;
    transition:.2s;
}

.external-event:hover{
    background:#263449;
}

.external-event.active{
    background:#2563eb;
    border-color:#2563eb;
}

.save-btn{
    margin-top:15px;
    background:#22c55e;
    border:none;
    padding:12px;
    width:100%;
    border-radius:10px;
    font-weight:600;
    cursor:pointer;
}

.save-btn:hover{
    background:#16a34a;
}

/* ===== Calendar ===== */
#calendar{
    flex:1;
    background:#111;
    padding:10px;
    border-radius:12px;
    border:1px solid #222;
}

.fc-timegrid-slot{
    background:#141922;
    border-bottom:1px solid #1f2937 !important;
}

.fc-event{
    border-radius:8px !important;
    font-size:12px;
    padding:4px;
}

/* ===== Suggestion Levels ===== */

.fc-event.suggestion-low{
    background:#3f1d1d !important;
    border:1px solid #7f1d1d !important;
    color:#fca5a5 !important;
}

.fc-event.suggestion-mid{
    background:#3a2f12 !important;
    border:1px solid #a16207 !important;
    color:#fde68a !important;
}

.fc-event.suggestion-high{
    background:#123524 !important;
    border:1px solid #166534 !important;
    color:#86efac !important;
}

.fc-event.suggestion-perfect{
    background:#052e1f !important;
    border:1px solid #22c55e !important;
    color:#4ade80 !important;
    box-shadow:0 0 8px rgba(34,197,94,.6);
}

/* scheduled */
.fc-event.scheduled{
    background:#2563eb !important;
    border-color:#2563eb !important;
}

/* ===== Modal ===== */

.modal{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.75);
    backdrop-filter: blur(5px);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
}

.modal-content{
    background:#0f172a;
    width:460px;
    padding:28px;
    border-radius:16px;
    border:1px solid #1e293b;
    box-shadow:0 20px 60px rgba(0,0,0,.6);
}

.modal h3{
    margin:0 0 12px 0;
    font-size:20px;
}

.progress-bar{
    height:10px;
    background:#1e293b;
    border-radius:999px;
    overflow:hidden;
    margin:8px 0 16px 0;
}

.progress-fill{
    height:100%;
    border-radius:999px;
    transition:.3s;
}

.badge{
    display:inline-block;
    padding:6px 10px;
    font-size:12px;
    border-radius:999px;
    margin:4px 4px 0 0;
}

.badge-missing{
    background:#3f1d1d;
    color:#f87171;
    border:1px solid #7f1d1d;
}

.badge-sub{
    background:#1e3a2a;
    color:#4ade80;
    border:1px solid #14532d;
}

.close-btn{
    margin-top:20px;
    background:#2563eb;
    border:none;
    padding:10px 16px;
    border-radius:10px;
    cursor:pointer;
}

.top-bar{
    padding:20px 30px 0 30px;
}

.back-button{
    display:inline-block;
    background:#111827;
    border:1px solid #374151;
    color:#e5e7eb;
    padding:8px 16px;
    border-radius:8px;
    text-decoration:none;
    font-size:14px;
    transition:.2s;
}

.back-button:hover{
    background:#1f2937;
    border-color:#4b5563;
}
</style>
</head>
<body>

<div class="top-bar">
<a href="index.php" class="back-button">
← Back to admin page
</a>
</div>

<div class="wrapper">

<div id="external-events">
<h3>Songs</h3>
<div id="songList"></div>
<button class="save-btn" onclick="saveSchedule()">Save Schedule</button>
</div>

<div id="calendar"></div>

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

document.addEventListener('DOMContentLoaded', function() {

calendar = new FullCalendar.Calendar(
document.getElementById('calendar'),
{
initialView:'timeGridWeek',
editable:true,
droppable:true,
slotMinTime:"09:00:00",
slotMaxTime:"21:00:00",
height:750,

eventClick:function(info){

const type = info.event.extendedProps.type;

if(type==="suggestion"){
showSuggestionDetail(info.event);
return;
}

if(type==="scheduled"){
if(confirm("Remove this session?")){
info.event.remove();
}
}

}
}
);

calendar.render();
loadSongs();
});

/* ===== Load Songs From DB ===== */

function loadSongs(){

fetch("fetch_suggestions.php")
.then(res=>res.json())
.then(data=>{

const container=document.getElementById("songList");
container.innerHTML="";

data.forEach(song=>{

const div=document.createElement("div");
div.className="external-event";

div.dataset.songId = song.id;
div.dataset.songName = song.name;

div.innerHTML=`
<strong>${song.name}</strong><br>
Progress: ${song.progress}%
`;

div.onclick=function(){

document.querySelectorAll('.external-event')
.forEach(el=>el.classList.remove('active'));

div.classList.add('active');

showSuggestions(song);
};

container.appendChild(div);
});

new FullCalendar.Draggable(container,{
itemSelector:'.external-event',
eventData:function(el){
return{
title:el.dataset.songName,
duration:"02:00",
classNames:["scheduled"],
extendedProps:{
type:"scheduled",
song_id:el.dataset.songId
}
};
}
});

});
}

/* ===== Suggestion ===== */

function showSuggestions(song){

calendar.getEvents().forEach(e=>{
if(e.extendedProps.type==="suggestion"){
e.remove();
}
});

fetch("suggest_times.php?song_id="+song.id)
.then(res=>res.json())
.then(data=>{

data.forEach(slot=>{

let className="suggestion-low";

if(slot.readiness>=70) className="suggestion-high";
if(slot.readiness>=40 && slot.readiness<70) className="suggestion-mid";
if(slot.missing_roles==="") className="suggestion-perfect";

const start = slot.date+"T"+slot.start_time;

const endDate = new Date(start);
endDate.setHours(endDate.getHours()+2);

calendar.addEvent({
title:`${song.name}\nReady: ${slot.readiness}%`,
start:start,
end:endDate,
classNames:[className],
editable:false,
durationEditable:false,
extendedProps:{
type:"suggestion",
readiness:slot.readiness,
missingCount:slot.missing_roles.split(",").length,
missingRoles:slot.missing_roles.split(","),
substitutes:slot.substitutes.split(","),
song_name:song.name
}
});

});

});
}

/* ===== Modal ===== */

function showSuggestionDetail(event){

const readiness=event.extendedProps.readiness;
const missingCount=event.extendedProps.missingCount;
const roles=event.extendedProps.missingRoles;
const subs=event.extendedProps.substitutes;

let color="#22c55e";
if(readiness<70) color="#f59e0b";
if(readiness<40) color="#ef4444";

let missingHtml="";
let subHtml="";

if(missingCount===0){
missingHtml=`<div class="badge badge-sub">ครบทุกตำแหน่ง</div>`;
}else{
missingHtml=roles.map(r=>`<div class="badge badge-missing">${r}</div>`).join("");
subHtml=subs.map(s=>`<div class="badge badge-sub">${s}</div>`).join("");
}

document.getElementById("modalTitle").innerText=event.extendedProps.song_name;

document.getElementById("modalBody").innerHTML=`
<div>Readiness: <strong>${readiness}%</strong></div>
<div class="progress-bar">
<div class="progress-fill" style="width:${readiness}%;background:${color}"></div>
</div>
${missingHtml}
${subHtml}
`;

document.getElementById("suggestionModal").style.display="flex";
}

function closeModal(){
document.getElementById("suggestionModal").style.display="none";
}

/* ===== Save Schedule To DB ===== */

function saveSchedule(){

const events = calendar.getEvents()
.filter(e=>e.extendedProps.type==="scheduled");

const payload = events.map(e=>({
song_id:e.extendedProps.song_id,
start:e.start,
end:e.end
}));

fetch("save_schedule.php",{
method:"POST",
headers:{
"Content-Type":"application/json"
},
body:JSON.stringify(payload)
})
.then(res=>res.text())
.then(msg=>{
alert(msg);
});

}

</script>

</body>
</html>