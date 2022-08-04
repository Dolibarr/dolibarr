<?php
/*
 * File: folder_structure.blade.php
 * Category: View
 * Author: M.Goldenbaum
 * Created: 15.09.18 19:53
 * Updated: -
 *
 * Description:
 *  -
 */

/**
 * @var \Webklex\PHPIMAP\Support\FolderCollection $paginator
 * @var \Webklex\PHPIMAP\Folder $folder
 */

?>
<table>
    <thead>
    <tr>
        <th>Folder</th>
        <th>Unread messages</th>
    </tr>
    </thead>
    <tbody>
    <?php if($paginator->count() > 0): ?>
        <?php foreach($paginator as $folder): ?>
                <tr>
                    <td><?php echo $folder->name; ?></td>
                    <td><?php echo $folder->search()->unseen()->setFetchBody(false)->count(); ?></td>
                </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="4">No folders found</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<?php echo $paginator->links(); ?>