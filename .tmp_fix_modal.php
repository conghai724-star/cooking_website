<?php
function fixModal($file) {
    if (!file_exists($file)) return;
    $c = file_get_contents($file);
    $c = preg_replace('/Vi ph\?m b\?n quy\?n/', 'Vi phạm bản quyền', $c);
    $c = preg_replace('/H\?y/', 'Hủy', $c);
    file_put_contents($file, $c);
    echo "Fixed modal $file\n";
}
fixModal('app/views/recipes/partials/report_modal.php');
