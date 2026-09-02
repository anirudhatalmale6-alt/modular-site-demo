<?php
/**
 * seed-demo-data.php — fills the demo with plausible comments and messages.
 *
 * This exists so the demo does not look empty. Delete this file before the
 * site goes live; nothing else references it.
 *
 *     php seed-demo-data.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only.\n");
}

require __DIR__ . '/core/bootstrap.php';

$pdo = Db::get();
$pdo->exec('DELETE FROM comments');
$pdo->exec('DELETE FROM messages');
$pdo->exec('DELETE FROM rate_log');   // so the rate limiter starts fresh too

$comments = [
    ['noticeboard', 'Marian Fowles', 'approved', "Is the Thursday change permanent or just for March? I've been putting the wrong date in my diary for a fortnight.", '-9 days', "Just for March — the hall is double-booked that week. April goes back to the usual Wednesday."],
    ['noticeboard', 'D. Ashworth', 'approved', "Thank you for putting the minutes up so quickly this time. Much appreciated by those of us who can't get to the meetings.", '-7 days', null],
    ['noticeboard', 'Ify Okonkwo', 'approved', "Would it be possible to have the noticeboard photographed and posted here as well? My mother doesn't drive any more and misses the paper notices.", '-4 days', "A good idea. We'll photograph it each Tuesday from next week."],
    ['noticeboard', 'Tom R.', 'pending', "The gully outside number 41 is blocked again. Third time this winter.", '-2 hours', null],

    ['page:about', 'Helen Vasey', 'approved', "Fifty years this year — is anything planned to mark it? There must be people who remember the adoption of the road.", '-6 days', null],
    ['page:about', 'G. Pryce', 'approved', "Correction for the record: the association was formed in November 1974, not the spring. I have the founding letter if it is of use to the archive.", '-3 days', "Thank you — please do bring it in. It will be scanned and filed."],

    ['memo:lighting-replacement', 'Sandra Whitlock', 'approved', "Very glad to see the badger sett taken seriously. Could the specification be published in full? I'd like to see the shielding detail.", '-5 days', null],
    ['memo:lighting-replacement', 'K. Bhattacharya', 'approved', "2700K is the right call. The estate over the hill went with 4000K and it looks like a car park at night.", '-5 days', null],
    ['memo:lighting-replacement', 'Anonymous resident', 'pending', "Will the work affect access for deliveries during the day? I'm at the far end of the path.", '-1 day', null],

    ['memo:annual-accounts-2025', 'R. Dunmore', 'approved', "£320 under estimate on the footpath is impressive. Who was the contractor?", '-2 days', null],

    ['album:summer-fete', 'Priya Raval', 'approved', "The photograph of the cake stall is worth framing. Whoever took it, thank you.", '-8 days', null],

    ['noticeboard', 'CHEAP LOANS NOW', 'spam', "BUY BACKLINKS CHEAP! visit https://example.com/spam https://example.com/more https://example.com/again best seo services", '-1 day', null],
    ['noticeboard', 'xnzkrt', 'spam', "casino bonus click here www.example.net", '-3 hours', null],
];

$stmt = $pdo->prepare(
    "INSERT INTO comments (thread, author, email, body, status, reply, ip, spam_score, spam_reason, created_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now', ?))"
);

foreach ($comments as [$thread, $author, $status, $body, $when, $reply]) {
    $score  = $status === 'spam' ? 11 : 0;
    $reason = $status === 'spam' ? 'spam vocabulary; links in the body' : null;
    $stmt->execute([$thread, $author, '', $body, $status, $reply, '203.0.113.' . random_int(2, 250), $score, $reason, $when]);
}

$messages = [
    ['Wendy Attah', 'w.attah@example.com', 'Reading room hire, 4 May', "Good morning — I'd like to book the reading room for a family gathering on Saturday 4 May, from midday to five. Is it free, and is the deposit still £30?", 'new', '-5 hours'],
    ['Michael Osei', 'm.osei@example.com', 'Copy of the 1998 lighting minutes', "Is the 1998 memorandum about the original lighting scheme in the archive? I can't find it online and it would be useful for a comparison.", 'new', '-1 day'],
    ['J. Halloran', 'jhalloran@example.com', 'Fallen branch, lower footpath', "There's a large branch down across the lower footpath about thirty metres past the gate. Passable but awkward with a pushchair.", 'read', '-3 days'],
];

$stmt = $pdo->prepare(
    "INSERT INTO messages (name, email, subject, body, status, ip, created_at) VALUES (?, ?, ?, ?, ?, ?, datetime('now', ?))"
);
foreach ($messages as [$name, $email, $subject, $body, $status, $when]) {
    $stmt->execute([$name, $email, $subject, $body, $status, '203.0.113.9', $when]);
}

echo "Seeded " . count($comments) . " comments and " . count($messages) . " messages.\n";
