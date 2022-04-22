<?php
$logs = '';
foreach ($blame as $log):
    $date = $log['logTimestamp'];
    $logs .= 
    "<tr>
        <td> $log[id] </td>
        <td> $log[action] </td>
        <td> $log[userBrowserName] </td>
        <td> $log[userBrowserVersion] </td>
        <td>".$date->format('d-m-Y H:i:s')." </td>
    </tr>";
endforeach;
?>

<div id="user-log" class="aba-content">

    <table class="history-table entity-table">
        <caption> Logs de acesso do usuário </caption>
        <thead>
            <tr>
                <th>log ID</th>
                <th>Action</th>
                <th>Browser</th>
                <th>Browser Version</th>
                <th>Data / Hora</th>
            </tr>
        </thead>
        <tbody>
            <?= $logs ?>
        </tbody>
    </table>

</div>

<!-- 
'log_id'
'request_id'
'ip'
'session_id'
'user_id'
'action'
'user_agent'
'user_browser_name'
'user_browser_version'
'user_os'
'user_device'
'request_metadata'
'log_metadata'
'request_ts'
'log_ts' 
-->

