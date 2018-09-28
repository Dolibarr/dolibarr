<?php
$res=@include("../main.inc.php");
if (! $res) $res=@include("../../main.inc.php");
?>
<html>
<head>
<script>
  window.location.href = "frontend";
</script>
</head>
</html>