<?php

use EDAM\NoteStore\NoteFilter;
use EDAM\NoteStore\NotesMetadataResultSpec;
use Evernote\Exception\NotFoundNotebookException;

require __DIR__ . '/../../vendor/autoload.php';

/**
 * Authorization Tokens are created by either:
 * [1] OAuth workflow: https://dev.evernote.com/doc/articles/authentication.php
 * or by creating a 
 * [2] Developer Token: https://dev.evernote.com/doc/articles/authentication.php#devtoken
 */
$token = '%YOUR_TOKEN%';

/** Understanding SANDBOX vs PRODUCTION vs CHINA Environments
 *
 * The Evernote API 'Sandbox' environment -> SANDBOX.EVERNOTE.COM 
 *    - Create a sample Evernote account at https://sandbox.evernote.com
 * 
 * The Evernote API 'Production' Environment -> WWW.EVERNOTE.COM
 *    - Activate your Sandboxed API key for production access at https://dev.evernote.com/support/
 * 
 * The Evernote API 'CHINA' Environment -> APP.YINXIANG.COM
 *    - Activate your Sandboxed API key for Evernote China service access at https://dev.evernote.com/support/ 
 *      or https://dev.yinxiang.com/support/. For more information about Evernote China service, please refer 
 *      to https://dev.evernote.com/doc/articles/bootstrap.php
 *
 * For testing, set $sandbox to true; for production, set $sandbox to false and $china to false; 
 * for china service, set $sandbox to false and $china to true.
 * 
 */
$sandbox = true;
$china   = false;

$client = new \Evernote\Client($token, $sandbox, null, null, $china);

$notebook_guid = "%YOUR_NOTE_BOOK_GUID%";

$notebook = $client->getNotebook($notebook_guid);

if (empty($notebook)) {
    throw new NotFoundNotebookException('Notebook not found');
}

if ($notebook->isLinkedNotebook()) {
    $notestore = $client->getAdvancedClient()->getSharedNoteStore($notebook->getLinkedNotebook());
}
else {
    $notestore = $notebook->isBusinessNotebook() ? $client->getBusinessNoteStore() : $client->getUserNoteStore();
}

$note_token = $notestore->getToken();

$filter = new NoteFilter();
$filter->notebookGuid = $notebook->getGuid();

$result_spec = new NotesMetadataResultSpec();
$result_spec->includeTitle = true;
$result_spec->includeCreated = true;
$result_spec->includeUpdated = true;

$offset = 0;
$max_results = 1000;
do {
    $result = $notestore->findNotesMetadata($note_token, $filter, $offset, $max_results, $result_spec);
    echo '<div>';
    foreach ($result->notes as $note) {
        echo "<p><b>guid</b> : {$note->guid}</p>";
        echo "<p><b>notebook_guid</b> : $notebook_guid</p>";
        echo "<p><b>note</b> : {$note->title}</p>";
        echo "<p><b>url</b> : <a href='https://www.evernote.com/client/web#?anb=true&b={$notebook->guid}&n={$note->guid}&s=s348&search=v4&'>{$note->title}</a></p>";
    }
    echo '</div>';
    $offset += $max_results;
} while ($offset < $result->totalNotes);