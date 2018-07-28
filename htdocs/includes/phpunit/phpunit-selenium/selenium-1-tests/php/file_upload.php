<?php
if ($_FILES) {
    $file = $_FILES['upload_here'];
    echo "<div id=\"uploaded\">
        The file name is <span id=\"name\">{$file['name']}</span>
        and its size is <span id=\"size\">{$file['size']}</span>
        </div>
        ";
}
?>
<form action="" method="post" enctype="multipart/form-data">
    <input type="file" name="upload_here" id="upload_here" />
    <input type="submit" id="submit" value="Upload" />
</form>
