<?php
$c = mysqli_connect('localhost', 'root', '', 'naig_db');
$r = mysqli_query($c, "UPDATE users SET role = 'consumer' WHERE role = '' OR role IS NULL");
echo mysqli_affected_rows($c) . " rows updated.\n";
