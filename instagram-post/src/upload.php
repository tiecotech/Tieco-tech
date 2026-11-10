<?php

// Local-only helper: receives the recorded video from reel.html and saves it next to the slides.
$ext = ($_GET['ext'] ?? '') === 'mp4' ? 'mp4' : 'webm';
$bytes = file_put_contents(__DIR__.'/../reel-raw.'.$ext, file_get_contents('php://input'));
header('Content-Type: text/plain');
echo $bytes === false ? 'error' : "saved {$bytes} bytes as reel-raw.{$ext}";
