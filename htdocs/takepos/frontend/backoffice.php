<?php
$res=@include("../../main.inc.php");
if (! $res) $res=@include("../../../main.inc.php");
?>
<html>
<head>
<script>
window.location.href = "<?php echo DOL_URL_ROOT;?>";
</script>
</head>
</html>