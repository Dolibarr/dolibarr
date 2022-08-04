<?php
/*
 * File: message_table.blade.php
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
 * @var \Webklex\PHPIMAP\Message $message
 */

?>
<table>
    <thead>
    <tr>
        <th>UID</th>
        <th>Subject</th>
        <th>From</th>
        <th>Attachments</th>
    </tr>
    </thead>
    <tbody>
    <?php if($paginator->count() > 0): ?>
        <?php foreach($paginator as $message): ?>
            <tr>
                <td><?php echo $message->getUid(); ?></td>
                <td><?php echo $message->getSubject(); ?></td>
                <td><?php echo $message->getFrom()[0]->mail; ?></td>
                <td><?php echo $message->getAttachments()->count() > 0 ? 'yes' : 'no'; ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="4">No messages found</td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<?php echo $paginator->links(); ?>