<?php
// Run: php tests/run_booking_validation_tests.php
// Pure PHP tests; no MySQL needed.
require_once __DIR__ . '/../includes/booking_validation.php';

$passed = 0;
$failed = 0;
function expect($condition, $label) {
    global $passed, $failed;
    if ($condition) { ++$passed; echo "PASS  $label\n"; }
    else { ++$failed; echo "FAIL  $label\n"; }
}

$valid = [
 ['A1',1], ['H10',1], ['a1, H10',2], ['B3, A5, C10',3],
 [implode(', ',array_map(fn($i) => 'C'.$i,range(1,10))),10]
];
foreach ($valid as [$seat,$qty]) {
    $r=cb_validate_seats($seat,$qty);
    expect(!$r['errors'] && count($r['seats']) === $qty, "valid $seat ($qty)");
}
$invalid = [
 ['Z1',1,'outside rows'], ['I1',1,'outside rows I'], ['A0',1,'zero column'],
 ['A11',1,'column 11'], ['B99',1,'column 99'], ['A1, A1',2,'duplicate'],
 ['a1,A1',2,'case-fold duplicate'], ['A1,',1,'trailing comma'],
 [',A1',1,'leading comma'], ['A1 B2',2,'missing comma'], ['A1;B2',2,'bad separator'],
 ['',1,'empty'], ['A1,A2',1,'quantity mismatches'], ['A1',2,'quantity too high'],
 [implode(',',array_map(fn($i) => 'C'.$i,range(1,10))).',D1',11,'too many'],
 [str_repeat('A1,',50),1,'huge input'], ['A-1',1,'bad syntax'], ['A01',1,'leading zero'],
 ['H11',1,'H11'], ['AA1',1,'two letters'], ['A1,A1,A2',3,'duplicate among three']
];
foreach ($invalid as [$seat,$qty,$label]) {
    $r=cb_validate_seats($seat,$qty);
    expect(count($r['errors']) > 0, "reject $label");
}
$tz = new DateTimeZone('Asia/Singapore');
$now = new DateTimeImmutable('2026-10-28 12:00:00',$tz);
expect(cb_showtime_is_future('2026-11-01','14:30:00',$now),'future showtime');
expect(!cb_showtime_is_future('2026-10-27','14:30:00',$now),'past showtime');
expect(!cb_showtime_is_future('2026-10-28','12:00:00',$now),'showtime now not future');
expect(!cb_showtime_is_future('2026-02-31','18:00:00',$now),'invalid date rejected');
expect(!cb_showtime_is_future('2026-11-01','25:00:00',$now),'invalid time rejected');
$_POST=['movie_id'=>'11', 'payment_type'=>'2', 'full_name'=>['array'],'num_tickets'=>'2cats'];
expect(cb_post_positive_int('movie_id') === 11, 'clean showtime ID accepted');
expect(cb_post_positive_int('num_tickets') === 0, 'tampered quantity rejected');
expect(cb_post_text('full_name') === '', 'array field rejected');

echo "\n$passed passed; $failed failed\n";
exit($failed ? 1 : 0);
