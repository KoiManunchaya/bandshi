<?php
function pickBestSlot($slots) {
  usort($slots, fn($a,$b) => $b['score'] <=> $a['score']);
  return $slots[0] ?? null;
}
