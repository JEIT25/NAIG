<?php
$c = mysqli_connect('localhost', 'root', '', 'naig_db');
$r = mysqli_query($c, 'DESCRIBE users');
while($row = mysqli_fetch_assoc($r)) {
    echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Default'] . "\n";
}
